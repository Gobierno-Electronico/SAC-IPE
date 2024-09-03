<?php

namespace App\Http\Controllers;

use App\Enums\TipoInteraccionEnum;
use App\Models\ClasificadorFuenteFinanciamiento;
use App\Models\ClasificadorRubroIngreso;
use App\Models\CuentaCapitulo;
use App\Models\InteraccionCuentaConcepto;
use App\Models\InteraccionCuentaCuenta;
use App\Models\Poliza;
use App\Models\PresupuestoInicial;
use App\Http\Controllers\BitacoraController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Shuchkin\SimpleXLSX;
use DB;
use Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Cuenta;
use App\Models\CuentaClasificadorEgreso;

class PresupuestoController extends Controller
{

    private $cuentaActual = null;
    private $interaccionCuentaConceptoIzquierdaActual = null;
    private $interaccionCuentaCuentaActual = null;

    private $interaccionCuentaConceptoDerechaActual = null;

    private $cuentaDerechaActual = null;

    public function __construct()
    {
        $this->middleware('auth');
    }


    //INGRESOS
    public function presupuestoInicialIngresos()
    {
        return view('presupuestos.presupuesto-ingresos-inicial');
    }

    public function presupuestoInicialEgresos()
    {
        return view('presupuestos.presupuesto-egresos-inicial');
    }

    public function consultaPresupuestoIngresos(Request $request)
    {
        return view('presupuestos.presupuesto-ingresos-consulta');
    }

    public function consultaPresupuestoEgresos(Request $request)
    {
        return view('presupuestos.presupuesto-egresos-consulta');
    }

    public function ampliacionIngresos(Request $request)
    {
        return view('presupuestos.ampliaciones_reducciones.ampliacion-ingresos', ['tipo' => 'Ampliación']);
    }

    public function ampliacionEgresos(Request $request)
    {
        return view('presupuestos.ampliaciones_reducciones.ampliacion-egresos', ['tipo' => 'Ampliación']);
    }

    public function reduccionIngresos(Request $request)
    {
        return view('presupuestos.ampliaciones_reducciones.reduccion-ingresos', ['tipo' => 'Reducción']);
    }

    public function reduccionEgresos(Request $request)
    {
        return view('presupuestos.ampliaciones_reducciones.reduccion-egresos', ['tipo' => 'Reducción']);
    }

    public function tiposPresupuesto()
    {
        return view('presupuestos.tipos_presupuesto.tipos-presupuesto');
    }

    public function movimientos()
    {
        return view('movimientos.movimientos');
    }

    public function consultaAmpliacionesReducciones(){
        return view('presupuestos.ampliaciones_reducciones.consulta-ampliaciones-reducciones');
    }

    public function verDetalleAfectacion($evento){
        return view('presupuestos.ampliaciones_reducciones.ver-detalle-afectacion', ['evento' => $evento]);
    }

    public function recalendarizacion(){
        return view('presupuestos.recalendarizacion.recalendarizacion');
    }

