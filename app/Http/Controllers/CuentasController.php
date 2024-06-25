<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BitacoraController;
use App\Models\ClasificadorFuenteFinanciamiento;
use App\Models\ClasificadorRubroIngreso;
use App\Models\Cuenta;
use App\Models\CuentaClasificadorEgreso;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Shuchkin\SimpleXLSX;
use Log;

class CuentasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function listaDeCuentas()
    {
        return view('cuentas.lista');
    }
    public function editarCuenta($id)
    {
        $cuenta = Cuenta::find($id);
        if ($cuenta) {
            session()->flash('Codigo_cuenta', $cuenta);
            return view('cuentas.editar', compact('cuenta'));
        } else {
            session()->flash('message', 'Cuenta no encontrada');
            session()->flash('message_type', 'error');
            return redirect('/cuentas');
        }
    }

    public function cambiosCuenta(Request $request)
    {
        $requestData = $request->all();
        $id = $requestData['id'];
        unset($requestData['id']);

        // Realizar actualización
        try {
            // Validación para 'Descripcion_cuenta'
            if ("" == ($requestData['Descripcion_cuenta'])) {
                return response()->json(['error' => 'La descripción de la cuenta no puede quedar vacía.']);
            } elseif (strlen($requestData['Descripcion_cuenta']) > 255) {
                return response()->json(['error' => 'La descripción de la cuenta no debe superar los 255 caracteres.']);
            }

            // Validación para 'Clasificador_rubro_ingreso'
            if ("" == ($requestData['Clasificador_rubro_ingreso'])) {
                return response()->json(['error' => 'El Clasificador de Rubro de Ingreso no puede quedar vacío.']);
            } elseif (!$this->validarEntero($requestData, 'Clasificador_rubro_ingreso', 10)) {
                return response()->json(['error' => 'El Clasificador Rubro Ingreso debe ser un número entero y no superar los 10 dígitos.']);
            }

            // Validación para 'Clasificador_objeto_gasto'
            if ("" == ($requestData['Clasificador_objeto_gasto'])) {
                return response()->json(['error' => 'El Clasificador de Objeto de Gasto no puede quedar vacío.']);
            } elseif (!$this->validarEntero($requestData, 'Clasificador_objeto_gasto', 10)) {
                return response()->json(['error' => 'El Clasificador Objeto Gasto debe ser un número entero y no superar los 10 dígitos.']);
            }
            unset($requestData['Codigo_cuenta']);
            unset($requestData['Nivel']);
            Cuenta::find($id)->update($requestData);
            return response()->json(['mensaje' => 'Cuenta Actualizada con Éxito']);
        } catch (\Exception $error) {
            Log::debug($error->getMessage());
            return response()->json(['error' => 'Ocurrió un error al momento de Actualizar la Cuenta, intente más tarde']);
        } finally {
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('cambiosCuenta', 'modificó o intentó modificar la información de la cuenta ' . Cuenta::find($id)->Codigo_cuenta, $request);
        }
    }

    private function validarEntero($data, $key, $maxDigits)
    {
        if (!isset($data[$key]) || !is_numeric($data[$key]) || floor($data[$key]) != $data[$key] || strlen((string) $data[$key]) > $maxDigits) {
            return false;
        }
        return true;
    }

    // abre la vista "registrarCuenta" y llena las cuentas del primer nivel, ya que este es el que siempre se mostrará,
    //a excepción de que se quieran registrar cuentas de un solo nivel
    public function mostrarRegistrarCuenta()
    {
        $cuentasNivel1 = DB::table('dbo.cuentas')
            ->where('Nivel', '=', '1')
            ->get();
        return view('cuentas.registraCuenta', ['cuentasNivel1' => $cuentasNivel1]);
    }

    //obtiene la información de las cuentas registradas en la base de datos, con este método se llenan dinámicamente los select que se muestran en lka vista
    public function llenarSiguienteNivel(Request $request)
    {
        $cuentaPadre = $request->input('cuentaPadre');
        $nivel = $request->input('nivel');
        $cuentasSiguienteNivel = DB::table('dbo.cuentas')
            ->where('Nivel', '=', $nivel)
            ->where('Cuenta_padre_ID', 'LIKE', $cuentaPadre . '%')
            ->get();

        return response()->json([
            'mensaje' => $cuentasSiguienteNivel,
        ]);
    }

    public function agregarCuenta(Request $requestAgregarCuenta)
    {
        $usuariosController = new BitacoraController();

        $txtCodigo = $requestAgregarCuenta->input('txtCodigo');
        $codigoCuenta = $requestAgregarCuenta->input('codigoCuenta');
        $descripcionCuenta = $requestAgregarCuenta->input('descripcionCuenta');
        $cuentaRegistro = $requestAgregarCuenta->input('cuentaRegistro');
        $clasificadorIngreso = $requestAgregarCuenta->input('clasificadorIngreso');
        $clasificadorGasto = $requestAgregarCuenta->input('clasificadorGasto');
        $nivel = $requestAgregarCuenta->input('nivel');

        //con el código de cuenta que se construye en la vista previa del código de cuenta se obtiene la cuenta padre de la cuenta por registrar
        //con explode se divide el $codigoCuenta por puntos
        $codigoDividido = explode(".", $codigoCuenta);
        // con array_slice se elimina la última parte de $codigoDividido
        $codigoDividido = array_slice($codigoDividido, 0, -1);
        //con implode se vuelven a unir las partes mediante puntos
        $cuentaPadre = implode(".", $codigoDividido);

        $codigoCuentaExistente = $this->buscarCodigoDeCuenta($codigoCuenta);
        // se verifica que el código de la cuenta por registar no exista en la base de datos
        if (count($codigoCuentaExistente)) {
            //guardar bitácora
            $usuariosController->bitacora('agregarCuenta', 'agregó o intentó agregar una nueva cuenta con código: ' . $codigoCuenta, $requestAgregarCuenta);
            return response()->json(['error' => 'Cuenta ya existente']);
        } else {
            try {
                //registro de la cuenta haciendo uso del model Cuenta
                $cuenta = new Cuenta;
                $cuenta->Codigo_cuenta = $codigoCuenta;
                $cuenta->Descripcion_cuenta = $descripcionCuenta;
                $cuenta->Cuenta_registro = $cuentaRegistro;
                $cuenta->Clasificador_rubro_ingreso = empty($clasificadorIngreso) ? null : $clasificadorIngreso;
                $cuenta->Clasificador_objeto_gasto = empty($clasificadorGasto) ? null : $clasificadorGasto;
                $cuenta->Nivel = $nivel;
                $cuenta->Cuenta_padre_ID = empty($cuentaPadre) ? null : $cuentaPadre;
                $cuenta->Estado = 'True';
                $cuenta->save();
                return response()->json(['mensaje' => 'Cuenta agregada correctamente', 'error' => '']);
            } catch (\Throwable $th) {
                // Se maneja la excepción en caso de que exista, se manda al Log y se notifica al usuario
                Log::debug($th->getMessage());
                return response()->json(['error' => 'Ocurrió un error al momento de registrar la cuenta, intente más tarde']);
            } finally {
                //guardar bitácora
                $usuariosController->bitacora('agregarCuenta', 'agregó o intentó agregar una nueva cuenta con código: ' . $codigoCuenta, $requestAgregarCuenta);
            }
        }
    }
    //Busca un código de cuenta en base a uno que se ingrese en la vista para verificar si ya existe dicho código
    public function buscarCodigoDeCuenta($codigoCuenta)
    {
        try {
            $codigoExistente = DB::table('dbo.cuentas')
                ->where("Codigo_cuenta", "=", $codigoCuenta)
                ->get();
            return $codigoExistente;
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
            session()->flash('message', 'Ocurrió un error al obtener la información necesaria, intente más tarde');
            session()->flash('message_type', 'error');
            return redirect('/cuentas/mostrarRegistrarCuenta');
        }
    }

    public function cargaExcel()
    {
        return view('cuentas.cargaExcel');
    }

    // Esta función devuelve la vista llamada 'cargaExcel'
    public function importarExcel(Request $request)
    {
        set_time_limit(0);
        $request->validate([
            'file' => 'required|mimes:xls,xlsx',
        ]);

        // guardar registro en bitácora
        $usuariosController = new BitacoraController();
        $usuariosController->bitacora('importarExcel', 'importó o intentó importar un archivo excel para cargar un conjunto de cuentas', $request);

        // Validar que el archivo pueda ser analizado correctamente.
        if ($xlsx = SimpleXLSX::parse($request->file('file')->getPathname())) {
            // Validar que los encabezados coincidan con los campos esperados.
            $expectedHeaders = ['Cuenta', 'Descripcion', 'Cta. de registro', 'Naturaleza', 'CRI', 'CFF', 'COG', 'CA', 'CFG', 'CP', 'CTG'];
            $actualHeaders = $xlsx->rows()[0];
            $actualHeaders = array_map('trim', $actualHeaders);
            if (count($expectedHeaders) !== count($actualHeaders) || array_diff($expectedHeaders, $actualHeaders)) {
                $diferencias = '';
                foreach (array_diff($expectedHeaders, $actualHeaders) as $columna) {
                    $diferencias .= $columna . ', ';
                }
                return response()->json(['error' => 'Los campos del archivo no coinciden con los campos esperados: ' . $diferencias]);
            }
            $encabezados = $rows = [];
            $cuentasRepetidas = [];
            try {
                // Se obtienen todos los códigos de cuenta existentes en la base de datos utilizando el modelo Cuenta.
                $cuentasExistentes = Cuenta::pluck('Codigo_cuenta')->all();
                // Se recorren las filas del archivo Excel. La primera fila se considera como encabezados y se almacena en la variable $encabezados.
                // Luego, se combinan los encabezados con los datos de cada fila y se almacenan en $rows.
                foreach ($xlsx->rows() as $numero_fila => $datos_fila) {
                    if ($numero_fila === 0) {
                        $encabezados = array_map('trim', $datos_fila);
                        continue;
                    }
                    $rows[] = array_combine($encabezados, $datos_fila);
                }
                // Se inicia una transacción de base de datos para que todas las operaciones de base de datos dentro del bloque se puedan revertir si ocurre algún error.
                DB::beginTransaction();
                // Se procesan los datos de cada fila del archivo Excel y se crea un nuevo registro en la base de datos utilizando el modelo Cuenta.
                foreach ($rows as $row) {
                    if (in_array($row['Cuenta'], $cuentasExistentes)) {
                        $cuentaExistente = Cuenta::where('Codigo_cuenta', '=', $row['Cuenta'])->first();
                        if ($cuentaExistente->Descripcion_cuenta != $row['Descripcion']) {
                            $cuentaExistente->Descripcion_cuenta = $row['Descripcion'];
                            $cuentaExistente->save();
                            $cuentasRepetidas[] = "La descripción de la cuenta con código {$row['Cuenta']} fue actualizada!";
                            // return [$cuentaExistente, $row];
                        }

                        if ($cuentaExistente->Naturaleza != $row['Naturaleza']) {
                            $cuentaExistente->Naturaleza = $row['Naturaleza'];
                            $cuentaExistente->save();
                            $cuentasRepetidas[] = "La naturaleza de la cuenta con código {$row['Cuenta']} fue actualizada!";
                        }

                        if ($row['CRI'] != '') {
                            $CRI = ClasificadorRubroIngreso::where('Cuenta_contable', '=', $row['Cuenta'])->first();
                            if ($CRI) {
                                $CRI->Codificacion_rubro_ingreso = $row['CRI'];
                                $CRI->Nombre = $row['Descripcion'];
                                $CRI->save();
                            } else {
                                ClasificadorRubroIngreso::create([
                                    'Codificacion_rubro_ingreso' => $row['CRI'],
                                    'Nombre' => $row['Descripcion'],
                                    'Cuenta_contable' => $row['Cuenta'],
                                    'Cuenta_registro' => Str::upper($row['Cta. de registro']) == 'SÍ' || Str::upper($row['Cta. de registro']) == 'Sí' || Str::upper($row['Cta. de registro']) == 'Si' || Str::upper($row['Cta. de registro']) == 'SI',
                                ]);
                            }
                        }

                        if ($row['CFF'] != '') {
                            $CFF = ClasificadorFuenteFinanciamiento::where('Cuenta_contable', '=', $row['Cuenta'])->first();
                            if ($CFF) {
                                $CFF->Codificacion_fuente_financiamiento = $row['CFF'];
                                $CFF->Nombre = $row['Descripcion'];
                                $CFF->save();
                            } else {
                                ClasificadorFuenteFinanciamiento::create([
                                    'Codificacion_fuente_financiamiento' => $row['CFF'],
                                    'Nombre' => $row['Descripcion'],
                                    'Cuenta_contable' => $row['Cuenta'],
                                    'Cuenta_registro' => Str::upper($row['Cta. de registro']) == 'SÍ' || Str::upper($row['Cta. de registro']) == 'Sí' || Str::upper($row['Cta. de registro']) == 'Si' || Str::upper($row['Cta. de registro']) == 'SI',
                                ]);
                            }
                        }
                        if ($row['COG'] != '') {
                            $clasificadoresEgreso = CuentaClasificadorEgreso::where('codigoCuenta', '=', $row['Cuenta'])->first();
                            if ($clasificadoresEgreso) {
                                $clasificadoresEgreso->CTG = $row['CTG'];
                                $clasificadoresEgreso->CP = $row['CP'];
                                $clasificadoresEgreso->COG = $row['COG'];
                                $clasificadoresEgreso->CFG = $row['CFG'];
                                $clasificadoresEgreso->CA = $row['CA'];
                                $clasificadoresEgreso->save();
                            } else {
                                CuentaClasificadorEgreso::create([
                                    'codigoCuenta' => $row['Cuenta'],
                                    'CTG' => $row['CTG'],
                                    'CP' => $row['CP'],
                                    'COG' => $row['COG'],
                                    'CFG' => $row['CFG'],
                                    'CA' => $row['CA'],
                                ]);
                            }
                        }
                        if (($key = array_search($row['Cuenta'], $cuentasExistentes)) !== false) {
                            unset($cuentasExistentes[$key]);
                        }
                        continue;
                    }

                    //con explode se divide el $codigoCuenta por puntos
                    $codigoDividido = explode(".", $row['Cuenta']);
                    // con array_slice se elimina la última parte de $codigoDividido
                    $codigoDividido = array_slice($codigoDividido, 0, -1);
                    //con implode se vuelven a unir las partes mediante puntos
                    $cuentaPadre = implode(".", $codigoDividido);
                    //Tomando la ultima parte de $codigoDividido, se cuenta cada posicion después del punto para contemplar el Nivel de la cuenta
                    $nivel = count($codigoDividido);
                    //Se le suma para que el Nivel de la Cuenta empiece en "1"
                    $nivel = $nivel + 1;

                    Cuenta::create([
                        'Codigo_cuenta' => $row['Cuenta'],
                        'Descripcion_cuenta' => $row['Descripcion'],
                        'Nivel' => $row['Nivel'] = $nivel,
                        'Estado' => $row['Estado'] = 'True',
                        'Cuenta_registro' => Str::upper($row['Cta. de registro']) == 'SÍ' || Str::upper($row['Cta. de registro']) == 'Sí' || Str::upper($row['Cta. de registro']) == 'Si' || Str::upper($row['Cta. de registro']) == 'SI',
                        // 'Clasificador_rubro_ingreso' => empty($row['CRI']) ? null : $row['CRI'],
                        // 'Clasificador_objeto_gasto' => empty($row['COG']) ? null : $row['COG'],
                        'Cuenta_padre_ID' => empty($cuentaPadre) ? null : $cuentaPadre,
                    ]);
                }

                // codigo interno unicamente para borrar al inicio de la carga cualquier actualizacion en el plan de cuentas
                // if(!empty($cuentasExistentes)){
                //     foreach ($cuentasExistentes as $key => $value) {
                //         $cuentaBorrar = Cuenta::where('Codigo_cuenta', '=', $value)->first();
                //         CuentaCapitulo::where('Cuenta', '=', $value)->delete();
                //         InteraccionCuentaConcepto::where('cuenta_id', '=', $cuentaBorrar->id)->delete();
                //         MovimientoAnual::where('id_cuenta', '=', $cuentaBorrar->id)->delete();
                //         $cuentaBorrar->delete();
                //     }
                // }

                // Se verifica si hubo errores durante la importación. Si no hubo errores, se confirma la transacción y se redirige con un mensaje de éxito.
                // Si hubo errores, se revierte la transacción y se redirige con un mensaje de error que muestra los errores.
                if (empty($cuentasRepetidas)) {
                    DB::commit();
                    return response()->json(['mensaje' => 'Importación exitosa', 'error' => '']);
                } else {
                    DB::commit();
                    return response()->json(['mensaje' => $cuentasRepetidas, 'error' => '']);
                }
                // Si ocurre alguna excepción durante el proceso de importación, se revierte la transacción
                // y se redirige con un mensaje de error que incluye detalles sobre la excepción.
            } catch (\Exception $error) {
                DB::rollBack();
                Log::debug($error->getMessage());
                return response()->json(['error' => 'Ocurrió un error en la importación, intente más tarde']);
            }
            // Si no se puede analizar el archivo Excel correctamente (por ejemplo, si el formato no es válido),
            // se redirige con un mensaje de error que incluye información sobre el error de análisis.
        } else {
            return response()->json(['error' => 'Error al analizar el archivo Excel: ' . SimpleXLSX::parseError()]);
        }
    }

    public function limpiarCuentas()
    {
        try {
            $path = public_path('PlanCuentas/Plan-Cuentas-Identificador.xlsx');

            // Intentar parsear el archivo Excel
            $xlsx = SimpleXLSX::parse($path);
            if (!$xlsx) {
                session()->flash('message', 'No se pudo analizar el documento como archivo Excel válido.');
                session()->flash('message_type', 'error');
                return redirect('/home');
            }else{

                // Validar encabezados del archivo Excel
                $expectedHeaders = ['Cuenta', 'Descripcion', 'Cta. de registro', 'Naturaleza', 'Identificador'];
                $actualHeaders = $xlsx->rows()[0];
                $actualHeaders = array_map('trim', $actualHeaders);
                if (count($expectedHeaders) !== count($actualHeaders) || array_diff($expectedHeaders, $actualHeaders)) {
                    $diferencias = implode(', ', array_diff($expectedHeaders, $actualHeaders));
                    session()->flash('message', 'Los campos del archivo no coinciden con los campos esperados: ' . $diferencias);
                    session()->flash('message_type', 'error');
                    return redirect('/home');
                }
    
                // Guardar registro en bitácora
                $bitacoraController = new BitacoraController();
                $bitacoraController->bitacora('limpiarCuentas', 'depuró o intentó depurar el plan de cuentas ', Request());
    
                // Iniciar transacción
                DB::beginTransaction();
    
                // Leer los datos del archivo
                $rows = $xlsx->rows();
    
                // Eliminar la fila del encabezado
                array_shift($rows);
    
                // Obtener los registros de la base de datos
                $cuentas = Cuenta::all(); // Asumiendo que tienes un modelo Cuenta
    
                // Procesar cada cuenta de la base de datos
                foreach ($cuentas as $cuenta) {
                    $found = false;
                    foreach ($rows as $row) {
                        // Ajustar las columnas del Excel y convertir las respuestas
                        $cuentaExcel = $row[0];
                        $descripcionExcel = $row[1];
                        // $ctaRegistroExcel = (strtoupper($row[2]) == 'SI') ? 1 : 0;
                        // $naturalezaExcel = empty($row[3]) ? null : $row[3]; // Convertir vacío a null
                        $identificadorExcel = $row[4]; // Columna Identificador
    
                        // Comparar con la base de datos
                        if (
                            $cuenta->Codigo_cuenta == $cuentaExcel &&
                            $cuenta->Descripcion_cuenta == $descripcionExcel
                            // $cuenta->Cuenta_registro == $ctaRegistroExcel
                            // $cuenta->Naturaleza == $naturalezaExcel
                        ) {
                            // Si coincide, actualizar el identificador
                            $cuenta->identificador = $identificadorExcel;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        // Si no coincide, asignar 0
                        $cuenta->identificador = 0;
                    }
                    // Guardar los cambios en la base de datos
                    $cuenta->save();
                }
    
                // Commit de la transacción
                DB::commit();
    
                // Mensaje de éxito
                session()->flash('message', 'Plan de cuentas depurado correctamente');
                session()->flash('message_type', 'success');
                return redirect('/home');

            }

        } catch (\Exception $e) {
            // Rollback de la transacción en caso de error
            DB::rollback();

            // Registrar el error en el log
            Log::error('Error al depurar el plan de cuentas: ' . $e->getMessage());

            // Mensaje de error
            session()->flash('message', 'Ocurrió un error al depurar el plan de cuentas');
            session()->flash('message_type', 'error');
            return redirect('/home');
        }
        
    }


    public function plantillaExcel($archivo, Request $request)
    {
        // guardar registro en bitácora
        $usuariosController = new BitacoraController();
        $usuariosController->bitacora('plantillaExcel', 'descargó o intentó descargar la plantilla excel con la que se importan las cuentas', $request);

        $rutaArchivo = public_path('plantillas/' . $archivo);
        if (file_exists($rutaArchivo)) {
            return response()
                ->download($rutaArchivo, $archivo, [
                    'Content-Type' => 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename="' . $archivo . '"',
                    'Cache-Control' => 'must-revalidate',
                    'Pragma' => 'public',
                    'Expires' => '0',
                ]);
        } else {
            return response()->json(['error' => 'El archivo no existe']);
        }
    }

    //probablemente ya no sea útil
    public function relacionarCuentasCRI()
    {
        $res = ClasificadorRubroIngreso::all();
        $cuentasNoEncontradas = [];
        foreach ($res as $CRI) {
            $cuenta = Cuenta::where('Codigo_cuenta', '=', $CRI->Cuenta_contable)->first();
            if ($cuenta) {
                $cuenta->Clasificador_rubro_ingreso = $CRI->Codificacion_rubro_ingreso;
                $cuenta->save();
            } else {
                $cuentasNoEncontradas[] = $CRI;
            }
        }
        return $cuentasNoEncontradas;
    }

    //probablemente ya no sea útil
    public function relacionarCuentasCFF()
    {
        $res = ClasificadorFuenteFinanciamiento::all();
        $cuentasNoEncontradas = [];
        foreach ($res as $CFF) {
            $cuenta = Cuenta::where('Codigo_cuenta', '=', $CFF->Cuenta_contable)->first();
            if ($cuenta) {
                $cuenta->Clasificador_fuente_financiamiento = $CFF->Codificacion_fuente_financiamiento;
                $cuenta->save();
            } else {
                $cuentasNoEncontradas[] = $CFF;
            }
        }
        return $cuentasNoEncontradas;
    }
}
