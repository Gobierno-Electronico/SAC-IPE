<?php

namespace App\Http\Controllers;

use App\Models\ClasificadorDeConcepto;
use App\Models\Concepto;
use App\Models\Cuenta;
use App\Models\CuentaCapitulo;
use App\Models\InteraccionCuentaConcepto;
use App\Http\Controllers\BitacoraController;
use App\Models\InteraccionCuentaCuenta;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Shuchkin\SimpleXLSX;

class GuiaContabilizadoraController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function visualizarGuiaContabilizadora(Request $request)
    {
        return view("guia_contabilizadora.lista");

    }
    public function crearGuiaContabilizadora(Request $request)
    {
        // return response()->json('Método desactivado');
        $path = public_path('Guia/9.xlsx');

        // Validar que el archivo pueda ser analizado correctamente.
        if ($xlsx = SimpleXLSX::parse($path)) {
            // Validar que los encabezados coincidan con los campos esperados.
            $expectedHeaders = ['cuenta', 'clasificador', 'concepto', 'tipo'];
            $actualHeaders = $xlsx->rows()[0];
            $actualHeaders = array_map('trim', array_filter($actualHeaders));
            if (count($expectedHeaders) !== count($actualHeaders) || array_diff($expectedHeaders, $actualHeaders)) {
                return response()->json(['error' => 'Los campos del archivo no coinciden con los campos esperados.']);
            }
            $encabezados = $rows = [];
            $errores = [];
            try {
                $reemplazarCaracterEspecial = function ($texto) {
                    return str_replace("\xc2\xa0", '', $texto);
                };
                // Se obtienen todos los códigos de cuenta existentes en la base de datos utilizando el modelo Cuenta.
                // Se recorren las filas del archivo Excel. La primera fila se considera como encabezados y se almacena en la variable $encabezados.
                // Luego, se combinan los encabezados con los datos de cada fila y se almacenan en $rows.
                // dd($xlsx->rows());
                foreach ($xlsx->rows() as $numero_fila => $datos_fila) {
                    if ($numero_fila === 0) {
                        $encabezados = $datos_fila;
                        continue;
                    }
                    $rows[] = array_combine(array_map('trim', array_filter($encabezados)), array_map('trim', array_map($reemplazarCaracterEspecial, array_filter($datos_fila))));
                }
                // Se inicia una transacción de base de datos para que todas las operaciones de base de datos dentro del bloque se puedan revertir si ocurre algún error.
                $usuariosController = new BitacoraController();
                $usuariosController->bitacora('crearGuiaContabilizadora', 'creó o intentó crear la guía contabilizadora', $request);
                DB::beginTransaction();
                $total = 0;
                $cuentasFaltantes = [];
                $relacionesCuentaCapitulo = [];
                // Se procesan los datos de cada fila del archivo Excel y se crea un nuevo registro en la base de datos utilizando el modelo Cuenta.
                foreach ($rows as $row) {
                    $cuenta = Cuenta::where("Codigo_cuenta", $row["cuenta"])->first();
                    $concepto = Concepto::where("descripcion", $row["concepto"])->first();
                    $clasificador = ClasificadorDeConcepto::where("codigo_clasificador", $row["clasificador"])->first();
                    if (!$cuenta || !$concepto || !$clasificador) {
                        if (!$cuenta && !in_array($row["cuenta"], $cuentasFaltantes)) {
                            $cuentasFaltantes[] = $row["cuenta"];
                        }
                        dd($cuenta, $concepto, $row['concepto'] ,$clasificador);
                        continue;
                    }
                    $interaccion = InteraccionCuentaConcepto::where(['cuenta_id' => $cuenta->id, 'concepto_id' => $concepto->id, 'clasificador_de_concepto_id' => $clasificador->id, 'tipo_interaccion' => $row['tipo']])->first();
                    $cuentaCapitulo = CuentaCapitulo::where('cuenta_id', '=', $cuenta->id)->first();
                    if (!$cuentaCapitulo) {
                        $relacionesCuentaCapitulo[] = CuentaCapitulo::create([
                            'cuenta_id' => $cuenta->id,
                            'cuenta' => $cuenta->Codigo_cuenta,
                            'capitulo' => '4000'
                        ]);                    }
                 

                    if ($interaccion) {
                        continue;
                    } else {
                        // dd($cuenta, $concepto, $row['concepto'] ,$clasificador);

                    }
                    $total++;
                    $var = InteraccionCuentaConcepto::create([
                        'concepto_id' => $concepto->id,
                        'cuenta_id' => $cuenta->id,
                        'clasificador_de_concepto_id' => $clasificador->id,
                        'tipo_interaccion' => $row['tipo'],
                    ]);
                }
                // Se verifica si hubo errores durante la importación. Si no hubo errores, se confirma la transacción y se redirige con un mensaje de éxito.
                // Si hubo errores, se revierte la transacción y se redirige con un mensaje de error que muestra los errores.
                if (empty($errores)) {
                    DB::commit();
                    // return response()->json(['Cuentas faltantes' => $cuentasFaltantes]);

                    return response()->json(['mensaje' => 'Importación exitosa con un total de ' . $total . ' cuentas importadas', 'error' => '', 'todas' => $relacionesCuentaCapitulo]);
                } else {
                    DB::rollBack();
                    return response()->json(['error' => $errores]);
                }
                // Si ocurre alguna excepción durante el proceso de importación, se revierte la transacción
                // y se redirige con un mensaje de error que incluye detalles sobre la excepción.
            } catch (\Exception $error) {
                DB::rollBack();
                Log::debug($error->getMessage());
                return response()->json(['error' => $error->getMessage()]);
            }

            // Si no se puede analizar el archivo Excel correctamente (por ejemplo, si el formato no es válido),
            // se redirige con un mensaje de error que incluye información sobre el error de análisis.
        } else {
            return response()->json(['error' => 'Error al analizar el archivo Excel: ' . SimpleXLSX::parseError()]);
        }
    }
    // 2, 3, 4, 5, 6, 7, 9, 12, 13 xlsx solamente
    public function relacionarCuentasCuentas(Request $request)
    {
        // return response()->json('Método desactivado');
        $path = public_path('CuentasCuentas/14.xlsx');

        // Validar que el archivo pueda ser analizado correctamente.
        if ($xlsx = SimpleXLSX::parse($path)) {
            // Validar que los encabezados coincidan con los campos esperados.
            $expectedHeaders = ['cuenta', 'clasificador', 'concepto', 'tipo'];
            $actualHeaders = $xlsx->rows()[0];
            $actualHeaders = array_map('trim', array_filter($actualHeaders));
            if (count($expectedHeaders) !== count($actualHeaders) || array_diff($expectedHeaders, $actualHeaders)) {
                return response()->json(['error' => 'Los campos del archivo no coinciden con los campos esperados.']);
            }
            $encabezados = $rows = [];
            $errores = [];
            try {
                $reemplazarCaracterEspecial = function ($texto) {
                    return str_replace("\xc2\xa0", '', $texto);
                };
                // Se obtienen todos los códigos de cuenta existentes en la base de datos utilizando el modelo Cuenta.
                // Se recorren las filas del archivo Excel. La primera fila se considera como encabezados y se almacena en la variable $encabezados.
                // Luego, se combinan los encabezados con los datos de cada fila y se almacenan en $rows.
                // dd($xlsx->rows());
                foreach ($xlsx->rows() as $numero_fila => $datos_fila) {
                    if ($numero_fila === 0) {
                        $encabezados = $datos_fila;
                        continue;
                    }
                    $rows[] = array_combine(array_map('trim', array_filter($encabezados)), array_map('trim', array_map($reemplazarCaracterEspecial, array_filter($datos_fila))));
                }
                // dd($rows[0]['concepto'] == $rows[1]['concepto']);
                // Se inicia una transacción de base de datos para que todas las operaciones de base de datos dentro del bloque se puedan revertir si ocurre algún error.
                $usuariosController = new BitacoraController();
                $usuariosController->bitacora('relacionarCuentasCuentas', 'creó o intentó relacionar las cuentas', $request);
                DB::beginTransaction();
                $total = 0;
                $j = 0;
                $separacionConceptos = 0;
                $creadas = [];
                // Se procesan los datos de cada fila del archivo Excel y se crea un nuevo registro en la base de datos utilizando el modelo Cuenta.
                for ($i = 0; $i < count($rows); $i++) {
                    $j = $i;
                    $separacionConceptos = 1;
                    while ($j < count($rows) - 1 && $rows[$j]['concepto'] == $rows[$j + 1]['concepto']) {
                        $j++;
                        $separacionConceptos++;
                    }

                    $separacionConceptos /= 2;
                    $k = $i;

                    for ($l = 0; $l < ($separacionConceptos); $l++) {
                        $cuentaIzquierda = Cuenta::where("Codigo_cuenta", $rows[$k]["cuenta"])->first();
                        $conceptoIzquierda = Concepto::where("descripcion", $rows[$k]["concepto"])->first();
                        $clasificadorIzquierda = ClasificadorDeConcepto::where("codigo_clasificador", $rows[$k]["clasificador"])->first();
                        $cuentaDerecha = Cuenta::where("Codigo_cuenta", $rows[$k + ($separacionConceptos)]["cuenta"])->first();
                        $conceptoDerecha = Concepto::where("descripcion", $rows[$k + ($separacionConceptos)]["concepto"])->first();
                        $clasificadorDerecha = ClasificadorDeConcepto::where("codigo_clasificador", $rows[$k + ($separacionConceptos)]["clasificador"])->first();
                        if (!$cuentaIzquierda || !$conceptoIzquierda || !$clasificadorIzquierda || !$cuentaDerecha || !$conceptoDerecha || !$clasificadorDerecha) {
                            continue;
                        }
                        $interaccionIzquierda = InteraccionCuentaConcepto::where(['cuenta_id' => $cuentaIzquierda->id, 'concepto_id' => $conceptoIzquierda->id, 'clasificador_de_concepto_id' => $clasificadorIzquierda->id, 'tipo_interaccion' => $rows[$k]['tipo']])->first();
                        $interaccionDerecha = InteraccionCuentaConcepto::where(['cuenta_id' => $cuentaDerecha->id, 'concepto_id' => $conceptoDerecha->id, 'clasificador_de_concepto_id' => $clasificadorDerecha->id, 'tipo_interaccion' => $rows[$k + ($separacionConceptos)]['tipo']])->first();
                        // dd($cuentaDerecha,$conceptoDerecha, $clasificadorDerecha, $interaccionDerecha);
                        $interaccionExistente = InteraccionCuentaCuenta::where(['id_interaccion_concepto_cuenta_1' => $interaccionIzquierda->id, 'id_interaccion_concepto_cuenta_2' => $interaccionDerecha->id])->first();
                        // dd($interaccionIzquierda, $interaccionDerecha);
                        // dd($interaccionExistente);
                        if ($interaccionExistente) {
                            continue;
                        }


                        $res = InteraccionCuentaCuenta::create([
                            'id_interaccion_concepto_cuenta_1' => $interaccionIzquierda->id,
                            'id_interaccion_concepto_cuenta_2' => $interaccionDerecha->id
                        ]);
                        $total++;
                        $k++;
                        $creadas[] = $res;

                    }
                    $creadas[] = ['A' => $j];
                    $i = $j;

                }
                // Se verifica si hubo errores durante la importación. Si no hubo errores, se confirma la transacción y se redirige con un mensaje de éxito.
                // Si hubo errores, se revierte la transacción y se redirige con un mensaje de error que muestra los errores.
                if (empty($errores)) {
                    DB::commit();
                    // return response()->json(['Cuentas faltantes' => $cuentasFaltantes]);

                    return response()->json(['mensaje' => 'Importación exitosa con un total de ' . $total . ' cuentas importadas', 'error' => '']);
                } else {
                    DB::rollBack();
                    return response()->json(['error' => $errores]);
                }
                // Si ocurre alguna excepción durante el proceso de importación, se revierte la transacción
                // y se redirige con un mensaje de error que incluye detalles sobre la excepción.
            } catch (\Exception $error) {
                DB::rollBack();
                Log::debug($error->getMessage());
                // dd($creadas, $j);
                return response()->json(['error' => $error->getMessage(), 'creadas' => $creadas]);
            }

            // Si no se puede analizar el archivo Excel correctamente (por ejemplo, si el formato no es válido),
            // se redirige con un mensaje de error que incluye información sobre el error de análisis.
        } else {
            return response()->json(['error' => 'Error al analizar el archivo Excel: ' . SimpleXLSX::parseError()]);
        }
    }
    //1, 8 y 10 xlsx solamente
    public function relacionarCuentasCuentasSeguidas(Request $request)
    {
        // return response()->json('Método desactivado');
        $path = public_path('CuentasCuentas/17.xlsx');

        // Validar que el archivo pueda ser analizado correctamente.
        if ($xlsx = SimpleXLSX::parse($path)) {
            // Validar que los encabezados coincidan con los campos esperados.
            $expectedHeaders = ['cuenta', 'clasificador', 'concepto', 'tipo'];
            $actualHeaders = $xlsx->rows()[0];
            $actualHeaders = array_map('trim', array_filter($actualHeaders));
            if (count($expectedHeaders) !== count($actualHeaders) || array_diff($expectedHeaders, $actualHeaders)) {
                return response()->json(['error' => 'Los campos del archivo no coinciden con los campos esperados.']);
            }
            $encabezados = $rows = [];
            $errores = [];
            try {
                $reemplazarCaracterEspecial = function ($texto) {
                    return str_replace("\xc2\xa0", '', $texto);
                };
                // Se obtienen todos los códigos de cuenta existentes en la base de datos utilizando el modelo Cuenta.
                // Se recorren las filas del archivo Excel. La primera fila se considera como encabezados y se almacena en la variable $encabezados.
                // Luego, se combinan los encabezados con los datos de cada fila y se almacenan en $rows.
                // dd($xlsx->rows());
                foreach ($xlsx->rows() as $numero_fila => $datos_fila) {
                    if ($numero_fila === 0) {
                        $encabezados = $datos_fila;
                        continue;
                    }
                    $rows[] = array_combine(array_map('trim', array_filter($encabezados)), array_map('trim', array_map($reemplazarCaracterEspecial, array_filter($datos_fila))));
                }
                // dd($rows[0]['concepto'] == $rows[1]['concepto']);
                // Se inicia una transacción de base de datos para que todas las operaciones de base de datos dentro del bloque se puedan revertir si ocurre algún error.
                $usuariosController = new BitacoraController();
                $usuariosController->bitacora('relacionarCuentasCuentasSeguidas', 'creó o intentó relacionar las cuentas', $request);
                DB::beginTransaction();
                $total = 0;
                $j = 0;
                $creadas = [];
                // Se procesan los datos de cada fila del archivo Excel y se crea un nuevo registro en la base de datos utilizando el modelo Cuenta.
                for ($k = 0; $k < count($rows); $k++) {
                    
                    $cuentaIzquierda = Cuenta::where("Codigo_cuenta", $rows[$k]["cuenta"])->first();
                    $conceptoIzquierda = Concepto::where("descripcion", $rows[$k]["concepto"])->first();
                    $clasificadorIzquierda = ClasificadorDeConcepto::where("codigo_clasificador", $rows[$k]["clasificador"])->first();
                    $cuentaDerecha = Cuenta::where("Codigo_cuenta", $rows[$k + 1]["cuenta"])->first();
                    $conceptoDerecha = Concepto::where("descripcion", $rows[$k + 1]["concepto"])->first();
                    $clasificadorDerecha = ClasificadorDeConcepto::where("codigo_clasificador", $rows[$k + 1]["clasificador"])->first();
                    if (!$cuentaIzquierda || !$conceptoIzquierda || !$clasificadorIzquierda || !$cuentaDerecha || !$conceptoDerecha || !$clasificadorDerecha) {
                        continue;
                    }
                    $interaccionIzquierda = InteraccionCuentaConcepto::where(['cuenta_id' => $cuentaIzquierda->id, 'concepto_id' => $conceptoIzquierda->id, 'clasificador_de_concepto_id' => $clasificadorIzquierda->id, 'tipo_interaccion' => $rows[$k]['tipo']])->first();
                    $interaccionDerecha = InteraccionCuentaConcepto::where(['cuenta_id' => $cuentaDerecha->id, 'concepto_id' => $conceptoDerecha->id, 'clasificador_de_concepto_id' => $clasificadorDerecha->id, 'tipo_interaccion' => $rows[$k + 1]['tipo']])->first();
                    if(!$interaccionIzquierda || !$interaccionDerecha) {
                        dd($cuentaIzquierda, $conceptoIzquierda, $clasificadorIzquierda);
                    }
                    $interaccionExistente = InteraccionCuentaCuenta::where(['id_interaccion_concepto_cuenta_1' => $interaccionIzquierda->id, 'id_interaccion_concepto_cuenta_2' => $interaccionDerecha->id])->first();
                    // dd($interaccionIzquierda, $interaccionDerecha);
                    // dd($interaccionExistente);
                    if ($interaccionExistente) {
                        continue;
                    }

                    if(!$interaccionIzquierda || !$interaccionDerecha) {
                        dd($k);
                    }
                    $res = InteraccionCuentaCuenta::create([
                        'id_interaccion_concepto_cuenta_1' => $interaccionIzquierda->id,
                        'id_interaccion_concepto_cuenta_2' => $interaccionDerecha->id
                    ]);
                    $total++;
                    $k++;
                    $creadas[] = $res;


                    $creadas[] = ['A' => $j];
                }
                // Se verifica si hubo errores durante la importación. Si no hubo errores, se confirma la transacción y se redirige con un mensaje de éxito.
                // Si hubo errores, se revierte la transacción y se redirige con un mensaje de error que muestra los errores.
                if (empty($errores)) {
                    DB::commit();
                    // return response()->json(['Cuentas faltantes' => $cuentasFaltantes]);

                    return response()->json(['mensaje' => 'Importación exitosa con un total de ' . $total . ' cuentas importadas', 'error' => '']);
                } else {
                    DB::rollBack();
                    return response()->json(['error' => $errores]);
                }
                // Si ocurre alguna excepción durante el proceso de importación, se revierte la transacción
                // y se redirige con un mensaje de error que incluye detalles sobre la excepción.
            } catch (\Exception $error) {
                DB::rollBack();
                Log::debug($error->getMessage() .  $error->getLine());
                return response()->json(['error' => $error->getMessage(), 'creadas' => $creadas]);
            }

            // Si no se puede analizar el archivo Excel correctamente (por ejemplo, si el formato no es válido),
            // se redirige con un mensaje de error que incluye información sobre el error de análisis.
        } else {
            return response()->json(['error' => 'Error al analizar el archivo Excel: ' . SimpleXLSX::parseError()]);
        }
    }
}
