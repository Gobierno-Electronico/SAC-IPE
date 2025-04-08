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
use Illuminate\Support\Facades\Auth;

class ContabilidadController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
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

    public function cargarPolizaInicial(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'input-archivo' => 'required',
            'input-archivo.*' => 'mimes:xlsx'
        ]);
        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()->messages()));
            session()->flash('message', 'El archivo seleccionado no es un Excel o no se pudo procesar');
            session()->flash('message_type', 'error');
            return back();
        }
        $archivo = $request->file('input-archivo');

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
                    ->whereYear('fecha', '=', Carbon::now()->year)
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
                    $poliza = Poliza::whereYear('fecha', '=', Carbon::now()->year)->orderBy('evento', 'DESC')->first();
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

                    $buscarPolizaInicial = Poliza::where('cuenta', '=', $row['Cuenta'])->whereYear('fecha', '=', Carbon::now()->year)->where('categoria', '=', 'SALDO INICIAL')->first();
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
        $anioActual = Carbon::now()->year;
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
            'tipo_interaccion' => $row['Cargo'] != '' ? 'Cargo' : 'Abono',
            'validado' => false,
            'categoria' => 'SALDO INICIAL',
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
}
