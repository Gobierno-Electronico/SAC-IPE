<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\BitacoraController;
use App\Models\Cuenta;
use App\Models\CodigoDepartamento;
use App\Models\Poliza;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Enums\EstatusEvento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Log;
use DB;

use function PHPUnit\Framework\isNull;

class EgresosCapitulo1DevengadoCargaForm extends Component
{
    use WithFileUploads;
    public $fechaAfectacion = "";
    public $archivo;

    public $PTTOEjecutar = 0;
    public $consultarRegistro = false;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $observaciones = '';
    public $path = "";
    public $numeroPolizaRemanente = 0;

    public function render()
    {
        try {
            return view('livewire.egresos-capitulo1-devengadoCarga-form');
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al renderizar devengado del capítulo 1000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    private function descomprometerRecurso($numeroDeEvento, $anioActual, $conceptoDeCarga)
    {
        try {
            set_time_limit(30000);
            ini_set('max_execution_time', 30000);
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('descomprometerRecurso', 'se descomprometió o intentó descomprometer el recurso del evento ' . $numeroDeEvento, request());

            $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) AS numero_poliza')
                ->where('tipo_poliza', '=', 'D')
                ->whereYear('fecha', '=', $anioActual)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            $ultimoNumero = end($numerosPolizas);
            $ultimoNumeroPolizaTipoD = ($ultimoNumero) ? $ultimoNumero + 1 : 1;


            $rowsAfectadas = Poliza::where('tipo_poliza', '=', 'E')
                ->where('evento', '=', $numeroDeEvento)
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 1')
                ->update([
                    'estatus_evento' => EstatusEvento::CANCELADO->value
                ]);
            $resultado = DB::select('EXEC RegistroCancelacionCompromiso1000 @evento = ?, @anio = ?, @numeroPoliza = ?, @conceptoDeRegistro = ?', array($numeroDeEvento, $anioActual, $ultimoNumeroPolizaTipoD, $conceptoDeCarga));
            $rowsInsertadas =  $resultado[0]->filas_insertadas ?? 0;

            return [
                'rows_afectadas' => $rowsAfectadas,
                'rows_insertadas' => $rowsInsertadas,
            ];
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al descomprometer automáticamente el recurso del capítulo 1000: ' . $th->getMessage());
            throw $th;
        }
    }

    private function comprometerRecursoConNuevasSolvencias($eventoAnterior)
    {
        set_time_limit(30000);
        ini_set('max_execution_time', 30000);
        $anioActual = Carbon::now()->year;
        $polizasComprometidoCanceladas = Poliza::where('tipo_poliza', 'E')
            ->where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 1')
            ->where('evento', $eventoAnterior)
            ->whereYear('fecha', $anioActual)
            ->get();

        $polizasRecalendarizacion = Poliza::where('tipo_poliza', 'D')
            ->where('evento', $eventoAnterior)
            ->whereYear('fecha', $anioActual)
            ->get();

        foreach ($polizasRecalendarizacion as $poliza) {
            if ($poliza->categoria == 'RECALENDARIZACION AUMENTO') {
                $coincidenciaCuentaPorEjercer = $polizasComprometidoCanceladas->first(function ($p) use ($poliza) {
                    return $p->area == $poliza->area &&
                        $p->cuenta == $poliza->cuenta &&
                        $p->mes == $poliza->mes;
                });

                if ($coincidenciaCuentaPorEjercer) {
                    $coincidenciaCuentaPorEjercer->total = bcadd($coincidenciaCuentaPorEjercer->total, $poliza->total, 2);
                }

                $coincidenciaCuentaComprometida = $polizasComprometidoCanceladas->first(function ($polizaCompromiso) use ($coincidenciaCuentaPorEjercer) {
                    $conceptoGeneralCuentaPorEjercer = explode('(Por ejercer)', $coincidenciaCuentaPorEjercer->concepto);
                    return $polizaCompromiso->area == $coincidenciaCuentaPorEjercer->area &&
                        str_contains($polizaCompromiso->concepto, $conceptoGeneralCuentaPorEjercer[0]) &&
                        $polizaCompromiso->tipo_interaccion == 'Presupuestal - Cargo' &&
                        $polizaCompromiso->mes == $coincidenciaCuentaPorEjercer->mes;
                });



                if ($coincidenciaCuentaComprometida) {
                    $coincidenciaCuentaComprometida->total = $coincidenciaCuentaPorEjercer->total;
                }
            }

            if ($poliza->categoria == 'RECALENDARIZACION DISMINUCION') {
                $coincidenciaCuentaPorEjercer = $polizasComprometidoCanceladas->first(function ($p) use ($poliza) {
                    return $p->area == $poliza->area &&
                        $p->cuenta == $poliza->cuenta &&
                        $p->mes == $poliza->mes;
                });

                if ($coincidenciaCuentaPorEjercer) {
                    $coincidenciaCuentaPorEjercer->total = bcsub($coincidenciaCuentaPorEjercer->total, $poliza->total, 2);
                }

                $coincidenciaCuentaComprometida = $polizasComprometidoCanceladas->first(function ($polizaCompromiso) use ($coincidenciaCuentaPorEjercer) {
                    $conceptoGeneralCuentaPorEjercer = explode('(Por ejercer)', $coincidenciaCuentaPorEjercer->concepto);
                    return $polizaCompromiso->area == $coincidenciaCuentaPorEjercer->area &&
                        str_contains($polizaCompromiso->concepto, $conceptoGeneralCuentaPorEjercer[0]) &&
                        $polizaCompromiso->tipo_interaccion == 'Presupuestal - Cargo' &&
                        $polizaCompromiso->mes == $coincidenciaCuentaPorEjercer->mes;
                });

                if ($coincidenciaCuentaComprometida) {
                    $coincidenciaCuentaComprometida->total = $coincidenciaCuentaPorEjercer->total;
                }
            }
        }

        $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) AS numero_poliza')
            ->where('tipo_poliza', '=', 'E')
            ->whereYear('fecha', '=', $anioActual)
            ->distinct()
            ->orderBy('numero_poliza')
            ->pluck('numero_poliza')
            ->toArray();

        $numerosEvento = Poliza::selectRaw('CAST(evento AS INT) AS evento')
            ->distinct()
            ->whereYear('fecha', '=', $anioActual)
            ->orderBy('evento')
            ->pluck('evento')
            ->toArray();

        $ultimoNumero = end($numerosPolizas);
        $nuevoNumeroPoliza = ($ultimoNumero) ? $ultimoNumero + 1 : 1;
        $nuevoNumeroEvento = end($numerosEvento) + 1;


        $polizasComprometidoCanceladas->chunk(100)->each(function (Collection $chunk) use ($nuevoNumeroEvento, $nuevoNumeroPoliza) {
            $datos = $chunk->map(function ($poliza) use ($nuevoNumeroEvento, $nuevoNumeroPoliza) {
                $poliza->evento = $nuevoNumeroEvento;
                $poliza->numero_poliza = $nuevoNumeroPoliza;
                $poliza->estatus_evento = EstatusEvento::FINALIZADO->value;
                $poliza->fecha = Carbon::now('America/Mexico_City');
                $poliza->created_at = Carbon::now('America/Mexico_City');
                $poliza->updated_at = Carbon::now('America/Mexico_City');

                $array = $poliza->toArray();

                // Quitar la columna 'id' para evitar errores al insertar
                unset($array['id']);


                // Formatear fechas correctamente
                $array['created_at'] = now('America/Mexico_City')->format('Y-m-d H:i:s');
                $array['updated_at'] = now('America/Mexico_City')->format('Y-m-d H:i:s');
                $array['fecha'] = now('America/Mexico_City')->format('Y-m-d H:i:s');


                return $array;
            })->toArray();


            // Inserta el bloque procesado sin la columna 'id'
            Poliza::insert($datos);
        });
    }


