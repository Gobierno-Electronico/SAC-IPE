<?php

namespace App\Livewire;

use App\Models\Cuenta;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Clases\Column;
use Livewire\Attributes\On;
use App\Livewire\Tabla;
use App\Models\Poliza;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Http\Controllers\BitacoraController;
use Illuminate\Support\Facades\Auth;
use Log;
use DB;
use App\Enums\EstatusEvento;

class EgresosCapitulo4PagadoTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $totalDisponible = 0;
    public $totalDisponibleContable = 0;
    public $numeroEvento;
    public $numeroPolizaRemanente;
    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }

    public function render()
    {
        return view('livewire.egresos-capitulo4-pagado-table');
    }

    public function query(): Builder {}

    public function data()
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = array_slice($this->cacheData, $this->perPage * ($currentPage - 1), $this->perPage);
        return new LengthAwarePaginator($currentItems, count($this->cacheData), $this->perPage, $currentPage);
    }

    public function columns(): array
    {
        return [
            Column::make('area', 'Area'),
            Column::make('documentoFuente', 'Documento fuente'),
            Column::make('partida', 'Partida'),
            Column::make('cuentaBanco', 'Cuenta contable'),
            Column::make('cuentaRetenciones', 'Cuenta de retenciones'),
            Column::make('mes', 'Mes'),
            Column::make('movimiento', 'Movimiento'),
            Column::make('pttoEjercido', 'PPTO Ejercido')->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try {
            if (bccomp((string)($this->total + $registro['importe']), (string)$registro['montoEvento'], 2) == 1) {
                $this->dispatch('mostrarMensaje', mensaje: 'Monto total del evento superado', tipo: 'error', tiempo: 3000);
                return;
            }

            if ($this->verificarPresupuesto($registro) && $this->verificarMontoContable($registro)) {
                $nuevoRegistro = [
                    'id' => 0,
                    'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                    'partida' => $registro['codigoPartida'] . ' ' . $registro['descripcionPartida'],
                    'cuentaBanco' => $registro['codigoCuentaBanco'] . ' ' . $registro['descripcionCuentaBanco'],
                    'cuentaRetenciones' => $registro['codigoCuentaRetenciones'] . ' ' . $registro['descripcionCuentaRetenciones'],
                    'mes' => $registro['mes'],
                    'movimiento' => 'PAGADO',
                    'pttoEjercido' => $registro['pttoEjercido'],
                    'importe' => $registro['importe'],
                    'disponibilidad' => $this->totalDisponible,
                    'documentoFuente' => $registro['documentoFuente'],
                ];
                array_push($this->cacheData, $nuevoRegistro);
                array_push($this->dataCompleta, $registro);
                $this->total = 0;
                foreach ($this->cacheData as $key => $registro) {
                    $this->cacheData[$key]['id'] = $key + 1;
                    $this->dataCompleta[$key]['id'] = $key + 1;
                    $this->total += $registro['importe'];
                }
                $this->dispatch('cambioTotal', total: $this->total);
            }
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en pagado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarPresupuesto($registro)
    {
        $solvencia = $registro['pttoEjercido'];
        $this->totalDisponible = $solvencia - $registro['importe'];
        $totalImportes = 0;

        foreach ($this->cacheData as $movimiento) {
            if (str_contains($movimiento['area'], $registro['codigoAreaResponsable']) && str_contains($movimiento['partida'], $registro['codigoPartida']) && $movimiento['mes'] == $registro['mes']) {
                $totalImportes += $movimiento['importe'];
            }
        }

        if ($totalImportes > 0) {
            $this->totalDisponible = bcsub(bcsub($solvencia, $totalImportes, 2), $registro['importe'], 2);
        }


        if ($this->totalDisponible < 0) {
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto ejercido insuficiente', tipo: 'warning', tiempo: 3000);
            return false;
        }
        return true;
    }

    public function verificarMontoContable($registro)
    {
        if ($registro['montoContable'] == 0) {
            return true;
        }
        $solvenciaContable = $registro['montoContable'];
        $this->totalDisponibleContable = $solvenciaContable - $registro['importe'];
        $totalImportes = 0;

        foreach ($this->cacheData as $movimiento) {
            if (str_contains($movimiento['area'], $registro['codigoAreaResponsable']) && str_contains($movimiento['cuentaRetenciones'], $registro['codigoCuentaRetenciones']) && $movimiento['mes'] == $registro['mes']) {
                $totalImportes += $movimiento['importe'];
            }
        }

        if ($totalImportes > 0) {
            $this->totalDisponibleContable = bcsub(bcsub($solvenciaContable, $totalImportes, 2), $registro['importe'], 2);
        }


        if ($this->totalDisponibleContable < 0) {
            $this->dispatch('mostrarMensaje', mensaje: 'Monto contable insuficiente', tipo: 'warning', tiempo: 3000);
            return false;
        }
        return true;
    }

    public function edit($id)
    {
        try {
            $this->recalcularDisponibilidad($id);
            foreach ($this->dataCompleta as $key => $registro) {
                if ($registro['id'] == $id) {
                    $datosRegistro = [
                        'area' => $registro['areaResponsableId'],
                        'partida' => $registro['partidaId'],
                        'cuentaBanco' => $registro['cuentaBancoId'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe'],
                        'cuentaRetenciones' => $registro['cuentaRetencionesId'],
                        'pttoEjercido' => $registro['pttoEjercido'],
                        'montoContable' => $registro['montoContable'],
                        'documentoFuente' => $registro['documentoFuente'],
                    ];

                    unset($this->dataCompleta[$key]);
                    $this->dataCompleta = array_values($this->dataCompleta);
                    $this->dispatch('llenar-formulario', $datosRegistro);
                    break;
                }
            }

            foreach ($this->cacheData as $key => $registro) {
                if ($registro['id'] == $id) {
                    unset($this->cacheData[$key]);
                    $this->cacheData = array_values($this->cacheData);
                    break;
                }
            }

            $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al editar en pagado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function delete($id)
    {
        try {
            $this->recalcularDisponibilidad($id);
            foreach ($this->cacheData as $key => $registro) {
                if ($registro['id'] == $id) {
                    unset($this->cacheData[$key]);
                    $this->cacheData = array_values($this->cacheData);
                    break;
                }
            }

            foreach ($this->dataCompleta as $key => $registro) {
                if ($registro['id'] == $id) {
                    unset($this->dataCompleta[$key]);
                    $this->dataCompleta = array_values($this->dataCompleta);
                    break;
                }
            }

            $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al eliminar en pagado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function recalcularDisponibilidad($id)
    {
        $datosSeleccionado = [];
        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                $datosSeleccionado = [
                    'codigoArea' => $registro['codigoAreaResponsable'],
                    'codigoCuentaPartida' => $registro['codigoPartida'],
                    'mes' => $registro['mes']
                ];
            }
        }

        $totalImportes = 0;
        foreach ($this->cacheData as $key => $movimiento) {
            if ($movimiento['id'] != $id && str_contains($movimiento['area'], $datosSeleccionado['codigoArea']) && str_contains($movimiento['partida'], $datosSeleccionado['codigoCuentaPartida']) && $movimiento['mes'] == $datosSeleccionado['mes']) {
                if ($totalImportes == 0) {
                    $movimiento['disponibilidad'] = bcsub($movimiento['pttoEjercido'], $movimiento['importe'], 2);
                    $totalImportes += $movimiento['importe'];
                } else {
                    $movimiento['disponibilidad'] = bcsub(bcsub($movimiento['pttoEjercido'], $totalImportes, 2), $movimiento['importe'], 2);
                    $totalImportes += $movimiento['importe'];
                }
                $this->cacheData[$key] = $movimiento;
            }
        }
    }

    #[On('finalizar-registros')]
    public function finalizarRegistros()
    {
        if (empty($this->cacheData)) {
            $this->dispatch('mostrarMensaje', mensaje: 'Tabla sin registros', tipo: 'error', tiempo: 3000);
            return;
        }

        try {
            $idUsuarioRegistrante = Auth::id();
            $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) as numero_poliza')
                ->where('tipo_poliza', '=', 'E')
                ->whereYear('fecha', '=', (string) $this->anio)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            sort($numerosPolizas);
            $this->numeroPoliza = (int)end($numerosPolizas) + 1;

            $this->numeroEvento = $this->dataCompleta[0]['evento'];

            $anioActual = $this->anio;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);

            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar un pagado del capítulo 4 con evento: ' . $this->numeroEvento, request());
            DB::beginTransaction();

            foreach ($this->dataCompleta as $movimiento) {
                $movimiento['importe'] = doubleval($movimiento['importe']);
                $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $movimiento['partidaId'])->whereIn('concepto_id', [40, 43, 46, 48, 49, 51])
                    ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();

                $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                    ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                    ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();

                $interaccionCuentaCuentasFiltradas = [];
                foreach ($interaccionCuentaCuentas as $cuenta) {
                    if ($cuenta['tipo_interaccion'] == 'Contable - Cargo') {
                        if ($cuenta['Codigo_cuenta'] == $movimiento['codigoCuentaRetenciones']) {
                            $interaccionCuentaCuentasFiltradas[] = $cuenta;
                            continue;
                        }
                    } else if ($cuenta['tipo_interaccion'] == 'Contable - Abono') {
                        if ($cuenta['Codigo_cuenta'] == $movimiento['codigoCuentaBanco']) {
                            $interaccionCuentaCuentasFiltradas[] = $cuenta;
                            continue;
                        }
                    } else {
                        $interaccionCuentaCuentasFiltradas[] = $cuenta;
                    }
                }

                $interaccionCuentaCuentas = $interaccionCuentaCuentasFiltradas;

                $polizas = [
                    [
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $movimiento['codigoAreaResponsable'],
                        'tipo_poliza' => 'E',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $movimiento['fechaAfectacion'],
                        'cuenta' => $movimiento['codigoPartida'],
                        'concepto' => $movimiento['descripcionPartida'],
                        'total' => abs($movimiento['importe']),
                        'mes' => $movimiento['mes'],
                        'descripcion' => $movimiento['observaciones'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $interaccionCuentaConceptoPrincipal->tipo_interaccion,
                        'validado' => false,
                        'estatus_evento' => EstatusEvento::ACTIVO->value,
                        'categoria' => 'EGRESOS PAGADO CAPITULO 4',
                        'documento_fuente' => $movimiento['documentoFuente'],
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]
                ];

                foreach ($interaccionCuentaCuentas as $key => $dataCuenta) {
                    array_push($polizas, [
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $movimiento['codigoAreaResponsable'],
                        'tipo_poliza' => 'E',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $movimiento['fechaAfectacion'],
                        'cuenta' => $dataCuenta['Codigo_cuenta'],
                        'concepto' => $dataCuenta['Descripcion_cuenta'],
                        'total' => $movimiento['importe'],
                        'mes' => $movimiento['mes'],
                        'descripcion' => $movimiento['observaciones'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $dataCuenta['tipo_interaccion'],
                        'validado' => false,
                        'estatus_evento' => EstatusEvento::ACTIVO->value,
                        'categoria' => 'EGRESOS PAGADO CAPITULO 4',
                        'documento_fuente' => $movimiento['documentoFuente'],
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }

                Poliza::insert($polizas);
            }

            $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) as numero_poliza')
                ->where('tipo_poliza', '=', 'EAUX')
                ->whereYear('fecha', '=', (string) $this->anio)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            sort($numerosPolizas);
            $this->numeroPolizaRemanente = (int)end($numerosPolizas) + 1;

            $polizasInicialesEgresosEjercido = Poliza::where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS EJERCIDO CAPITULO 4')
                ->where('evento', '=', $this->numeroEvento)
                ->whereYear('fecha', '=', (string) $this->anio)
                ->get();

            $polizasInicialesEgresosPagado = Poliza::where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS PAGADO CAPITULO 4')
                ->where('evento', '=', $this->numeroEvento)
                ->whereYear('fecha', '=', (string) $this->anio)
                ->where('concepto', 'LIKE', '%(Pagado)%')
                ->get();

            $polizasDevengado = Poliza::where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 4')
                ->where('evento', '=', $this->numeroEvento)
                ->whereYear('fecha', '=', (string) $this->anio)
                ->where('tipo_interaccion', '=', 'Contable - Abono')
                ->get();


            $polizasPagadoContableCargo =  Poliza::where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS PAGADO CAPITULO 4')
                ->where('evento', '=', $this->numeroEvento)
                ->whereYear('fecha', '=', (string) $this->anio)
                ->where('tipo_interaccion', '=', 'Contable - Cargo')
                ->get();




            $totalRemanente = DB::select('EXEC ImporteTotalCapitulo4Pagado @evento = ?, @anio = ?', array($this->numeroEvento, $this->anio))[0]->MontoDelEvento;
            if ($totalRemanente > 0) {



                $remanentesContables = [];
                foreach ($polizasDevengado as $devengado) {
                    $devengado->matchEncontrado = 0;
                    $conceptoCuentaDevengada = Poliza::where('cuentaRelacionada', '=', $devengado->cuentaRelacionada)->value('concepto');
                    $conceptoGeneralCuentaDevengada = explode('(', $conceptoCuentaDevengada);
                    $codigoCuentaPagada = Cuenta::where('Descripcion_cuenta', 'LIKE', '%' . $conceptoGeneralCuentaDevengada[0] . '(Pagado)' . '%')->value('Codigo_cuenta');
                    foreach ($polizasPagadoContableCargo as $index => $pagado) {
                        if ($pagado->cuentaRelacionada == $codigoCuentaPagada && $devengado->concepto == $pagado->concepto) {
                            // bcsub($operando1, $operando2, $escala_de_decimales)
                            $totalRemanente = bcsub((string)$devengado->total, (string)$pagado->total, 2);
                            $devengado->total = $totalRemanente;
                            $pagado->total = $totalRemanente;
                            $devengado->matchEncontrado = 1;

                            array_push($remanentesContables, $devengado->toArray());
                            array_push($remanentesContables, $pagado->toArray());

                            $polizasPagadoContableCargo->forget($index);
                        }
                    }
                }
                foreach ($polizasDevengado as $devengado) {
                    if ($devengado->matchEncontrado == 0) {
                        array_push($remanentesContables, $devengado->toArray());
                        $devengado->tipo_interaccion = 'Contable - Cargo';
                        array_push($remanentesContables, $devengado->toArray());
                    }
                }
                // dd($remanentesContables);




                foreach ($remanentesContables as $remanente) {
                    Poliza::create([
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $remanente['area'],
                        'tipo_poliza' => 'EAUX',
                        'numero_poliza' =>  $this->numeroPolizaRemanente,
                        'fecha' => $movimiento['fechaAfectacion'],
                        'cuenta' => $remanente['cuenta'],
                        'concepto' => $remanente['concepto'],
                        'total' => $remanente['total'],
                        'mes' => $remanente['mes'],
                        'descripcion' => $remanente['descripcion'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $remanente['tipo_interaccion'],
                        'validado' => false,
                        'estatus_evento' => EstatusEvento::FINALIZADO->value,
                        'categoria' => 'EGRESOS EJERCIDO CAPITULO 4 REMANENTE PAGADO',
                        'documento_fuente' => $movimiento['documentoFuente'],
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }







                foreach ($polizasInicialesEgresosEjercido as $polizaImporte) {
                    $clave = $polizaImporte->cuenta . '-' . $polizaImporte->concepto;
                    if (isset($resultado[$clave])) {
                        $resultado[$clave]['total'] += $polizaImporte['total'];
                    } else {
                        // Si la clave no existe, agregar el nuevo depósito al resultado
                        $resultado[$clave] = [
                            'idUsuarioRegistrante' => $idUsuarioRegistrante,
                            'area' => $polizaImporte->area,
                            'tipo_poliza' => 'EAUX',
                            'numero_poliza' =>  $this->numeroPolizaRemanente,
                            'fecha' => $movimiento['fechaAfectacion'],
                            'cuenta' => $polizaImporte->cuenta,
                            'concepto' => $polizaImporte->concepto,
                            'total' => $polizaImporte['total'],
                            'mes' => $polizaImporte->mes,
                            'descripcion' => $polizaImporte->descripcion,
                            'evento' => $this->numeroEvento,
                            'tipo_interaccion' => $polizaImporte->tipo_interaccion,
                            'validado' => false,
                            'estatus_evento' => EstatusEvento::FINALIZADO->value,
                            'categoria' => 'EGRESOS EJERCIDO CAPITULO 4 REMANENTE PAGADO',
                            'documento_fuente' => $movimiento['documentoFuente'],
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ];
                    }
                }
                // dd($resultado);

                foreach ($resultado as $polizaInicial) {
                    $total = $polizaInicial['total'];
                    foreach ($polizasInicialesEgresosPagado as $polizaPagado) {
                        $conceptoGeneral = explode('(', $polizaPagado->concepto);

                        if (str_contains($polizaInicial['concepto'], rtrim($conceptoGeneral[0])) !== false && $conceptoGeneral[1] == 'Pagado)') {
                            $total = bcsub((string)$total, (string)$polizaPagado['total'], 2);
                        }
                    }
                    Poliza::create([
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $polizaInicial['area'],
                        'tipo_poliza' => 'EAUX',
                        'numero_poliza' =>  $this->numeroPolizaRemanente,
                        'fecha' => $movimiento['fechaAfectacion'],
                        'cuenta' => $polizaInicial['cuenta'],
                        'concepto' => $polizaInicial['concepto'],
                        'total' => $total,
                        'mes' => $polizaInicial['mes'],
                        'descripcion' => $polizaInicial['descripcion'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $polizaInicial['tipo_interaccion'],
                        'validado' => false,
                        'estatus_evento' => EstatusEvento::FINALIZADO->value,
                        'categoria' => 'EGRESOS EJERCIDO CAPITULO 4 REMANENTE PAGADO',
                        'documento_fuente' => $movimiento['documentoFuente'],
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }
            } else {
                $this->numeroPolizaRemanente = 0;
            }


            $importeTotalEvento = DB::select('EXEC ImporteTotalCapitulo4Pagado @evento = ?, @anio = ?', [$this->numeroEvento, $this->anio]);
            if ($importeTotalEvento[0]->MontoDelEvento == 0) {
                Poliza::where('evento', '=', $this->numeroEvento)
                    ->whereYear('fecha', '=', (string) $this->anio)
                    ->whereIn('categoria', ['EGRESOS EJERCIDO CAPITULO 4', 'EGRESOS PAGADO CAPITULO 4'])
                    ->update(['estatus_evento' => EstatusEvento::FINALIZADO->value]);
            }

            DB::commit();
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total, $this->numeroPolizaRemanente);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en pagado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }


    public function changeState($value) {}
}
