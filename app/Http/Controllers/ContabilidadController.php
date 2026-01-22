<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BitacoraController;
use Shuchkin\SimpleXLSX;
use DB;
use Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Poliza;
use Illuminate\Support\Carbon;
use App\Models\Cuenta;
use App\Models\Auxiliar;
use Illuminate\Support\Facades\Auth;

class ContabilidadController extends Controller
{
    private $documentoFuente = "";
    public int $anio;

    public function __construct()
    {
        $this->middleware('auth');
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }

    public function movimientosDeudores()
    {
        return view('contabilidad.movimientos-deudores');
    }

    public function movimientosDiario()
    {
        return view('contabilidad.movimientos-diario');
    }
    
    public function polizaInicial()
    {
        return view('contabilidad.carga-poliza-inicial');
    }

    public function registroPolizaDiario()
    {
        return view('contabilidad.registro-poliza-diario');
    }

    public function consultaPolizaInicial()
    {
        return view('contabilidad.consulta-poliza-inicial');
    }

    public function auxiliares()
    {
        return view('contabilidad.carga-auxiliares');
    }

    public function cargarPolizaInicial(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'input-archivo' => 'required',
            'input-archivo.*' => 'mimes:xlsx',
            'selectDocumentoFuente' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()->messages()));
            session()->flash('message', implode(" ", $errors));
            session()->flash('message_type', 'error');
            return back();
        }
        $archivo = $request->file('input-archivo');
        $this->documentoFuente = $request->get('selectDocumentoFuente');
        // Validar que el archivo pueda ser analizado correctamente.
        if ($xlsx = SimpleXLSX::parse($archivo)) {
            // Validar que los encabezados coincidan con los campos esperados.
            $expectedHeaders = ['Cuenta', 'Descripcion', 'Cargo', 'Abono', 'Naturaleza'];
  
            $actualHeaders = $xlsx->rows()[0];
            $actualHeaders = array_map('trim', array_filter($actualHeaders));
            if (count($expectedHeaders) !== count($actualHeaders) || array_diff($expectedHeaders, $actualHeaders) || count($xlsx->sheetNames()) > 1) {
                session()->flash('message', 'Los campos del archivo no coinciden con los campos esperados o no se cumple con el formato.');
                session()->flash('message_type', 'error');
                return back();
            }
            $encabezados = $rows = [];
            $errores = [];
            try {
                $reemplazarCaracterEspecial = function ($texto) {
                    return str_replace("\xc2\xa0", '', $texto);
                };
                $numeroRegistros = 0;
                foreach ($xlsx->rows() as $numero_fila => $datos_fila) {
                    if ($numero_fila === 0) {
                        $encabezados = $datos_fila;
                        continue;
                    }
                    $numeroRegistros++;
                    if (count($encabezados) != count($datos_fila)) {
                        dd($encabezados, $datos_fila);
                    }
                    $rows[] = array_combine(array_map('trim', array_filter($encabezados)), array_map('trim', array_map($reemplazarCaracterEspecial, $datos_fila)));

                    $fila = array_combine(
                        array_map('trim', array_filter($encabezados)),
                        array_map('trim', array_map($reemplazarCaracterEspecial, $datos_fila))
                    );

                    $columnas = ['Cargo', 'Abono'];

                    foreach ($columnas as $columna) {

                        if (isset($fila[$columna])) {
                            $valor = $fila[$columna];

                            // Eliminar posibles caracteres no numéricos (espacios o caracteres especiales)
                            $valor = preg_replace('/[^\d.-]/', '', $valor);

                            // Validar si el valor es numérico solo si no está vacío
                            if ($valor !== '' && !is_numeric($valor)) {
                                $errores[] = "El valor de $columna en la fila $numero_fila no es numérico.";
                                continue;
                            }

                            // Validar si el valor es mayor o igual a 0 (no negativo) solo si no está vacío
                            if ($valor !== '' && $valor < 0) {
                                $errores[] = "El valor de $columna en la fila $numero_fila no debe ser negativo.";
                            }

                            // Validar si el valor tiene como máximo dos decimales solo si no está vacío
                            if ($valor !== '' && !preg_match('/^\d+(\.\d{1,2})?$/', $valor)) {
                                $errores[] = "El valor de $columna en la fila $numero_fila debe tener como máximo dos dígitos después del punto decimal.";
                            }

                            // Asignar el valor limpio de vuelta al array
                            $fila[$columna] = $valor;
                        }
                    }
                    if (empty($fila['Cargo']) && empty($fila['Abono'])) {
                        $errores[] = "Al menos una de las columnas 'Cargo' o 'Abono' debe estar llena en la fila $numero_fila.";
                    }
                    // Si la fila es válida, agregarla al array de filas válidas

                }


                // Validar que al menos una de las columnas "Cargo" o "Abono" no esté vacía


                // Si hay errores, devolverlos y abortar la operación
                if (!empty($errores)) {
                    session()->flash('message', implode('<br>', $errores));
                    session()->flash('message_type', 'error');
                    return back();
                }
                // Se inicia una transacción de base de datos para que todas las operaciones de base de datos dentro del bloque se puedan revertir si ocurre algún error.
                $usuariosController = new BitacoraController();
                $usuariosController->bitacora('cargarPolizaInicial', 'cargó o intentó cargar la póliza inicial de contabilidad', $request);
                DB::beginTransaction();
                $total = 0;
                $totalPresupuestos = count($rows);
                $numeroRegistros--; //Se quita uno, ya que hay una fila que es el total
                $cuentasFaltantes = [];
                $presupuestosRepetidos = [];
                $cuentasEnLaGuiaFaltantes = [];
                // $poliza = Poliza::whereYear('fecha', '=', Carbon::now()->year)->orderBy('numero_poliza','DESC')->first();
                // $numeroPoliza = $poliza ? $poliza->numero_poliza + 1 : 1;
                $numerosEvento = Poliza::select('evento')
                    ->whereYear('fecha', '=', (string) $this->anio)
                    ->distinct()
                    ->orderBy('evento')
                    ->pluck('evento')
                    ->toArray();
                $ultimoEvento = end($numerosEvento);
                $eventoFaltante = [];

                for ($i = 1; $i <= $ultimoEvento; $i++) {
                    if (!in_array($i, $numerosEvento)) {
                        $eventoFaltante[] = $i;
                    }
                }

                if (empty($eventoFaltante)) {
                    $poliza = Poliza::whereYear('fecha', '=', (string) $this->anio)->orderBy('evento', 'DESC')->first();
                    $numeroEvento = $poliza ? $poliza->evento + 1 : 1;
                } else {
                    $numeroEvento = $eventoFaltante[0];
                }
                // Se procesan los datos de cada fila del archivo Excel y se crea un nuevo registro en la base de datos utilizando el modelo Cuenta.
                foreach ($rows as $row) {
                    $cuenta = Cuenta::where("Codigo_cuenta", $row["Cuenta"])->first();

                    if (!$cuenta) {
                        if (!$cuenta && !in_array($row["Cuenta"], $cuentasFaltantes)) {
                            $cuentasFaltantes[] = $row["Cuenta"];
                        }
                    }

                    $buscarPolizaInicial = Poliza::where('cuenta', '=', $row['Cuenta'])->whereYear('fecha', '=', (string) $this->anio)->where('categoria', '=', 'SALDO INICIAL')->first();
                    if ($buscarPolizaInicial) {
                        if ($buscarPolizaInicial->validado) {

                            DB::rollBack();
                            session()->flash('message', 'Los saldos iniciales ya se encuentran validados.');
                            session()->flash('message_type', 'error');
                            return back();
                        } else {
                            DB::rollBack();
                            session()->flash('message', 'Ya existe un saldo inicial cargado no validado. Si quiere realizar cambios, primero elimine el saldo inicial desde la consulta de saldos iniciales.');
                            session()->flash('message_time', '0');
                            session()->flash('message_type', 'error');
                            return back();
                        }
                    }
                    $creacionExitosaPoliza = $this->generarPolizasInicial($row, $numeroEvento);
                    if (!$creacionExitosaPoliza) {
                        continue;
                    }
                    // $presupuestoInicial = PresupuestoInicial::create([
                    //     'area' => $row['Area Recaudadora'],
                    //     'tipo' => $row['TIPO'],
                    //     'anio' => Carbon::now()->year + 1,
                    //     'cuenta' => $row['Cuenta'],
                    //     'descripcion' => $row['Descripción'],
                    //     'concepto' => $row['CONCEPTO'],
                    //     'total' => $row['TOTAL'],
                    //     'monto_enero' => $row['ENERO'],
                    //     'monto_febrero' => $row['FEBRERO'],
                    //     'monto_marzo' => $row['MARZO'],
                    //     'monto_abril' => $row['ABRIL'],
                    //     'monto_mayo' => $row['MAYO'],
                    //     'monto_junio' => $row['JUNIO'],
                    //     'monto_julio' => $row['JULIO'],
                    //     'monto_agosto' => $row['AGOSTO'],
                    //     'monto_septiembre' => $row['SEPTIEMBRE'],
                    //     'monto_octubre' => $row['OCTUBRE'],
                    //     'monto_noviembre' => $row['NOVIEMBRE'],
                    //     'monto_diciembre' => $row['DICIEMBRE'],
                    //     'fecha' => Carbon::now('America/Mexico_City'),
                    //     'validado' => false,
                    //     'categoria' => 'INGRESOS'
                    // ]);

                }

                // Se verifica si hubo errores durante la importación. Si no hubo errores, se confirma la transacción y se redirige con un mensaje de éxito.
                // Si hubo errores, se revierte la transacción y se redirige con un mensaje de error que muestra los errores.
                if (empty($cuentasFaltantes)) {
                    DB::commit();
                    session()->flash('message', 'Importación exitosa del presupuesto inicial');
                    session()->flash('message_type', 'success');
                    return back();
                    // return response()->json(['mensaje' => 'Importación exitosa con un total de ' . $total . ' presupuestos.', 'error' => '', 'Cuentas faltantes' => $cuentasFaltantes, 'Presupuestos repetidos' => $presupuestosRepetidos, 'Guia contabilizadora faltante' => $cuentasEnLaGuiaFaltantes]);
                } else {
                    DB::rollBack();
                    session()->flash('message', 'No se creó el saldo inicial. Se descargará un archivo con las cuentas faltantes.');
                    session()->flash('message_type', 'error');

                    $txtFileName = 'CuentasFaltantes.txt';
                    $txtFilePath = public_path('/CuentasFaltantes/' . $txtFileName);
                    $archivo = fopen($txtFilePath, 'w');

                    fwrite($archivo, 'Cuentas Faltantes' . PHP_EOL . '---------------------------------------------------------------------------------------' . PHP_EOL);

                    foreach ($cuentasFaltantes as $cuenta) {
                        $linea = 'Código de cuenta: ' . $cuenta;
                        fwrite($archivo, $linea . PHP_EOL . '---------------------------------------------------------------------------------------' . PHP_EOL);
                    }

                    fclose($archivo);

                    session()->flash('download', '1');
                    session()->flash('path', '/CuentasFaltantes/' . $txtFileName);
                    session()->flash('nombreArchivo', $txtFileName);
                    return back();
                }
                // Si ocurre alguna excepción durante el proceso de importación, se revierte la transacción
                // y se redirige con un mensaje de error que incluye detalles sobre la excepción.
            } catch (\Exception $error) {
                DB::rollBack();
                Log::debug($error->getMessage());
                session()->flash('message', 'error' . $error->getMessage());
                session()->flash('message_type', 'error');
                return back();
            }

            // Si no se puede analizar el archivo Excel correctamente (por ejemplo, si el formato no es válido),
            // se redirige con un mensaje de error que incluye información sobre el error de análisis.
        } else {
            session()->flash('message', ['error' => 'Error al analizar el archivo Excel: ' . SimpleXLSX::parseError()]);
            session()->flash('message_type', 'error');
            return back();
        }
    }

    public function generarPolizasInicial($row, $numeroEvento)
    {
        $idUsuarioRegistrante = Auth::id();
        $anioActual = $this->anio;
        $fecha = Carbon::now('America/Mexico_City');
        $fecha->year($anioActual);
        $poliza = new Poliza([
            'idUsuarioRegistrante' => $idUsuarioRegistrante,
            'area' => '0',
            'tipo_poliza' => 'SI',
            'numero_poliza' => '1',
            'fecha' => $fecha,
            'cuenta' => $row['Cuenta'],
            'concepto' => $row['Descripcion'],
            'total' => $row['Cargo'] != '' ? $row['Cargo'] : $row['Abono'],
            'mes' => 'Enero',
            'descripcion' => 'CARGA DE SALDOS INICIALES DEL EJERCICIO ' . $anioActual,
            'evento' => $numeroEvento,
            'tipo_interaccion' => $row['Cargo'] != '' ? 'Contable - Cargo' : 'Contable - Abono',
            'validado' => false,
            'categoria' => 'SALDO INICIAL',
            'documento_fuente' => $this->documentoFuente,
            'created_at' => $fecha,
            'updated_at' => $fecha
        ]);
        $poliza->save();
        return true;
    }

    public function plantillaPolizaInicial()
    {
        $rutaArchivo = public_path('PresupuestoInicial/Formato carga poliza inicial.xlsx');
        // Verificar si el archivo existe
        if (file_exists($rutaArchivo)) {
            // Descargar el archivo Excel
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('plantillaPolizaInicial', 'descargó la plantilla de carga de poliza inicial', request());
            return response()->download($rutaArchivo, 'Formato carga poliza inicial.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } else {
            abort(404);
        }
    }

    public function registrarAuxiliares(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'input-archivo' => 'required|mimes:xlsx,xls',
            'anio'    => 'required|numeric'
        ]);

        if ($validator->fails()) {
            session()->flash('message', implode(" ", $validator->errors()->all()));
            session()->flash('message_type', 'error');
            return back();
        }

        $anio = $request->anio;

        $existenDelAnio = Auxiliar::where('anio', $anio)->exists();

        if ($existenDelAnio) {
            session()->flash('message', "Ya existen auxiliares registrados para el año {$anio}. No es posible importar nuevamente.");
            session()->flash('message_type', 'error');
            return back();
        }

        $archivo = $request->file('input-archivo');

        if (!$xlsx = SimpleXLSX::parse($archivo)) {
            session()->flash('message', 'Error al leer el archivo: ' . SimpleXLSX::parseError());
            session()->flash('message_type', 'error');
            return back();
        }

        $encabezadosEsperados = [
            "Cuenta", "Descripción", "Enero", "Febrero", "Marzo", "Abril",
            "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre",
            "Noviembre", "Diciembre"
        ];

        $encabezados = array_map('trim', $xlsx->rows()[0]);

        if ($encabezados != $encabezadosEsperados) {
            session()->flash('message', 'Los encabezados del archivo no coinciden con el formato esperado.');
            session()->flash('message_type', 'error');
            return back();
        }

        $mesesMap = [
            "Enero" => 2, "Febrero" => 3, "Marzo" => 4, "Abril" => 5,
            "Mayo" => 6, "Junio" => 7, "Julio" => 8, "Agosto" => 9,
            "Septiembre" => 10, "Octubre" => 11, "Noviembre" => 12, "Diciembre" => 13
        ];

        $cuentasFaltantesPlanCuentas = [];

        DB::beginTransaction();

        try {

            foreach ($xlsx->rows() as $i => $fila) {

                if ($i == 0) continue;

                $codigo = trim($fila[0]);
                $descripcion = trim($fila[1]);

                $existeCuenta = Cuenta::where("Codigo_cuenta", $codigo)->first();

                if (!$existeCuenta) {

                    if (!in_array($codigo, array_column($cuentasFaltantesPlanCuentas, 'Codigo_cuenta'))) {
                        $cuentasFaltantesPlanCuentas[] = [
                            "Codigo_cuenta" => $codigo,
                            "Descripcion_cuenta" => $descripcion
                        ];
                    }

                    continue;
                }

                foreach ($mesesMap as $nombreMes => $colIndex) {

                    $valor = $fila[$colIndex] ?? 0;
                    $valor = str_replace(['.', ','], ['', '.'], $valor);

                    if ($valor === '' || !is_numeric($valor)) {
                        $valor = 0;
                    }

                    Auxiliar::create([
                        'codigo_cuenta'      => $codigo,
                        'descripcion_cuenta' => $descripcion,
                        'mes'                => $nombreMes, 
                        'total'              => $valor,
                        'anio'               => $anio,
                    ]);
                }
            }

            if (!empty($cuentasFaltantesPlanCuentas)) {

                DB::rollBack();

                $txtFileName = 'CuentasFaltantesEnPlanCuentas.txt';
                $dir = public_path('/CuentasFaltantes');

                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }

                $txtFilePath = $dir . '/' . $txtFileName;
                $archivo = fopen($txtFilePath, 'w');

                fwrite($archivo, "Cuentas Faltantes en el Plan de Cuentas\n");
                fwrite($archivo, str_repeat("-", 90) . "\n\n");

                foreach ($cuentasFaltantesPlanCuentas as $cuenta) {
                    fwrite($archivo, "Código de cuenta: " . $cuenta["Codigo_cuenta"] . "\n");
                    fwrite($archivo, "Descripción: " . $cuenta["Descripcion_cuenta"] . "\n");
                    fwrite($archivo, str_repeat("-", 90) . "\n");
                }

                fclose($archivo);

                session()->flash('message', 'Se detectaron cuentas que no existen en el plan de cuentas.');
                session()->flash('message_type', 'error');
                session()->flash('download', '1');
                session()->flash('path', '/CuentasFaltantes/' . $txtFileName);
                session()->flash('nombreArchivo', $txtFileName);

                return back();
            }

            DB::commit();
            session()->flash('message', 'Auxiliares importados correctamente');
            session()->flash('message_type', 'success');
            return back();

        } catch (\Exception $e) {

            DB::rollBack();
            Log::error($e->getMessage());

            session()->flash('message', 'Error al importar: ' . $e->getMessage());
            session()->flash('message_type', 'error');
            return back();
        }
    }

    public function plantillaAuxiliares()
    {
        $rutaArchivo = public_path('plantillas/Plantilla de auxiliares' . '.xlsx');
        if (file_exists($rutaArchivo)) {
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('plantillaAuxiliares', 'descargó la plantilla de auxiliares', request());
            return response()->download($rutaArchivo, 'Plantilla de auxiliares.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } else {
            abort(404);
        }
    }
}