    private function buscarSolvencia($areaPresupuestalSolicitante, $areaDeBusqueda,  $cuenta, $mes, &$solvenciaRequerida, $evento)
    {
        set_time_limit(30000);
        ini_set('max_execution_time', 30000);
        $criteriosDeBusqueda = [
            function () use (&$solvenciaRequerida, $areaPresupuestalSolicitante, $areaDeBusqueda, $cuenta, $mes, $evento) {
                return $this->buscarEnMesesAnteriores($areaPresupuestalSolicitante, $areaDeBusqueda, $cuenta, $cuenta, $mes, $solvenciaRequerida, $evento);
            },
            function () use (&$solvenciaRequerida, $areaPresupuestalSolicitante, $areaDeBusqueda, $cuenta, $mes, $evento) {
                return $this->buscarEnMesesPosteriores($areaPresupuestalSolicitante, $areaDeBusqueda, $cuenta, $cuenta, $mes, $solvenciaRequerida, $evento);
            },
            function () use (&$solvenciaRequerida, $areaPresupuestalSolicitante, $areaDeBusqueda, $cuenta, $mes, $evento) {
                return $this->buscarEnGruposPorJerarquia($areaPresupuestalSolicitante, $areaDeBusqueda, $cuenta, $mes, $solvenciaRequerida, $evento);
            },
            function () use (&$solvenciaRequerida, $areaPresupuestalSolicitante, $cuenta, $mes, $evento) {
                return $this->buscarEnMismaCuentaDeAreaDistinta($areaPresupuestalSolicitante, $cuenta, $mes, $solvenciaRequerida, $evento);
            },
            function () use (&$solvenciaRequerida, $areaPresupuestalSolicitante, $cuenta, $mes, $evento) {
                return $this->buscarEnCuentasDeAreaDistinta($areaPresupuestalSolicitante, $cuenta, $mes, $solvenciaRequerida, $evento);
            },
        ];

        foreach ($criteriosDeBusqueda as $criterio) {
            if ($solvenciaRequerida == 0) {
                break;
            } else {
                $criterio();
            }
        }
    }

