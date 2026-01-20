<?php

namespace App\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Clases\Column;
use Livewire\Attributes\On;
use App\Livewire\Tabla;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\BitacoraController;
use App\Models\Poliza;
use Carbon\Carbon;
use App\Enums\EstatusEvento;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use Log;
use DB;

class EgresosCapitulo1DevengadoTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $totalDisponible = 0;
    public $numeroEvento;
    public $numeroPolizaRemanente;
    public $importeRestante = 0;
    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }

    public function render()
    {
        return view('livewire.egresos-capitulo1-devengado-table');
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
            Column::make('cuentaContable', 'Cuenta contable'),
            Column::make('mes', 'Mes'),
            Column::make('movimiento', 'Movimiento'),
            Column::make('pttoComprometido', 'PPTO Comprometido')->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('importeAbono', 'Importe abono')->component('columns.importe'),
            Column::make('importeRestante', 'Importe restante')->component('columns.importe'),
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

            if ($this->verificarPresupuesto($registro)) {
                $nuevoRegistro = [
                    'id' => 0,
                    'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                    'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                    'cuentaContable' => $registro['codigoCuentaAbono'] . ' ' . $registro['descripcionCuentaAbono'],
                    'mes' => $registro['mes'],
                    'movimiento' => 'DEVENGADO',
                    'pttoComprometido' => $registro['pttoComprometido'],
                    'importe' => $registro['importe'],
                    'importeAbono' => $registro['importeAbono'],
                    'importeRestante' => $this->importeRestante,
                    'documentoFuente' => $registro['documentoFuente'],
                ];
                array_push($this->cacheData, $nuevoRegistro);
                array_push($this->dataCompleta, $registro);
                $this->total = 0;
                foreach ($this->cacheData as $key => $registro) {
                    $this->cacheData[$key]['id'] = $key + 1;
                    $this->dataCompleta[$key]['id'] = $key + 1;
                    $this->total += $registro['importeAbono'];
                }
                $this->dispatch('cambioTotal', total: $this->total);
            }
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en devengado del capítulo 1: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarPresupuesto($registro)
    {
        $solvencia = $registro['pttoComprometido'] - $registro['importe'];
        $this->importeRestante = $registro['importe'] - $registro['importeAbono']; 
        $totalImportes = 0;

        foreach ($this->cacheData as $movimiento) {
            if (str_contains($movimiento['area'], $registro['codigoAreaResponsable']) && str_contains($movimiento['partida'], $registro['codigoCuenta']) && $movimiento['mes'] == $registro['mes']) {
                $totalImportes += $movimiento['importeAbono'];
            }
        }

        if ($totalImportes > 0) {
            $this->importeRestante = bcsub(bcsub($registro['importe'], $totalImportes, 2), $registro['importeAbono'], 2);
        }

        if ($this->importeRestante < 0) {
            $this->dispatch('mostrarMensaje', mensaje: 'La suma de los importes abono no puede ser mayor al importe general', tipo: 'warning', tiempo: 3000);
            return false;
        }

        if ($solvencia < 0) {
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar insuficiente', tipo: 'warning', tiempo: 3000);
            return false;
        }

        return true;
    }

    public function verificarBalance(){
        $totalImportesAbonos = array_sum(array_column($this->cacheData, 'importeAbono'));

        $importesTotalesUnicos = [];
        $totalImportes = 0;

        foreach ($this->cacheData as $movimiento) {
            $clave = $movimiento['area'].'-'.$movimiento['partida'].'-'.$movimiento['mes'];
            if (!isset($importesTotalesUnicos[$clave])) {
                $importesTotalesUnicos[$clave] = $movimiento['importe'];
            }
        }

        $totalImportes = array_sum($importesTotalesUnicos);

        if($totalImportesAbonos < $totalImportes){
            $this->dispatch('mostrarMensaje', mensaje: 'La póliza no está balanceada', tipo: 'warning', tiempo: 3000);
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
                        'partida' => $registro['cuentaId'],
                        'cuentaContable' => $registro['cuentaAbonoId'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe'],
                        'importeAbono' => $registro['importeAbono'],
                        'pttoComprometido' => $registro['pttoComprometido'],
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
            Log::error('Ocurrió un error al editar en devengado del capítulo 1: ' . $th->getMessage());
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
            Log::error('Ocurrió un error al eliminar en devengado del capítulo 1: ' . $th->getMessage());
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
                    'codigoCuentaPartida' => $registro['codigoCuenta'],
                    'mes' => $registro['mes']
                ];
            }
        }

        $totalImportes = 0;
        foreach ($this->cacheData as $key => $movimiento) {
            if ($movimiento['id'] != $id && str_contains($movimiento['area'], $datosSeleccionado['codigoArea']) && str_contains($movimiento['partida'], $datosSeleccionado['codigoCuentaPartida']) && $movimiento['mes'] == $datosSeleccionado['mes']) {
                if ($totalImportes == 0) {
                    $movimiento['disponibilidad'] = bcsub($movimiento['pttoComprometido'], $movimiento['importe'], 2);
                    $totalImportes += $movimiento['importe'];
                } else {
                    $movimiento['dispibilidad'] = bcsub(bcsub($movimiento['pttoComprometido'], $totalImportes, 2), $movimiento['importe'], 2);
                    $totalImportes += $movimiento['importe'];
                }
                $this->cacheData[$key] = $movimiento;
            }
        }
    }

    #[On('finalizar-registros')]
    public function finalizarRegistros()
    {
        set_time_limit(30000);
        ini_set('max_execution_time', 30000);

        if(!$this->verificarBalance()){
            return;
        };

        if (empty($this->cacheData)) {
            $this->dispatch('mostrarMensaje', mensaje: 'Tabla sin registros', tipo: 'error', tiempo: 3000);
            return;
        }

        try {
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
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar un devengado del capítulo 1 con evento: ' . $this->numeroEvento, request());
            DB::beginTransaction();

            foreach ($this->dataCompleta as $movimiento) {
                $movimiento['importe'] = doubleval($movimiento['importe']);
                $movimiento['importeAbono'] = doubleval($movimiento['importeAbono']);
                
                $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $movimiento['cuentaId'])->whereIn('concepto_id', [10102])
                    ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();

                $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                    ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                    ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();

                $interaccionCuentaCuentasFiltradas = [];
                $interaccionCuentaCuentasContable = [];
                $polizas = [];
                foreach ($interaccionCuentaCuentas as $cuenta) {
                    if ($cuenta['tipo_interaccion'] == 'Contable - Abono') {
                        if ($cuenta['Codigo_cuenta'] == $movimiento['codigoCuentaAbono']) {
                            $interaccionCuentaCuentasContable[] = $cuenta;
                            continue;
                        }
                    } else {
                        $interaccionCuentaCuentasFiltradas[] = $cuenta;
                    }
                }

                $interaccionCuentaCuentas = $interaccionCuentaCuentasFiltradas; 

                $polizaPrincipalRegistrada = Poliza::where('cuenta', '=', $movimiento['codigoCuenta'])
                ->where('evento', '=', $this->numeroEvento)
                ->where('numero_poliza', '=', $this->numeroPoliza)
                ->where('tipo_poliza', '=', 'E')
                ->where('area', '=', $movimiento['codigoAreaResponsable'])
                ->where('mes', '=', $movimiento['mes'])
                ->where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 1')
                ->get();

                if($polizaPrincipalRegistrada->isEmpty()){
                    $polizas = [
                        [
                            'area' => $movimiento['codigoAreaResponsable'],
                            'tipo_poliza' => 'E',
                            'numero_poliza' =>  $this->numeroPoliza,
                            'fecha' => $movimiento['fechaAfectacion'],
                            'cuenta' => $movimiento['codigoCuenta'],
                            'concepto' => $movimiento['descripcionCuenta'],
                            'total' => abs($movimiento['importe']),
                            'mes' => $movimiento['mes'],
                            'descripcion' => $movimiento['observaciones'],
                            'evento' => $this->numeroEvento,
                            'tipo_interaccion' => $interaccionCuentaConceptoPrincipal->tipo_interaccion,
                            'validado' => false,
                            'estatus_evento' => true,
                            'categoria' => 'EGRESOS DEVENGADO CAPITULO 1',
                            'documento_fuente' => $movimiento['documentoFuente'],
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ]
                    ];

                    foreach ($interaccionCuentaCuentas as $key => $dataCuenta) {
                        array_push($polizas, [
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
                            'estatus_evento' => true,
                            'categoria' => 'EGRESOS DEVENGADO CAPITULO 1',
                            'documento_fuente' => $movimiento['documentoFuente'],
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ]);
                    }
               }

               foreach ($interaccionCuentaCuentasContable as $key => $dataCuenta) {
                    array_push($polizas, [
                        'area' => $movimiento['codigoAreaResponsable'],
                        'tipo_poliza' => 'E',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $movimiento['fechaAfectacion'],
                        'cuenta' => $dataCuenta['Codigo_cuenta'],
                        'concepto' => $dataCuenta['Descripcion_cuenta'],
                        'total' => $movimiento['importeAbono'],
                        'mes' => $movimiento['mes'],
                        'descripcion' => $movimiento['observaciones'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $dataCuenta['tipo_interaccion'],
                        'validado' => false,
                        'estatus_evento' => true,
                        'categoria' => 'EGRESOS DEVENGADO CAPITULO 1',
                        'documento_fuente' => $movimiento['documentoFuente'],
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }

               Poliza::insert($polizas);

            }

           /*  $numerosPolizas = Poliza::select('numero_poliza')
                ->where('tipo_poliza', '=', 'EAUX')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            sort($numerosPolizas);
            $this->numeroPolizaRemanente = (int)end($numerosPolizas) + 1;

            $polizasInicialesEgresosComprometido = Poliza::where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 1')
                ->where('evento', '=', $this->numeroEvento)
                ->get();

            $polizasInicialesEgresosDevengado = Poliza::where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 1')
                ->where('evento', '=', $this->numeroEvento)
                ->where('concepto', 'LIKE', '%(Devengado)%')
                ->get();

            $totalRemanente = DB::select('EXEC ImporteTotalCapitulo1Devengado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            if ($totalRemanente > 0) {
                foreach ($polizasInicialesEgresosComprometido as $polizaImporte) {
                    $clave = $polizaImporte->cuenta . '-' . $polizaImporte->concepto;
                    if (isset($resultado[$clave])) {
                        $resultado[$clave]['total'] += $polizaImporte['total'];
                    } else {
                        // Si la clave no existe, agregar el nuevo depósito al resultado
                        $resultado[$clave] = [
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
                            'estatus_evento' => false,
                            'categoria' => 'EGRESOS COMPROMETIDO CAPITULO 1 REMANENTE DEVENGADO',
                            'documento_fuente' => $movimiento['documentoFuente'],
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ];
                    }
                }

                foreach ($resultado as $polizaInicial) {
                    $total = $polizaInicial['total'];
                    foreach ($polizasInicialesEgresosDevengado as $polizaDevengado) {
                        $conceptoGeneral = explode('(', $polizaDevengado->concepto);

                        if (str_contains($polizaInicial['concepto'], rtrim($conceptoGeneral[0])) !== false && $conceptoGeneral[1] == 'Devengado)') {
                            $total = $total - $polizaDevengado['total'];
                        }
                    }
                    Poliza::create([
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
                        'estatus_evento' => false,
                        'categoria' => 'EGRESOS COMPROMETIDO CAPITULO 1 REMANENTE DEVENGADO',
                        'documento_fuente' => $movimiento['documentoFuente'],
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }
            } else {
                $this->numeroPolizaRemanente = 0;
            }

            $importeTotalEvento = DB::select('EXEC ImporteTotalCapitulo1Devengado @evento = ?', [$this->numeroEvento]);
            if ($importeTotalEvento[0]->MontoDelEvento == 0) {
                Poliza::where('evento', '=', $this->numeroEvento)
                    ->whereIn('categoria', ['EGRESOS COMPROMETIDO CAPITULO 1'])
                    ->update(['estatus_evento' => false]);
            } */

            $this->liberarRemanente();

            DB::commit();
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total, $this->numeroPolizaRemanente); 
         } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en devengado del capítulo 1: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        } 
    }

     private function liberarRemanente(){
        set_time_limit(30000);
        ini_set('max_execution_time', 30000);
        $eventoActual = $this->numeroEvento;

        $polizasCompromiso = Poliza::where('evento', $eventoActual)
            ->where('categoria', 'EGRESOS COMPROMETIDO CAPITULO 1')
            ->get();

        $polizasDevengado = Poliza::where('evento', $eventoActual)
            ->where('categoria', 'EGRESOS DEVENGADO CAPITULO 1')
            ->where('concepto', 'LIKE', '%Devengado%')
            ->get();

        $indexDevengado = [];
        foreach ($polizasDevengado as $polizaDevengado) {
            $concepto = trim(explode('(', $polizaDevengado->concepto)[0]);
            $key = $polizaDevengado->area . '|' . $polizaDevengado->mes . '|' . $concepto;
            $indexDevengado[$key] = $polizaDevengado;
        }

        $updatesFinalizado = [];
        $updatesActivo = [];

        foreach ($polizasCompromiso as $polizaComprometida) {
            $concepto = trim(explode('(', $polizaComprometida->concepto)[0]);
            $key = $polizaComprometida->area . '|' . $polizaComprometida->mes . '|' . $concepto;

            if (isset($indexDevengado[$key])) {
                $dev = $indexDevengado[$key];
                $nuevoTotal = min($dev->total, $polizaComprometida->total);

                $updatesFinalizado[] = [
                    'id' => $polizaComprometida->id,
                    'total' => $nuevoTotal,
                    'estatus_evento' => EstatusEvento::FINALIZADO->value,
                ];
            } else {
                $updatesActivo[] = [
                    'id' => $polizaComprometida->id,
                    'evento' => $polizaComprometida->evento + 1,
                    'estatus_evento' => EstatusEvento::ACTIVO->value,
                ];
            }
        }

        foreach ($updatesFinalizado as $data) {
            Poliza::where('id', $data['id'])->update([
                'total' => $data['total'],
                'estatus_evento' => $data['estatus_evento'],
            ]);
        }

        foreach ($updatesActivo as $data) {
            Poliza::where('id', $data['id'])->update([
                'evento' => $data['evento'],
                'estatus_evento' => $data['estatus_evento'],
            ]);
        }
    }



    public function changeState($value) {}
}