    public function cargarPresupuestoInicialIngresos(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'input-archivo' => 'required',
            'input-archivo.*' => 'mimes:xlsx'
        ]);
        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()->messages()));
            session()->flash('message', implode(" ", $errors));
            session()->flash('message_type', 'error');
            return back();
        }
        $archivo = $request->file('input-archivo');
        // Poliza::truncate();
        // PresupuestoInicial::truncate();
        // return response()->json('Método desactivado');
        // Validar que el archivo pueda ser analizado correctamente.
        if ($xlsx = SimpleXLSX::parse($archivo)) {
            // Validar que los encabezados coincidan con los campos esperados.
            $expectedHeaders = ['Area Recaudadora', 'TIPO', 'CFF', 'CRI', 'Cuenta', 'Descripción', 'CONCEPTO', 'TOTAL', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
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
                }
                // Se inicia una transacción de base de datos para que todas las operaciones de base de datos dentro del bloque se puedan revertir si ocurre algún error.
                $usuariosController = new BitacoraController();
                $usuariosController->bitacora('cargarPresupuestoInicialIngresos', 'cargó o intentó cargar el presupuesto inicial de ingresos', $request);
                DB::beginTransaction();
                $total = 0;
                $totalPresupuestos = count($rows);
                $numeroRegistros--; //Se quita uno, ya que hay una fila que es el total
                $cuentasFaltantes = [];
                $presupuestosRepetidos = [];
                $cuentasEnLaGuiaFaltantes = [];
                // $poliza = Poliza::whereYear('fecha', '=', Carbon::now()->year)->orderBy('numero_poliza','DESC')->first();
                // $numeroPoliza = $poliza ? $poliza->numero_poliza + 1 : 1;
                $numerosPolizas = Poliza::select('numero_poliza')
                    ->where('tipo_poliza', '=', 'P')
                    ->whereYear('fecha', '=', Carbon::now()->year)
                    ->distinct()
                    ->orderBy('numero_poliza')
                    ->pluck('numero_poliza')
                    ->toArray();
                $numerosEvento = Poliza::select('evento')
                    ->distinct()
                    ->whereYear('fecha', '=', Carbon::now()->year)
                    ->orderBy('evento')
                    ->pluck('evento')
                    ->toArray();
                $ultimoNumero = end($numerosPolizas); // Obtiene el último número del arreglo
                $ultimoEvento = end($numerosEvento);
                $numeroFaltante = [];
                $eventoFaltante = [];
                for ($i = 1; $i <= $ultimoNumero; $i++) {
                    if (!in_array($i, $numerosPolizas)) {
                        $numeroFaltante[] = $i;
                    }
                }
                for ($i = 1; $i <= $ultimoEvento; $i++) {
                    if (!in_array($i, $numerosEvento)) {
                        $eventoFaltante[] = $i;
                    }
                }
                if (empty($numeroFaltante)) {
                    $poliza = Poliza::whereYear('fecha', '=', Carbon::now()->year)->where('tipo_poliza', '=', 'P')->orderBy('numero_poliza', 'DESC')->first();
                    $numeroPoliza = $poliza ? $poliza->numero_poliza + 1 : 1;
                } else {
                    $numeroPoliza = $numeroFaltante[0];
                }

                if (empty($eventoFaltante)) {
                    $poliza = Poliza::whereYear('fecha', '=', Carbon::now()->year)->orderBy('evento', 'DESC')->first();
                    $numeroEvento = $poliza ? $poliza->evento + 1 : 1;
                } else {
                    $numeroEvento = $eventoFaltante[0];
                }
                // Se procesan los datos de cada fila del archivo Excel y se crea un nuevo registro en la base de datos utilizando el modelo Cuenta.
                foreach ($rows as $row) {
                    $total++;
                    if ($total == $totalPresupuestos) {
                        continue;
                    }
                    $cuenta = Cuenta::where("Codigo_cuenta", $row["Cuenta"])->first();

                    if (!$cuenta) {
                        if (!$cuenta && !in_array($row["Cuenta"], $cuentasFaltantes)) {
                            $cuentasFaltantes[] = $row["Cuenta"];
                        }
                    }
                    $cri = ClasificadorRubroIngreso::where('Codificacion_rubro_ingreso', '=', $row["CRI"])->where('Nombre' , '=' , $row["Descripción"])->first();
                    if (!$cri) {
                        ClasificadorRubroIngreso::create([
                            'Codificacion_rubro_ingreso' => $row["CRI"],
                            'Nombre' => $row["Descripción"],
                            'Cuenta_contable' => $row["Cuenta"],
                            'Cuenta_registro' => $cuenta->Cuenta_registro
                        ]);
                    }
                    $cff = ClasificadorFuenteFinanciamiento::where('Codificacion_fuente_financiamiento', '=', $row["CFF"])->where('Nombre' , '=' , $row["Descripción"])->first();
                    if (!$cff) {
                        ClasificadorFuenteFinanciamiento::create([
                            'Codificacion_fuente_financiamiento' => $row["CFF"],
                            'Nombre' => $row["Descripción"],
                            'Cuenta_contable' => $row["Cuenta"],
                            'Cuenta_registro' => $cuenta->Cuenta_registro
                        ]);
                    }

                    $buscarPresupuesto = Poliza::where('cuenta', '=', $row['Cuenta'])->whereYear('fecha', '=', Carbon::now()->year)->where('categoria', '=', 'INICIAL INGRESOS')->first();
                    if ($buscarPresupuesto) {
                        if ($buscarPresupuesto->validado) {

                            DB::rollBack();
                            session()->flash('message', 'El presupuesto inicial ya se encuentra validado.');
                            session()->flash('message_type', 'error');
                            return back();
                        } else {
                            DB::rollBack();
                            session()->flash('message', 'Ya existe un presupuesto cargado no validado. Si quiere realizar cambios, primero elimine el presupuesto desde la consulta de presupuesto de egresos.');
                            session()->flash('message_time', '0');
                            session()->flash('message_type', 'error');
                            return back();
                        }
                    }
                    $creacionExitosaPoliza = $this->generarPolizasPresupuestoInicialIngresos($row, $cuentasEnLaGuiaFaltantes, $numeroPoliza, $numeroEvento);
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
                if (empty($cuentasEnLaGuiaFaltantes)) {
                    DB::commit();
                    session()->flash('message', 'Importación exitosa del presupuesto inicial');
                    session()->flash('message_type', 'success');
                    return back();
                    // return response()->json(['mensaje' => 'Importación exitosa con un total de ' . $total . ' presupuestos.', 'error' => '', 'Cuentas faltantes' => $cuentasFaltantes, 'Presupuestos repetidos' => $presupuestosRepetidos, 'Guia contabilizadora faltante' => $cuentasEnLaGuiaFaltantes]);
                } else {
                    DB::rollBack();
                    session()->flash('message', 'No se creó el presupuesto inicial. Se encontraron cuentas no relacionadas en la guía contabilizadora. Revisé el archivo que se descargará.');
                    session()->flash('message_type', 'error');

                    $txtFileName = 'CuentasFaltantes.txt';
                    $txtFilePath = public_path('/CuentasFaltantes/' . $txtFileName);
                    $archivo = fopen($txtFilePath, 'w');

                    fwrite($archivo, 'Cuentas Faltantes' . PHP_EOL . '---------------------------------------------------------------------------------------' . PHP_EOL);

                    foreach ($cuentasEnLaGuiaFaltantes as $cuenta) {
                        $linea = 'Código de cuenta: ' . $cuenta->Codigo_cuenta . PHP_EOL . 'Descripción: ' . $cuenta->Descripcion_cuenta;
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


    private function generarPolizasPresupuestoInicialIngresos($presupuesto, &$cuentasEnLaGuiaFaltantes, $numeroPoliza, $numeroEvento)
    {
        // dd($presupuesto);
        $cuenta = Cuenta::where('Codigo_cuenta', '=', $presupuesto['Cuenta'])->first();
        $interaccionCuentaConceptoIzquierda = InteraccionCuentaConcepto::where('cuenta_id', '=', $cuenta->id)->first();
        if (!$interaccionCuentaConceptoIzquierda) {
            $cuentasEnLaGuiaFaltantes[] = $cuenta;
            return false;
        }
        $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoIzquierda->id)->first();
        if (!$interaccionCuentaCuenta) {
            $cuentasEnLaGuiaFaltantes[] = $cuenta;
            return false;;
        }
        $interaccionCuentaConceptoDerecha = InteraccionCuentaConcepto::where('id', '=', $interaccionCuentaCuenta->id_interaccion_concepto_cuenta_2)->first();
        if (!$interaccionCuentaConceptoIzquierda) {
            $cuentasEnLaGuiaFaltantes[] = $cuenta;
            return false;
        }
        $cuentaDerecha = Cuenta::find($interaccionCuentaConceptoDerecha->cuenta_id);
        if (!$cuentaDerecha) {
            $cuentasEnLaGuiaFaltantes[] = $cuentaDerecha;
            return false;
        }
        // dd($presupuesto, $cuenta, $interaccionCuentaConceptoIzquierda, $interaccionCuentaCuenta, $interaccionCuentaConceptoDeracha, $cuentaDerecha);
        $meses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        $anioActual = Carbon::now()->year;
        $fecha = Carbon::now('America/Mexico_City');
        $fecha->year($anioActual);
        $polizaEstimado = [];
        $polizaPorEjecutar = [];
        foreach ($meses as $mes) {
            $polizaEstimado[] = [
                'area' => $presupuesto['Area Recaudadora'],
                'tipo_poliza' => $presupuesto['TIPO'],
                'numero_poliza' => $numeroPoliza,
                'fecha' => $fecha,
                'cuenta' => $presupuesto['Cuenta'],
                'concepto' => $presupuesto['Descripción'],
                'total' => $presupuesto[$mes],
                'mes' => $mes,
                'descripcion' => $presupuesto['CONCEPTO'],
                'evento' => $numeroEvento,
                'tipo_interaccion' => TipoInteraccionEnum::PRESUPUESTAL_CARGO,
                'validado' => false,
                'categoria' => 'INICIAL INGRESOS',
                'created_at' => $fecha,
                'updated_at' => $fecha
            ];
            $cri = ClasificadorRubroIngreso::where('Codificacion_rubro_ingreso', '=', $presupuesto["CRI"])->where('Nombre' , '=' , $cuentaDerecha->Descripcion_cuenta)->first();
            $cff = ClasificadorFuenteFinanciamiento::where('Codificacion_fuente_financiamiento', '=', $presupuesto["CFF"])->where('Nombre' , '=' , $cuentaDerecha->Descripcion_cuenta)->first();

            if (!$cri) {
                ClasificadorRubroIngreso::create([
                    'Codificacion_rubro_ingreso' => $presupuesto["CRI"],
                    'Nombre' => $cuentaDerecha->Descripcion_cuenta,
                    'Cuenta_contable' => $cuentaDerecha->Codigo_cuenta,
                    'Cuenta_registro' => $cuentaDerecha->Cuenta_registro
                ]);
            }

            if (!$cff) {
                ClasificadorFuenteFinanciamiento::create([
                    'Codificacion_fuente_financiamiento' => $presupuesto["CFF"],
                    'Nombre' => $cuentaDerecha->Descripcion_cuenta,
                    'Cuenta_contable' => $cuentaDerecha->Codigo_cuenta,
                    'Cuenta_registro' => $cuentaDerecha->Cuenta_registro
                ]);
            }

            $polizaPorEjecutar[] = [
                'area' => $presupuesto['Area Recaudadora'],
                'tipo_poliza' => $presupuesto['TIPO'],
                'numero_poliza' => $numeroPoliza,
                'fecha' => $fecha,
                'cuenta' => $cuentaDerecha->Codigo_cuenta,
                'concepto' => $cuentaDerecha->Descripcion_cuenta,
                'total' => $presupuesto[$mes],
                'mes' => $mes,
                'descripcion' => $presupuesto['CONCEPTO'],
                'evento' => $numeroEvento,
                'tipo_interaccion' => TipoInteraccionEnum::PRESUPUESTAL_ABONO,
                'validado' => false,
                'categoria' => 'INICIAL INGRESOS',
                'created_at' => $fecha,
                'updated_at' => $fecha
            ];
        }
        if (!empty($polizaEstimado)) {
            Poliza::insert($polizaEstimado);
        }

        if (!empty($polizaPorEjecutar)) {
            Poliza::insert($polizaPorEjecutar);
        }
        return true;
    }

    public function plantillaPresupuestoInicial()
    {
        $validator = Validator::make(request()->all(), [
            'type' => ['required', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            abort(404);
        }
        $formFields = $validator->getData();
        $rutaArchivo = public_path('PresupuestoInicial/Formato presupuesto incial ' . $formFields['type'] . '.xlsx');
        // dd($rutaArchivo);
        // Verificar si el archivo existe
        if (file_exists($rutaArchivo)) {
            // Descargar el archivo Excel
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('plantillaPresupuestoInicial', 'descargó la plantilla del presupuesto inicial', request());
            return response()->download($rutaArchivo, 'Formato presupuesto incial ingresos.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } else {
            abort(404);
        }
    }



    // public function borrarPresupuestoInicial()
    // {
    //     try {
    //         DB::table('polizas')->truncate();
    //         return response()->json(['success' => 'El Presupuesto Inicial ha sido borrado con éxito.']);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Hubo un error al borrar el Presupuesto Inicial: ' . $e->getMessage()], 500);
    //     }
    // }

    //EGRESOS

    public function cargarPresupuestoInicialEgresos(Request $request)
    {
        set_time_limit(0);
        $validator = Validator::make(request()->all(), [
            'input-archivo' => 'required',
            'input-archivo.*' => 'mimes:xlsx',
            'capitulo' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()->messages()));
            session()->flash('message', implode(" ", $errors));
            session()->flash('message_type', 'error');
            return back();
        }
        $capitulo = $request->get('capitulo');
        // switch ($capitulo) {
        //     case '2000':
        //         $numPoliza = 2;
        //         break;
        //     case '3000':
        //         $numPoliza = 3;
        //         break;
        //     default:
        //         session()->flash('message', 'El capítulo seleccionado no se encuentra programado');
        //         session()->flash('message_type', 'error');
        //         return back();
        // }
        $archivo = $request->file('input-archivo');
        // $archivo = public_path('PresupuestoInicial/presupuesto egresos 2024.xlsx');
        // Poliza::truncate();
        // PresupuestoInicial::truncate();
        // dd(1);
        // return response()->json('Método desactivado');
        // Validar que el archivo pueda ser analizado correctamente.
        if ($xlsx = SimpleXLSX::parse($archivo)) {
            // Validar que los encabezados coincidan con los campos esperados.
            $expectedHeaders = ['Area Ejecutora', 'TIPO', 'COG', 'CA', 'CFG', 'CP', 'CTG', 'Cuenta', 'Descripción', 'CONCEPTO', 'TOTAL', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
            $actualHeaders = $xlsx->rows()[0];
            $actualHeaders = array_map('trim', array_filter($actualHeaders));
            if (count($expectedHeaders) !== count($actualHeaders) || array_diff($expectedHeaders, $actualHeaders) || count($xlsx->sheetNames()) > 1) {
                session()->flash('message', 'Los campos del archivo no coinciden con los campos esperados o no se cumple con el formato.');
                session()->flash('message_type', 'error');
                return back();
            }
            $encabezados = $rows = [];
            $errores = [];
            $numeroRegistros = 0;
            try {
                $reemplazarCaracterEspecial = function ($texto) {
                    return str_replace("\xc2\xa0", '', $texto);
                };
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
                }
                // Se inicia una transacción de base de datos para que todas las operaciones de base de datos dentro del bloque se puedan revertir si ocurre algún error.
                $usuariosController = new BitacoraController();
                $usuariosController->bitacora('cargarPresupuestoInicialEgresos', 'cargó o intentó cargar el presupuesto inicial de egresos', $request);
                DB::beginTransaction();
                $numerosPolizas = Poliza::select('numero_poliza')
                    ->where('tipo_poliza', '=', 'P')
                    ->whereYear('fecha', '=', Carbon::now()->year)
                    ->distinct()
                    ->orderBy('numero_poliza')
                    ->pluck('numero_poliza')
                    ->toArray();

                $numerosEvento = Poliza::select('evento')
                    ->whereYear('fecha', '=', Carbon::now()->year)
                    ->distinct()
                    ->orderBy('evento')
                    ->pluck('evento')
                    ->toArray();
                $ultimoNumero = end($numerosPolizas); // Obtiene el último número del arreglo
                $ultimoEvento = end($numerosEvento);

                $numeroFaltante = [];
                $eventoFaltante = [];

                for ($i = 1; $i <= $ultimoNumero; $i++) {
                    if (!in_array($i, $numerosPolizas)) {
                        $numeroFaltante = $i;
                        break;
                    }
                }

                for ($i = 1; $i <= $ultimoEvento; $i++) {
                    if (!in_array($i, $numerosEvento)) {
                        $eventoFaltante[] = $i;
                    }
                }
                if (empty($numeroFaltante)) {
                    $poliza = Poliza::whereYear('fecha', '=', Carbon::now()->year)->where('tipo_poliza', '=', 'P')->orderBy('numero_poliza', 'DESC')->first();
                    $numeroPoliza = $poliza ? $poliza->numero_poliza + 1 : 1;
                } else {
                    $numeroPoliza = $numeroFaltante;
                }

                if (empty($eventoFaltante)) {
                    $poliza = Poliza::whereYear('fecha', '=', Carbon::now()->year)->orderBy('evento', 'DESC')->first();
                    $numeroEvento = $poliza ? $poliza->evento + 1 : 1;
                } else {
                    $numeroEvento = $eventoFaltante[0];
                }
                $cuentasFaltantes = [];
                $cuentasEnLaGuiaFaltantes = [];
                // Se procesan los datos de cada fila del archivo Excel y se crea un nuevo registro en la base de datos utilizando el modelo Cuenta.
                foreach ($rows as $row) { 
                    $cuenta = Cuenta::where("Codigo_cuenta", $row["Cuenta"])->first();

                    $cuentaCapitulo = CuentaCapitulo::where('cuenta_id', '=', $cuenta->id)->first();
                    if (!$cuentaCapitulo) {
                        $relacionesCuentaCapitulo[] = CuentaCapitulo::create([
                            'cuenta_id' => $cuenta->id,
                            'cuenta' => $cuenta->Codigo_cuenta,
                            'capitulo' => $capitulo
                        ]);                    
                    }

                    if (!$cuenta) {
                        if (!$cuenta && !in_array($row["Cuenta"], $cuentasFaltantes)) {

                            $cuentasFaltantes[] = $row["Cuenta"];
                        }
                    } else {
                        $cuentaCapitulo = CuentaCapitulo::where('cuenta_id', '=', $cuenta->id)->first();
                        if ($cuentaCapitulo && $cuentaCapitulo->capitulo != $capitulo) {
                            DB::rollBack();
                            session()->flash('message', 'Se detectó una cuenta que no pertenece al capítulo seleccionado.');
                            session()->flash('message_type', 'error');
                            return back();
                        }
                    }
                    $relacionCuentaClasificador = cuentaClasificadorEgreso::where('codigoCuenta', '=', $row["Cuenta"])->first();
                    if(!$relacionCuentaClasificador){
                        CuentaClasificadorEgreso::create([
                            'codigoCuenta' => $row["Cuenta"],
                            'CTG' => $row["CTG"],
                            'CP' => $row["CP"],
                            'COG' => $row["COG"],
                            'CFG' => $row["CFG"],
                            'CA' => $row["CA"]
                        ]);
                    }
                    // $cri = ClasificadorRubroIngreso::where('Codificacion_rubro_ingreso', '=', $row["CRI"])->where('Nombre' , '=' , $row["Descripción"])->first();
                    // if (!$cri) {
                    //     ClasificadorRubroIngreso::create([
                    //         'Codificacion_rubro_ingreso' => $row["CRI"],
                    //         'Nombre' => $row["Descripción"],
                    //         'Cuenta_contable' => $row["Cuenta"],
                    //         'Cuenta_registro' => $cuenta->Cuenta_registro
                    //     ]);
                    // }
                    // $cff = ClasificadorFuenteFinanciamiento::where('Codificacion_fuente_financiamiento', '=', $row["CFF"])->where('Nombre' , '=' , $row["Descripción"])->first();
                    // if (!$cff) {
                    //     ClasificadorFuenteFinanciamiento::create([
                    //         'Codificacion_fuente_financiamiento' => $row["CFF"],
                    //         'Nombre' => $row["Descripción"],
                    //         'Cuenta_contable' => $row["Cuenta"],
                    //         'Cuenta_registro' => $cuenta->Cuenta_registro
                    //     ]);
                    // }

                    $buscarPresupuesto = Poliza::where('cuenta', '=', $row['Cuenta'])->whereYear('fecha', '=', Carbon::now()->year)->where('area', '=', $row['Area Ejecutora'])->where('categoria', '=', 'INICIAL EGRESOS')->first();
                    if ($buscarPresupuesto) {
                        if ($buscarPresupuesto->validado) {

                            DB::rollBack();
                            session()->flash('message', 'El presupuesto inicial de este capítulo ya se encuentra validado.');
                            session()->flash('message_type', 'error');
                            return back();
                        } else {
                            DB::rollBack();
                            session()->flash('message', 'Ya existe un presupuesto cargado no validado de este capítulo. Si quiere realizar cambios, primero elimine el presupuesto del capítulo seleccionado desde la consulta de presupuesto de egresos.');
                            session()->flash('message_time', '0');
                            session()->flash('message_type', 'error');
                            return back();
                        }
                    }

                    $creacionExitosaPoliza = $this->generarPolizasPresupuestoInicialEgresos($row, $cuentasEnLaGuiaFaltantes, $numeroPoliza, $numeroEvento);
                    if (!$creacionExitosaPoliza) {
                        continue;
                    }
                    //     $presupuestoInicial[] = [
                    //         'area' => $row['Area Ejecutora'],
                    //         'tipo' => $row['TIPO'],
                    //         'anio' => Carbon::now()->year + 1,
                    //         'cuenta' => $row['Cuenta'],
                    //         'descripcion' => $row['Descripción'],
                    //         'concepto' => $row['CONCEPTO'],
                    //         'total' => $row['TOTAL'],
                    //         'monto_enero' => $row['ENERO'],
                    //         'monto_febrero' => $row['FEBRERO'],
                    //         'monto_marzo' => $row['MARZO'],
                    //         'monto_abril' => $row['ABRIL'],
                    //         'monto_mayo' => $row['MAYO'],
                    //         'monto_junio' => $row['JUNIO'],
                    //         'monto_julio' => $row['JULIO'],
                    //         'monto_agosto' => $row['AGOSTO'],
                    //         'monto_septiembre' => $row['SEPTIEMBRE'],
                    //         'monto_octubre' => $row['OCTUBRE'],
                    //         'monto_noviembre' => $row['NOVIEMBRE'],
                    //         'monto_diciembre' => $row['DICIEMBRE'],
                    //         'fecha' => Carbon::now('America/Mexico_City'),
                    //         'validado' => false,
                    //         'categoria' => 'EGRESOS'
                    //     ];
                }

                if (!empty($polizaEstimado)) {
                    Poliza::insert($polizaEstimado);
                }

                // Se verifica si hubo errores durante la importación. Si no hubo errores, se confirma la transacción y se redirige con un mensaje de éxito.
                // Si hubo errores, se revierte la transacción y se redirige con un mensaje de error que muestra los errores.
                if (empty($cuentasEnLaGuiaFaltantes)) {

                    DB::commit();
                    session()->flash('message', 'Importación exitosa del presupuesto inicial');
                    session()->flash('message_type', 'success');
                    return back();
                    // return response()->json(['mensaje' => 'Importación exitosa con un total de ' . $total . ' presupuestos.', 'error' => '', 'Cuentas faltantes' => $cuentasFaltantes, 'Presupuestos repetidos' => $presupuestosRepetidos, 'Guia contabilizadora faltante' => $cuentasEnLaGuiaFaltantes]);
                } else {
                    DB::rollBack();
                    session()->flash('message', 'No se creó el presupuesto inicial. Se encontraron cuentas no relacionadas en la guía contabilizadora. Revisé el archivo que se descargará.');
                    session()->flash('message_type', 'error');

                    $txtFileName = 'CuentasFaltantes.txt';
                    $txtFilePath = public_path('/CuentasFaltantes/' . $txtFileName);
                    $archivo = fopen($txtFilePath, 'w');

                    fwrite($archivo, 'Cuentas Faltantes' . PHP_EOL . '---------------------------------------------------------------------------------------' . PHP_EOL);

                    foreach ($cuentasEnLaGuiaFaltantes as $cuenta) {
                        $linea = 'Código de cuenta: ' . $cuenta->Codigo_cuenta . PHP_EOL . 'Descripción: ' . $cuenta->Descripcion_cuenta;
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
                Log::debug($error->getMessage() . $error->getLine());

                session()->flash('message', 'error' . $error->getMessage());
                session()->flash('message_type', 'error');
                return back();
            }

            // Si no se puede analizar el archivo Excel correctamente (por ejemplo, si el formato no es válido),
            // se redirige con un mensaje de error que incluye información sobre el error de análisis.
        } else {
            session()->flash('message', 'Error al analizar el archivo Excel: ' . SimpleXLSX::parseError());
            session()->flash('message_type', 'error');
            return back();
        }
    }

    private function generarPolizasPresupuestoInicialEgresos($presupuesto, &$cuentasEnLaGuiaFaltantes, $numPoliza, $numeroEvento)
    {
        if ($this->cuentaActual == null) {
            $cuenta = Cuenta::where('Codigo_cuenta', '=', $presupuesto['Cuenta'])->first();
            $this->cuentaActual = $cuenta;
            $interaccionCuentaConceptoIzquierda = InteraccionCuentaConcepto::where('cuenta_id', '=', $cuenta->id)->first();
            $this->interaccionCuentaConceptoIzquierdaActual = $interaccionCuentaConceptoIzquierda;

            if (!$interaccionCuentaConceptoIzquierda) {
                if (!in_array($cuenta, $cuentasEnLaGuiaFaltantes)) {
                    $cuentasEnLaGuiaFaltantes[] = $cuenta;
                }
                return false;
            }
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_2', '=', $interaccionCuentaConceptoIzquierda->id)->first();
            $this->interaccionCuentaCuentaActual = $interaccionCuentaCuenta;
            if (!$interaccionCuentaCuenta) {
                if (!in_array($cuenta, $cuentasEnLaGuiaFaltantes)) {
                    $cuentasEnLaGuiaFaltantes[] = $cuenta;
                }
                return false;
            }
            $interaccionCuentaConceptoDerecha = InteraccionCuentaConcepto::where('id', '=', $interaccionCuentaCuenta->id_interaccion_concepto_cuenta_1)->first();
            $this->interaccionCuentaConceptoDerechaActual = $interaccionCuentaConceptoDerecha;

            if (!$interaccionCuentaConceptoDerecha) {
                if (!in_array($cuenta, $cuentasEnLaGuiaFaltantes)) {
                    $cuentasEnLaGuiaFaltantes[] = $cuenta;
                }
                return false;
            }
            $cuentaDerecha = Cuenta::find($interaccionCuentaConceptoDerecha->cuenta_id);
            $this->cuentaDerechaActual = $cuentaDerecha;
            if (!$cuentaDerecha) {
                if (!in_array($cuentaDerecha, $cuentasEnLaGuiaFaltantes)) {
                    $cuentasEnLaGuiaFaltantes[] = $cuentaDerecha;
                }
                return false;
            }
        } else {
            if ($presupuesto['Cuenta'] == $this->cuentaActual->Codigo_cuenta) {
                // dd($presupuesto,$this->cuentaActual);
                $interaccionCuentaConceptoIzquierda = $this->interaccionCuentaConceptoIzquierdaActual;
                $interaccionCuentaCuenta = $this->interaccionCuentaCuentaActual;
                $interaccionCuentaConceptoDerecha = $this->interaccionCuentaConceptoDerechaActual;
                $cuentaDerecha = $this->cuentaDerechaActual;
            } else {
                $cuenta = Cuenta::where('Codigo_cuenta', '=', $presupuesto['Cuenta'])->first();
                $this->cuentaActual = $cuenta;
                $interaccionCuentaConceptoIzquierda = InteraccionCuentaConcepto::where('cuenta_id', '=', $cuenta->id)->first();
                $this->interaccionCuentaConceptoIzquierdaActual = $interaccionCuentaConceptoIzquierda;
                if (!$interaccionCuentaConceptoIzquierda) {
                    if (!in_array($cuenta, $cuentasEnLaGuiaFaltantes)) {
                        $cuentasEnLaGuiaFaltantes[] = $cuenta;
                    }
                    return false;
                }
                $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_2', '=', $interaccionCuentaConceptoIzquierda->id)->first();
                $this->interaccionCuentaCuentaActual = $interaccionCuentaCuenta;

                if (!$interaccionCuentaCuenta) {
                    if (!in_array($cuenta, $cuentasEnLaGuiaFaltantes)) {
                        $cuentasEnLaGuiaFaltantes[] = $cuenta;
                    }
                    return false;
                }
                $interaccionCuentaConceptoDerecha = InteraccionCuentaConcepto::where('id', '=', $interaccionCuentaCuenta->id_interaccion_concepto_cuenta_1)->first();
                $this->interaccionCuentaConceptoDerechaActual = $interaccionCuentaConceptoDerecha;
                if (!$interaccionCuentaConceptoDerecha) {
                    if (!in_array($cuenta, $cuentasEnLaGuiaFaltantes)) {
                        $cuentasEnLaGuiaFaltantes[] = $cuenta;
                    }
                    return false;
                }
                $cuentaDerecha = Cuenta::find($interaccionCuentaConceptoDerecha->cuenta_id);
                $this->cuentaDerechaActual = $cuentaDerecha;
                if (!$cuentaDerecha) {
                    if (!in_array($cuentaDerecha, $cuentasEnLaGuiaFaltantes)) {
                        $cuentasEnLaGuiaFaltantes[] = $cuentaDerecha;
                    }
                    return false;
                }
            }
        }
        // dd($presupuesto, $cuenta, $interaccionCuentaConceptoIzquierda, $interaccionCuentaCuenta, $interaccionCuentaConceptoDeracha, $cuentaDerecha);
        $meses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        $anioActual = Carbon::now()->year;
        $created = Carbon::now('America/Mexico_City');
        $fecha = Carbon::now('America/Mexico_City');
        $fecha->year($anioActual);
        $polizaEstimado = [];
        $polizaPorEjecutar = [];
        foreach ($meses as $mes) {

            $polizaEstimado[] = [
                'area' => $presupuesto['Area Ejecutora'],
                'tipo_poliza' => $presupuesto['TIPO'],
                'numero_poliza' => strval($numPoliza),
                'fecha' => $fecha,
                'cuenta' => $presupuesto['Cuenta'],
                'concepto' => $presupuesto['Descripción'],
                'total' => $presupuesto[$mes],
                'mes' => $mes,
                'descripcion' => $presupuesto['CONCEPTO'],
                'evento' => $numeroEvento,
                'tipo_interaccion' => 'Presupuestal - Abono',
                'validado' => false,
                'categoria' => 'INICIAL EGRESOS',
                'created_at' => $created,
                'updated_at' => $created
            ];

            $polizaPorEjecutar[] = [
                'area' => $presupuesto['Area Ejecutora'],
                'tipo_poliza' => $presupuesto['TIPO'],
                'numero_poliza' => strval($numPoliza),
                'fecha' => $fecha,
                'cuenta' => $cuentaDerecha->Codigo_cuenta,
                'concepto' => $cuentaDerecha->Descripcion_cuenta,
                'total' => $presupuesto[$mes],
                'mes' => $mes,
                'descripcion' => $presupuesto['CONCEPTO'],
                'evento' => $numeroEvento,
                'tipo_interaccion' => 'Presupuestal - Cargo',
                'validado' => false,
                'categoria' => 'INICIAL EGRESOS',
                'created_at' => $created,
                'updated_at' => $created
            ];
        }
        // if (!empty($polizaEstimado)) {
        //     Poliza::insert($polizaEstimado);
        // }

        // if (!empty($polizaPorEjecutar)) {
        //     Poliza::insert($polizaPorEjecutar);
        // }

        if (!empty($polizaEstimado)) {
            $columns = ['area', 'tipo_poliza', 'numero_poliza', 'fecha', 'cuenta', 'concepto', 'total', 'mes', 'descripcion', 'evento', 'tipo_interaccion', 'validado', 'categoria', 'created_at', 'updated_at'];
            $values = [];
            $bindings = [];

            foreach ($polizaEstimado as $row) {
                $valueString = '(' . implode(',', array_fill(0, count($row), '?')) . ')';
                $values[] = $valueString;
                $bindings = array_merge($bindings, array_values($row));
            }

            $query = 'INSERT INTO polizas (' . implode(',', $columns) . ') VALUES ' . implode(',', $values);
            DB::insert($query, $bindings);
        }

        if (!empty($polizaPorEjecutar)) {
            $columns = ['area', 'tipo_poliza', 'numero_poliza', 'fecha', 'cuenta', 'concepto', 'total', 'mes', 'descripcion', 'evento', 'tipo_interaccion', 'validado', 'categoria', 'created_at', 'updated_at'];
            $values = [];
            $bindings = [];

            foreach ($polizaPorEjecutar as $row) {
                $valueString = '(' . implode(',', array_fill(0, count($row), '?')) . ')';
                $values[] = $valueString;
                $bindings = array_merge($bindings, array_values($row));
            }

            $query = 'INSERT INTO polizas (' . implode(',', $columns) . ') VALUES ' . implode(',', $values);
            DB::insert($query, $bindings);
        }
        return true;
    }

}