    private function buscarEnMesesAnteriores($areaPresupuestalSolicitante, $areaDeBusqueda, $cuentaCargo, $cuentaAbono, $mes, &$solvenciaRequerida, $evento)
    {
        set_time_limit(30000);
        ini_set('max_execution_time', 30000);
        if (strtoupper($mes) == 'ENERO') {
            return;
        }
        //convertir el mes a número, porque el procedimiento lo espera como número
        if (!is_numeric($mes)) {
            $meses = [
                'ENERO' => 1,
                'FEBRERO' => 2,
                'MARZO' => 3,
                'ABRIL' => 4,
                'MAYO' => 5,
                'JUNIO' => 6,
                'JULIO' => 7,
                'AGOSTO' => 8,
                'SEPTIEMBRE' => 9,
                'OCTUBRE' => 10,
                'NOVIEMBRE' => 11,
                'DICIEMBRE' => 12,
            ];
        }
        $numeroMes = $meses[$mes];

        $solvenciaMesesAnteriores = DB::select(
            'exec SolvenciaMesesAnterioresPorCuenta @area = ?, @cuenta = ?, @anio = ?, @mesLimite = ?',
            array($areaDeBusqueda, $cuentaAbono->Codigo_cuenta, Carbon::now()->year, $numeroMes)
        );

        if (count($solvenciaMesesAnteriores) > 0) {
            $polizas = [];

            foreach ($solvenciaMesesAnteriores as $solvencia) {
                if (empty($solvencia->Solvencia) || $solvencia->Solvencia <= 0) {
                    continue;
                }

                if ($solvencia->Solvencia >= $solvenciaRequerida && $solvenciaRequerida > 0) {
                    $this->crearPolizaDeReclasificacion($polizas, $areaPresupuestalSolicitante, $areaDeBusqueda, $cuentaCargo, $cuentaAbono, $solvenciaRequerida, $solvencia->mes, array_search($numeroMes, $meses), $evento);
                    $solvenciaRequerida = 0;
                } else {
                    $this->crearPolizaDeReclasificacion($polizas, $areaPresupuestalSolicitante, $areaDeBusqueda, $cuentaCargo, $cuentaAbono, $solvencia->Solvencia, $solvencia->mes, array_search($numeroMes, $meses), $evento);
                    $solvenciaRequerida = bcsub($solvenciaRequerida, $solvencia->Solvencia, 2);
                }

                if ($solvenciaRequerida == 0) {
                    break;
                }
            }
            Poliza::insert($polizas);
        }
    }

    private function buscarEnMesesPosteriores($areaPresupuestalSolicitante, $areaDeBusqueda, $cuentaCargo, $cuentaAbono, $mes, &$solvenciaRequerida, $evento)
    {
        set_time_limit(30000);
        ini_set('max_execution_time', 30000);

        if (strtoupper($mes) == 'DICIEMBRE') {
            return;
        }
        //convertir el mes a número, porque el procedimiento lo espera como número
        if (!is_numeric($mes)) {
            $meses = [
                'ENERO' => 1,
                'FEBRERO' => 2,
                'MARZO' => 3,
                'ABRIL' => 4,
                'MAYO' => 5,
                'JUNIO' => 6,
                'JULIO' => 7,
                'AGOSTO' => 8,
                'SEPTIEMBRE' => 9,
                'OCTUBRE' => 10,
                'NOVIEMBRE' => 11,
                'DICIEMBRE' => 12,
            ];
        }
        $numeroMes = $meses[$mes];

        $solvenciaMesesPosteriores = DB::select(
            'exec SolvenciaMesesPosterioresPorCuenta @area = ?, @cuenta = ?, @anio = ?, @mesLimite = ?',
            array($areaDeBusqueda, $cuentaAbono->Codigo_cuenta, Carbon::now()->year, $numeroMes)
        );

        if (count($solvenciaMesesPosteriores) > 0) {
            $polizas = [];

            foreach ($solvenciaMesesPosteriores as $solvencia) {
                if (empty($solvencia->Solvencia) || $solvencia->Solvencia <= 0) {
                    continue;
                }

                if ($solvencia->Solvencia >= $solvenciaRequerida && $solvenciaRequerida > 0) {
                    $this->crearPolizaDeReclasificacion($polizas, $areaPresupuestalSolicitante, $areaDeBusqueda, $cuentaCargo, $cuentaAbono, $solvenciaRequerida, $solvencia->mes, array_search($numeroMes, $meses), $evento);
                    $solvenciaRequerida = 0;
                } else {
                    $this->crearPolizaDeReclasificacion($polizas, $areaPresupuestalSolicitante, $areaDeBusqueda, $cuentaCargo, $cuentaAbono, $solvencia->Solvencia, $solvencia->mes, array_search($numeroMes, $meses), $evento);
                    $solvenciaRequerida = bcsub($solvenciaRequerida, $solvencia->Solvencia, 2);
                }

                if ($solvenciaRequerida == 0) {
                    break;
                }
            }

            Poliza::insert($polizas);
        }
    }

    private function buscarEnMismaCuentaDeAreaDistinta($areaPresupuestalSolicitante, $cuenta, $mes, &$solvenciaRequerida, $evento)
    {
        set_time_limit(30000);
        ini_set('max_execution_time', 30000);
        $anioActual = Carbon::now()->year;
        $solvenciaCuentaAreasDistintas = DB::select('exec SolvenciaCuentaEnAreasDistitas @cuenta = ?, @anio = ?, @mes = ?, @area = ?', array($cuenta->Codigo_cuenta, $anioActual, $mes, $areaPresupuestalSolicitante));

        if (count($solvenciaCuentaAreasDistintas) > 0) {

            $polizas = [];

            foreach ($solvenciaCuentaAreasDistintas as $registro) {
                if (floatval($registro->Solvencia) > 0) {
                    if (floatval($registro->Solvencia) >= $solvenciaRequerida && $solvenciaRequerida > 0) {
                        $this->crearPolizaDeReclasificacion($polizas, $areaPresupuestalSolicitante, $registro->area, $cuenta, $cuenta, $solvenciaRequerida, $mes, $mes, $evento);
                        $solvenciaRequerida = 0;
                    } else {
                        $this->crearPolizaDeReclasificacion($polizas, $areaPresupuestalSolicitante, $registro->area, $cuenta, $cuenta, floatval($registro->Solvencia), $mes, $mes, $evento);
                        $solvenciaRequerida = bcsub($solvenciaRequerida, $registro->Solvencia, 2);
                    }
                    if ($solvenciaRequerida == 0) {
                        break;
                    }
                } else {
                    continue;
                }
            }

            Poliza::insert($polizas);
        }
    }

    private function buscarEnGruposPorJerarquia($areaPresupuestalSolicitante, $areaDeBusqueda, $cuenta, $mes, &$solvenciaRequerida, $evento, $nivel = 1)
    {
        set_time_limit(30000);
        ini_set('max_execution_time', 30000);

        $gruposPorJerarquia = [
            1 => '16',
            2 => '15',
            3 => '13',
            4 => '17',
            5 => '14',
            6 => '12',
            7 => '11'
        ];

        // Si ya no hay más niveles que buscar, termina
        if (!isset($gruposPorJerarquia[$nivel])) {
            return;
        }

        $primerosNumerosCOG = $gruposPorJerarquia[$nivel];

        //extraemos las solvencias del primer grupo al que se le puede quitar recurso
        $solvenciaPorGrupoJerarquia = DB::select('exec SolvenciaGruposPorCOG @area = ?, @anio = ?, @primerosNumerosCOG = ?, @mes = ?', array($areaDeBusqueda, 2025, $primerosNumerosCOG, $mes));

        if (count($solvenciaPorGrupoJerarquia) > 0) {

            $polizas = [];

            foreach ($solvenciaPorGrupoJerarquia as $registro) {

                //verificamos si en la cuenta tenemos solvencia en el mes en el que se requiere el recurso
                if ($registro && floatval($registro->Solvencia) > 0) {
                    $cuentaCargo = Cuenta::where('Codigo_cuenta', '=', $registro->cuenta)->first();
                    if (floatval($registro->Solvencia) >= $solvenciaRequerida && $solvenciaRequerida > 0) {
                        $this->crearPolizaDeReclasificacion($polizas, $areaPresupuestalSolicitante, $areaDeBusqueda, $cuentaCargo, $cuenta, floatval($solvenciaRequerida), $registro->mes, $mes, $evento);
                        $solvenciaRequerida = 0;
                    } else {
                        $this->crearPolizaDeReclasificacion($polizas, $areaPresupuestalSolicitante, $areaDeBusqueda, $cuentaCargo, $cuenta, floatval($registro->Solvencia), $registro->mes, $mes, $evento);
                        $solvenciaRequerida = bcsub($solvenciaRequerida, $registro->Solvencia, 2);
                    }

                    if ($solvenciaRequerida == 0) {
                        break;
                    } else {
                        $this->buscarEnMesesAnteriores($areaPresupuestalSolicitante, $areaDeBusqueda, $cuentaCargo, $cuenta, $mes, $solvenciaRequerida, $evento);
                        if ($solvenciaRequerida == 0) {
                            break;
                        } else {
                            $this->buscarEnMesesPosteriores($areaPresupuestalSolicitante, $areaDeBusqueda, $cuentaCargo, $cuenta, $mes, $solvenciaRequerida, $evento);
                        }
                    }
                } else {
                    continue;
                }
            }

            Poliza::insert($polizas);
        }


        // Si aún hay solvencia pendiente, llamar al siguiente nivel
        if ($solvenciaRequerida > 0) {
            $this->buscarEnGruposPorJerarquia(
                $areaPresupuestalSolicitante,
                $areaDeBusqueda,
                $cuenta,
                $mes,
                $solvenciaRequerida,
                $evento,
                $nivel + 1
            );
        } else {
            return;
        }
    }

    private function buscarEnCuentasDeAreaDistinta($areaPresupuestalSolicitante, $cuenta, $mes, &$solvenciaRequerida, $evento)
    {
        set_time_limit(30000);
        ini_set('max_execution_time', 30000);

        $anioActual = Carbon::now()->year;
        $areasConSolvencia = DB::select('exec SolvenciaAreas @anio = ?, @area = ?', array($anioActual, $areaPresupuestalSolicitante));

        if (count($areasConSolvencia) > 0) {
            foreach ($areasConSolvencia as $area) {

                $cuentasConSolvencia = DB::table('polizas')
                    ->selectRaw('cuenta as Codigo_cuenta, concepto as Descripcion_cuenta')
                    ->where('tipo_poliza', 'P')
                    ->where('area', $area->area)
                    ->where('concepto', 'like', '%(Por ejercer)%')
                    ->where('categoria', 'like', '%EGRESOS%')
                    ->where('total', '>', 0)
                    ->groupBy('cuenta', 'concepto')
                    ->orderByRaw("CASE WHEN cuenta = ? THEN 0 ELSE 1 END, cuenta", $cuenta->Codigo_cuenta)
                    ->get();

                foreach ($cuentasConSolvencia as $cuentaConSolvencia) {
                    Log::info('BUsque en meses anteriores');
                    $this->buscarEnMesesAnteriores($areaPresupuestalSolicitante, $area->area, $cuentaConSolvencia, $cuenta, $mes, $solvenciaRequerida, $evento);

                    if ($solvenciaRequerida == 0) {
                        return;
                    }

                    Log::info('BUsque en meses posteriores');
                    $this->buscarEnMesesPosteriores($areaPresupuestalSolicitante, $area->area, $cuentaConSolvencia, $cuenta, $mes, $solvenciaRequerida, $evento);

                    if ($solvenciaRequerida == 0) {
                        return;
                    }

                    Log::info('Busque por jerarquía');
                    $this->buscarEnGruposPorJerarquia($areaPresupuestalSolicitante, $area->area, $cuentaConSolvencia, $cuenta, $mes, $solvenciaRequerida, $evento);
                }
            }
        }

        if ($solvenciaRequerida > 0) {
            Log::info('NO SE SOLVENTÓ TODO EL PRESUPUESTO');
        }
    }

    private function crearPolizaDeReclasificacion(array &$polizas, $areaPresupuestalSolicitante, $areaDeBusqueda, $cuentaCargo, $cuentaAbono, $total, $mesCargo, $mesAbono, $evento)
    {
        $fechaActual = Carbon::now('America/Mexico_City');
        $idUsuarioRegistrante = Auth::id();

        $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) AS numero_poliza')
            ->where('tipo_poliza', '=', 'D')
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->distinct()
            ->orderBy('numero_poliza')
            ->pluck('numero_poliza')
            ->toArray();
        $ultimoNumero = end($numerosPolizas);
        $ultimoNumeroPolizaTipoD = ($ultimoNumero) ? $ultimoNumero + 1 : 1;

        //crear poliza cargo
        array_push($polizas, [
            'idUsuarioRegistrante' => $idUsuarioRegistrante,
            'area' => $areaDeBusqueda,
            'tipo_poliza' => 'D',
            'numero_poliza' =>  $ultimoNumeroPolizaTipoD,
            'fecha' => $fechaActual,
            'cuenta' => $cuentaCargo->Codigo_cuenta,
            'concepto' => $cuentaCargo->Descripcion_cuenta,
            'total' => $total,
            'mes' => $mesCargo,
            'descripcion' => 'Reclasificación presupuestal automática del capítulo 1000',
            'evento' => $evento,
            'tipo_interaccion' => 'Presupuestal - Cargo',
            'validado' => false,
            'estatus_evento' => EstatusEvento::FINALIZADO->value,
            'categoria' => 'RECALENDARIZACION DISMINUCION',
            'created_at' => $fechaActual,
            'updated_at' => $fechaActual
        ]);

        //crear poliza abono
        array_push($polizas, [
            'idUsuarioRegistrante' => $idUsuarioRegistrante,
            'area' => $areaPresupuestalSolicitante,
            'tipo_poliza' => 'D',
            'numero_poliza' =>  $ultimoNumeroPolizaTipoD,
            'fecha' => $fechaActual,
            'cuenta' => $cuentaAbono->Codigo_cuenta,
            'concepto' => $cuentaAbono->Descripcion_cuenta,
            'total' => $total,
            'mes' => $mesAbono,
            'descripcion' => 'Reclasificación presupuestal automática del capítulo 1000',
            'evento' => $evento,
            'tipo_interaccion' => 'Presupuestal - Abono',
            'validado' => false,
            'estatus_evento' => EstatusEvento::FINALIZADO->value,
            'categoria' => 'RECALENDARIZACION AUMENTO',
            'created_at' => $fechaActual,
            'updated_at' => $fechaActual
        ]);
    }


    public function cargarDevengado()
    {
        set_time_limit(30000);
        ini_set('max_execution_time', 30000);
        $solvenciaRequerida = 0;
        $seDescomprometioRecurso = false;
        try {
            $idUsuarioRegistrante = Auth::id();
            $datosExcelAsociados = $this->leerArchivoExcel();
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('cargarDevengado', 'cargó o intentó cargar el devengado del capítulo 1000 de egresos', request());
            DB::beginTransaction();
            $cuentasFaltantesPlanCuentas = [];
            $cuentasEnLaGuiaFaltantes = [];
            $anioActual = Carbon::now()->year;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);

            $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) as numero_poliza')
                ->where('tipo_poliza', '=', 'E')
                ->whereYear('fecha', '=', $anioActual)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();

            $numerosEvento = Poliza::selectRaw('CAST(evento AS INT) AS evento')
                ->distinct()
                ->whereYear('fecha', '=', $anioActual)
                ->orderBy('evento')
                ->pluck('evento')
                ->toArray();

            $ultimoNumero = end($numerosPolizas);
            $this->numeroPoliza = ($ultimoNumero) ? $ultimoNumero + 1 : 1;
            $this->numeroEvento = end($numerosEvento);

            $polizas = [];
            foreach ($datosExcelAsociados as $dato) {

                if ($this->observaciones == '') {
                    $this->observaciones = $dato['CONCEPTO'];
                }

                $cuenta = Cuenta::where("Codigo_cuenta", $dato["CUENTA"])->first();
                if (!$cuenta) {
                    $codigosExistentesPlan = array_column($cuentasFaltantesPlanCuentas, 'Codigo_cuenta');
                    if (!in_array($dato["CUENTA"], $codigosExistentesPlan)) {
                        $cuentasFaltantesPlanCuentas[] = [
                            "Codigo_cuenta" => $dato["CUENTA"],
                            "Descripcion_cuenta" => $dato["DESCRIPCION"]
                        ];
                    }
                    continue;
                }

                //inicia módulo para transferencias
                if (empty($cuentasEnLaGuiaFaltantes) && empty($cuentasFaltantesPlanCuentas)) {
                    if (str_contains($dato["DESCRIPCION"], "(Devengado)")) {
                        //obtener cuenta comprometida en base a la devengada
                        $conceptoGeneralCuenta = explode('(Devengado)', $dato['DESCRIPCION']);
                        $descripcionCuentaComprometida = $conceptoGeneralCuenta[0] . '(Comprometido)';
                        $codigoCuentaComprometida = Cuenta::where('Descripcion_cuenta', '=', $descripcionCuentaComprometida)
                            ->value('Codigo_cuenta');
                        $solvencia = DB::select(
                            'EXEC SolvenciaComprometidoCapitulo1 @area = ?, @cuenta = ?, @anio = ?, @mes = ?, @evento = ?',
                            array($dato['AREA EJECUTORA'], $codigoCuentaComprometida, $anioActual, $dato['MES'], $this->numeroEvento)
                        )[0]->total;

                        $totalSinFormato = (float) str_replace([',', '$', ' '], '', $dato['CARGO']);
                        if ($totalSinFormato > $solvencia) {

                            if ($seDescomprometioRecurso == false) {
                                //Cancelar el compromiso y descomprometerlo
                                $resultadoDescomprometerRecurso = $this->descomprometerRecurso($this->numeroEvento, $anioActual, $dato['CONCEPTO']);
                            }
                            if ($resultadoDescomprometerRecurso['rows_afectadas'] > 0 && $resultadoDescomprometerRecurso['rows_insertadas'] > 0) {
                                $seDescomprometioRecurso = true;


                                $solvenciaRequerida = bcsub($totalSinFormato, $solvencia, 2);
                                //obtener cuenta presupuestal (por ejercer) en base al concepto general
                                $descripcionCuentaPresupuestal = $conceptoGeneralCuenta[0] . '(Por ejercer)';
                                $cuentaPresupuestal = Cuenta::where('Descripcion_cuenta', '=', $descripcionCuentaPresupuestal)->first();

                                ini_set('memory_limit', '1024M');
                                $this->buscarSolvencia($dato['AREA EJECUTORA'], $dato['AREA EJECUTORA'], $cuentaPresupuestal, $dato['MES'], $solvenciaRequerida, $this->numeroEvento);
                            }
                        }
                    }
                }


                if (str_contains($dato["DESCRIPCION"], "(Devengado)")) {

                    $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $cuenta->id)
                        ->where('concepto_id', '=', 10102)
                        ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();

                    if (!$interaccionCuentaConceptoPrincipal) {
                        $codigosExistentes = array_column($cuentasEnLaGuiaFaltantes, 'Codigo_cuenta');

                        if (!in_array($dato['CUENTA'], $codigosExistentes)) {
                            $cuentasEnLaGuiaFaltantes[] = [
                                "Codigo_cuenta" => $dato["CUENTA"],
                                "Descripcion_cuenta" => $dato["DESCRIPCION"]
                            ];
                        }
                        continue;
                    }

                    $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                        ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                        ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();

                    $interaccionCuentaCuentasFiltradas = [];
                    foreach ($interaccionCuentaCuentas as $cuenta) {
                        if ($cuenta['tipo_interaccion'] != 'Contable - Abono') {
                            $interaccionCuentaCuentasFiltradas[] = $cuenta;
                        }
                    }

                    $interaccionCuentaCuentas = $interaccionCuentaCuentasFiltradas;

                    $this->total = $this->total + floatval(str_replace([',', '$', ' '], ['', '', ''], $dato['CARGO']));
                    array_push($polizas, [
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $dato['AREA EJECUTORA'],
                        'tipo_poliza' => 'E',
                        'numero_poliza' =>  $this->numeroPoliza + 1,
                        'fecha' => $this->fechaAfectacion,
                        'cuenta' => $dato['CUENTA'],
                        'concepto' => $dato['DESCRIPCION'],
                        'total' => floatval(str_replace([',', '$', ' '], ['', '', ''], $dato['CARGO'])),
                        'mes' => $dato['MES'],
                        'descripcion' => $dato['CONCEPTO'],
                        'evento' => $this->numeroEvento + 1,
                        'tipo_interaccion' => $interaccionCuentaConceptoPrincipal->tipo_interaccion,
                        'validado' => false,
                        'estatus_evento' => true,
                        'categoria' => 'EGRESOS DEVENGADO CAPITULO 1',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);

                    foreach ($interaccionCuentaCuentas as $dataCuenta) {
                        $this->total = $this->total + floatval(str_replace([',', '$', ' '], ['', '', ''], $dato['CARGO']));
                        array_push($polizas, [
                            'idUsuarioRegistrante' => $idUsuarioRegistrante,
                            'area' => $dato['AREA EJECUTORA'],
                            'tipo_poliza' => 'E',
                            'numero_poliza' =>  $this->numeroPoliza + 1,
                            'fecha' => $this->fechaAfectacion,
                            'cuenta' => $dataCuenta['Codigo_cuenta'],
                            'concepto' => $dataCuenta['Descripcion_cuenta'],
                            'total' => floatval(str_replace([',', '$', ' '], ['', '', ''], $dato['CARGO'])),
                            'mes' => $dato['MES'],
                            'descripcion' => $dato['CONCEPTO'],
                            'evento' => $this->numeroEvento + 1,
                            'tipo_interaccion' => $dataCuenta['tipo_interaccion'],
                            'validado' => false,
                            'estatus_evento' => true,
                            'categoria' => 'EGRESOS DEVENGADO CAPITULO 1',
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ]);
                    }
                } else {
                    $this->total = $this->total + floatval(str_replace([',', '$', ' '], ['', '', ''], $dato['ABONO']));
                    array_push($polizas, [
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $dato['AREA EJECUTORA'],
                        'tipo_poliza' => 'E',
                        'numero_poliza' =>  $this->numeroPoliza + 1,
                        'fecha' => $this->fechaAfectacion,
                        'cuenta' => $dato['CUENTA'],
                        'concepto' => $dato['DESCRIPCION'],
                        'total' => floatval(str_replace([',', '$', ' '], ['', '', ''], $dato['ABONO'])),
                        'mes' => $dato['MES'],
                        'descripcion' => $dato['CONCEPTO'],
                        'evento' => $this->numeroEvento + 1,
                        'tipo_interaccion' => 'Contable - Abono',
                        'validado' => false,
                        'estatus_evento' => true,
                        'categoria' => 'EGRESOS DEVENGADO CAPITULO 1',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }
            }

            if ($solvenciaRequerida == 0 && $seDescomprometioRecurso) {
                $this->comprometerRecursoConNuevasSolvencias($this->numeroEvento);
            }

            if (!empty($cuentasFaltantesPlanCuentas)) {
                $mensajeError = "Cuentas Faltantes en el plan:<br>";
                foreach ($cuentasFaltantesPlanCuentas as $cuenta) {
                    $mensajeError .= "Código: {$cuenta['Codigo_cuenta']}, Descripción: {$cuenta['Descripcion_cuenta']}<br>";
                }

                $this->dispatch('mostrarMensaje', mensaje: $mensajeError, tipo: 'error', tiempo: 5000);
            }

            if (empty($cuentasEnLaGuiaFaltantes)) {
                collect($polizas)->chunk(120)->each(function ($chunk) {
                    Poliza::insert($chunk->toArray());
                }); // divide $polizas en partes pequeñas (chunks) de 120 elementos. Esto evita la sobrecarga de memoria al hacer inserciones en la base.


                // $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) as numero_poliza')
                //     ->where('tipo_poliza', '=', 'EAUX')
                //     ->whereYear('fecha', '=', Carbon::now()->year)
                //     ->distinct()
                //     ->orderBy('numero_poliza')
                //     ->pluck('numero_poliza')
                //     ->toArray();
                // sort($numerosPolizas);
                // $this->numeroPolizaRemanente = (int)end($numerosPolizas) + 1;

                // $polizasInicialesEgresosComprometido = Poliza::where('tipo_poliza', '=', 'E')
                //     ->where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 1')
                //     ->where('evento', '=', $this->numeroEvento + 1)
                //     ->whereYear('fecha', '=', Carbon::now()->year)
                //     ->get();

                // $polizasInicialesEgresosDevengado = Poliza::where('tipo_poliza', '=', 'E')
                //     ->where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 1')
                //     ->where('evento', '=', $this->numeroEvento + 1)
                //     ->whereYear('fecha', '=', Carbon::now()->year)
                //     ->where('concepto', 'LIKE', '%(Devengado)%')
                //     ->get();

                // $totalRemanente = DB::select('EXEC ImporteTotalCapitulo1Devengado @evento = ?', array($this->numeroEvento + 1))[0]->MontoDelEvento;
                // if ($totalRemanente > 0) {
                //     foreach ($polizasInicialesEgresosComprometido as $polizaImporte) {
                //         $clave = $polizaImporte->cuenta . '-' . $polizaImporte->concepto;
                //         if (isset($resultado[$clave])) {
                //             $resultado[$clave]['total'] += $polizaImporte['total'];
                //         } else {
                //             $resultado[$clave] = [
                //                 'idUsuarioRegistrante' => $idUsuarioRegistrante,
                //                 'area' => $polizaImporte->area,
                //                 'tipo_poliza' => 'EAUX',
                //                 'numero_poliza' =>  $this->numeroPolizaRemanente,
                //                 'fecha' => $this->fechaAfectacion,
                //                 'cuenta' => $polizaImporte->cuenta,
                //                 'concepto' => $polizaImporte->concepto,
                //                 'total' => $polizaImporte['total'],
                //                 'mes' => $polizaImporte->mes,
                //                 'descripcion' => $polizaImporte->descripcion,
                //                 'evento' => $this->numeroEvento,
                //                 'tipo_interaccion' => $polizaImporte->tipo_interaccion,
                //                 'validado' => false,
                //                 'estatus_evento' => EstatusEvento::ACTIVO->value,
                //                 'categoria' => 'EGRESOS COMPROMETIDO CAPITULO 1 REMANENTE DEVENGADO',
                //                 'created_at' => $fecha,
                //                 'updated_at' => $fecha
                //             ];
                //         }
                //     }

                //     foreach ($resultado as $polizaInicial) {
                //         $total = $polizaInicial['total'];
                //         foreach ($polizasInicialesEgresosDevengado as $polizaDevengado) {
                //             $conceptoGeneral = explode('(', $polizaDevengado->concepto);

                //             if (str_contains($polizaInicial['concepto'], rtrim($conceptoGeneral[0])) !== false && $conceptoGeneral[1] == 'Devengado)') {
                //                 $total = $total - $polizaDevengado['total'];
                //             }
                //         }
                //         Poliza::create([
                //             'idUsuarioRegistrante' => $idUsuarioRegistrante,
                //             'area' => $polizaInicial['area'],
                //             'tipo_poliza' => 'EAUX',
                //             'numero_poliza' =>  $this->numeroPolizaRemanente,
                //             'fecha' => $this->fechaAfectacion,
                //             'cuenta' => $polizaInicial['cuenta'],
                //             'concepto' => $polizaInicial['concepto'],
                //             'total' => $total,
                //             'mes' => $polizaInicial['mes'],
                //             'descripcion' => $polizaInicial['descripcion'],
                //             'evento' => $this->numeroEvento,
                //             'tipo_interaccion' => $polizaInicial['tipo_interaccion'],
                //             'validado' => false,
                //             'estatus_evento' => EstatusEvento::ACTIVO->value,
                //             'categoria' => 'EGRESOS COMPROMETIDO CAPITULO 1 REMANENTE DEVENGADO',
                //             'created_at' => $fecha,
                //             'updated_at' => $fecha
                //         ]);
                //     }
                // } else {
                //     $this->numeroPolizaRemanente = 0;
                //     Poliza::where('evento', '=', $this->numeroEvento)
                //         ->whereIn('categoria', ['EGRESOS COMPROMETIDO CAPITULO 1'])
                //         ->whereYear('fecha', '=', Carbon::now()->year)
                //         ->update(['estatus_evento' => EstatusEvento::FINALIZADO->value]);
                // }


                DB::commit();
                Storage::delete($this->path);
                $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total, $this->numeroPolizaRemanente);
            } else {
                $mensajeError = "Cuentas Faltantes en la guía contabilizadora:<br>";
                foreach ($cuentasEnLaGuiaFaltantes as $cuenta) {
                    $mensajeError .= "Código: {$cuenta['Codigo_cuenta']} - Descripción: {$cuenta['Descripcion_cuenta']}<br>";
                }

                $this->dispatch('mostrarMensaje', mensaje: $mensajeError, tipo: 'error', tiempo: 5000);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ocurrió un error al cargar devengado del capítulo 1000: ' . $e->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        } finally {
            $this->dispatch('esconderCargando');
        }
    }


    public function leerArchivoExcel()
    {
        try {
            $this->validate([
                'archivo' => 'required|mimes:xlsx',
                'fechaAfectacion' => 'required'
            ], [
                'archivo.required' => "Debes seleccionar al menos un archivo.",
                'archivo.mimes' => "El archivo debe ser de tipo XLSX.",
                'fechaAfectacion.required' => "La fecha de afectación es requerida."
            ]);

            $path = $this->path;
            $path = $this->archivo->store('temp');
            $filePath = storage_path('app/' . $path);

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            $encabezadosEsperados = ['AREA EJECUTORA', 'CUENTA', 'DESCRIPCION', 'CONCEPTO', 'MES', 'CARGO', 'ABONO'];

            // Obtener encabezados del archivo y filtrar vacíos
            $encabezadosArchivo = array_filter(array_map('trim', $data[0]), fn($valor) => $valor !== "");

            // Reindexar array para evitar problemas de índices
            $encabezadosArchivo = array_values($encabezadosArchivo);

            $diferencias = array_diff_assoc($encabezadosArchivo, $encabezadosEsperados);

            if (!empty($diferencias)) {
                Storage::delete($path);
                $mensajeError = "Los siguientes encabezados no coinciden:\n";

                foreach ($diferencias as $indice => $valorIncorrecto) {
                    $mensajeError .= "- Esperado: '{$encabezadosEsperados[$indice]}' → Recibido: '{$valorIncorrecto}'\n";
                }

                $this->dispatch('mostrarMensaje', mensaje: $mensajeError, tipo: 'error', tiempo: 5000);
                return;
            }

            $indicesValoresNumericos = range(5, 6);
            $errores = [];

            foreach ($data as $filaIndex => $fila) {
                if ($filaIndex == 0) continue;

                foreach ($indicesValoresNumericos as $indice) {
                    $valor = $fila[$indice] ?? null;

                    if ($valor !== null && $valor !== '') {

                        $valor = preg_replace('/[^\d.-]/', '', $valor);

                        if (!is_numeric($valor) || $valor < 0 || !preg_match('/^\d+(\.\d{1,2})?$/', $valor)) {
                            $errores[] = "Fila " . ($filaIndex + 1) . ", Columna '{$encabezadosEsperados[$indice]}': Valor inválido '{$valor}' <br> - ";
                        }
                    }
                }
            }

            if (!empty($errores)) {
                Storage::delete($path);
                $mensajeError = "Errores en los valores numéricos:<br> - " . implode("\n", $errores);
                $this->dispatch('mostrarMensaje', mensaje: $mensajeError, tipo: 'error', tiempo: 5000);
                return;
            }

            $datosExcelAsociados = [];
            $encabezadosObligatorios = ['AREA EJECUTORA', 'CUENTA', 'DESCRIPCION', 'CONCEPTO', 'MES'];
            $numeroFila = 0;
            foreach (array_slice($data, 1) as $fila) {
                $numeroFila++;

                $fila = array_values(array_filter($fila, function ($valor) {
                    return trim($valor) !== '';
                }));

                if (count($encabezadosArchivo) != count($fila)) {
                    Storage::delete($path);
                    $this->dispatch('mostrarMensaje', mensaje: "El número de encabezados no coincide con el número de datos de una fila", tipo: 'error', tiempo: 5000);
                    return;
                }

                $filaAsociativa = array_combine($encabezadosArchivo, $fila);

                foreach ($encabezadosObligatorios as $campo) {
                    if (empty(trim($filaAsociativa[$campo]))) {
                        Storage::delete($path);
                        $this->dispatch('mostrarMensaje', mensaje: "El campo obligatorio '$campo' está vacío en la fila '$numeroFila", tipo: 'error', tiempo: 5000);
                        return;
                    }
                }

                $cargo = trim($filaAsociativa["CARGO"] ?? '');
                $abono = trim($filaAsociativa["ABONO"] ?? '');

                if ($cargo === '' && $abono === '') {
                    Storage::delete($path);
                    $this->dispatch('mostrarMensaje', mensaje: "Cada fila debe tener un valor en 'CARGO' o 'ABONO'", tipo: 'error', tiempo: 5000);
                    return;
                }

                $datosExcelAsociados[] = $filaAsociativa;
            }

            return $datosExcelAsociados;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'error', tiempo: 3000);
        } catch (\Exception $e) {
            Log::error("Error al procesar el archivo en carga de devengado del 1000: " . $e->getMessage() . ' ' . $e->getLine());
            Storage::delete($path);
            $this->dispatch('mostrarMensaje', mensaje: 'Hubo un error al procesar el archivo.', tipo: 'error', tiempo: 3000);
        }
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total)
    {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento + 1;
        $this->numeroPoliza = $numeroPoliza + 1;
        $this->total = $total;
    }
}
