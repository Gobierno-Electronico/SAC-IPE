<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use DB;
use Log;
use App\Clases\Column;
use App\Http\Controllers\BitacoraController;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use App\Models\Cuenta;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use App\Enums\EstatusEvento;

class IngresosDevengadoPrevRecaudadoEjerciciosAnterioresTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $numeroPoliza;
    public $numeroEvento;
    public $numeroPolizaRemanente;
    public $totalRegistrosPorCuentaPago = 0;
    public $totalDisponible = 0;

    public function render()
    {
        return view('livewire.ingresos-devengado-prev-recaudado-ejercicios-anteriores-table');
    }

    public function query(): Builder
    {
        return Poliza::query();
    }

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
            Column::make('cuentaPago', 'Cuenta de pago'),
            Column::make('mes', 'Mes'),
            Column::make('movimiento', 'Movimiento'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    public function edit($id)
    {
        try {
            foreach ($this->dataCompleta as $key => $registro) {
                if ($registro['id'] == $id) {
                    $datosRegistro = [
                        'area' => $registro['areaResponsableId'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe'],
                        'cuentaPago' => $registro['cuentaPagoId'],
                        'solvenciaAbono' => $registro['solvenciaAbono']
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
            Log::error('Ocurrió un error al editar en Devengado previamente recaudado ejercicios anteriores: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function delete($id)
    {
        try {
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
            Log::error('Ocurrió un error al eliminar en Devengado previamente recaudado ejercicios anteriores: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al eliminar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function recalcularDisponibilidad($id)
    {
        $datosSeleccionado = [];
        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                $datosSeleccionado = [
                    'codigoArea' => $registro['codigoAreaResponsable'],
                    'codigoCuenta' => $registro['codigoCuenta'],
                    'mes' => $registro['mes'],
                    'evento' => $registro['evento']
                ];
            }
        }

        $totalImportes = 0;
        foreach ($this->cacheData as $key => $movimiento) {
            if ($movimiento['id'] != $id && str_contains($movimiento['area'], $datosSeleccionado['codigoArea']) && str_contains($movimiento['partida'], $datosSeleccionado['codigoCuenta']) && $movimiento['mes'] == $datosSeleccionado['mes'] && $movimiento['evento'] == $datosSeleccionado['evento']) {
                if ($totalImportes == 0) {
                    $movimiento['disponibilidad'] = bcsub($movimiento['ejecutar'], $movimiento['importe'], 2);
                    $totalImportes += $movimiento['importe'];
                } else {
                    $movimiento['disponibilidad'] = bcsub(bcsub($movimiento['ejecutar'], $totalImportes, 2), $movimiento['importe'], 2);
                    $totalImportes += $movimiento['importe'];
                }
                $this->cacheData[$key] = $movimiento;
            }
        }
    }

    public function changeState($value) {}

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try {
            if($this->verificarPresupuesto($registro['solvenciaAbono'], $registro['importe'], $registro['codigoCuentaPago'])){
                
                if (bccomp((string)($this->total + $registro['importe']), (string)$registro['montoPorClasificar'], 2) == 1) {
                    $this->dispatch('mostrarMensaje', mensaje: 'Solvencia por clasificar insuficiente', tipo: 'error', tiempo: 3000);
                    return;
                }
    
    
                $nuevoRegistro = [
                    'id' => 0,
                    'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                    'cuentaPago' => $registro['codigoCuentaPago'] . ' ' . $registro['descripcionCuentaPago'],
                    'mes' => $registro['mes'],
                    'movimiento' => 'DEVENGADO PREVIAMENTE RECAUDADO EJERCICIOS ANTERIORES',
                    'importe' => $registro['importe'],
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
                Log::error('Ocurrió un error al agregar registro en Devengado previamente recaudado ejercicios anteriores: ' . $th->getMessage());
                $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
            }
    }

    public function verificarPresupuesto($solvencia, $importe, $codigoCuenta)
    {
        $this->totalDisponible = $solvencia - $importe;
        $totalImportes = 0;

        foreach ($this->cacheData as $movimiento){
            if(str_contains($movimiento['cuentaPago'], $codigoCuenta)){
                $totalImportes += $movimiento['importe'];
            }
        }

        if($totalImportes > 0){
            $this->totalDisponible = bcsub(bcsub($solvencia, $totalImportes, 2), $importe, 2);
        }

                if($this->totalDisponible < 0){
            $this->dispatch('mostrarMensaje', mensaje: 'Solvencia insuficiente', tipo: 'warning', tiempo: 3000);
            return false;
        }
        return true;
    }

    public function sumarRegistrosPorCuentaPago($registro)
    {
        $this->totalRegistrosPorCuentaPago = 0;
        foreach ($this->dataCompleta as $key => $movimiento) {
            if ($registro['codigoCuentaPago'] == $movimiento['codigoCuentaPago']) {
                $this->totalRegistrosPorCuentaPago += $movimiento['importe'];
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
                ->where('tipo_poliza', '=', 'D')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            sort($numerosPolizas);
            $this->numeroPoliza = (int)end($numerosPolizas) + 1;

            $numerosEvento = Poliza::selectRaw('CAST(evento AS INT) as evento')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('evento')
                ->pluck('evento')
                ->toArray();
            sort($numerosEvento);
    
            $this->numeroEvento = (int)end($numerosEvento) + 1;
 
            $anioActual = Carbon::now()->year;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);
            $cuentaIngresosClasificar = Cuenta::where('Codigo_cuenta', '2.1.9.1.01.01')->get();

            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar un devengado previamente recaudado con evento: ' . $this->numeroEvento, request());

            DB::beginTransaction();


            foreach ($this->dataCompleta as $movimiento) {
                $importeMovimiento = doubleval($movimiento['importe']);
                
                $polizas = [
                    [
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $movimiento['codigoAreaResponsable'],
                        'tipo_poliza' => 'D',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $movimiento['fechaAfectacion'],
                        'cuenta' => $movimiento['codigoCuentaPago'],
                        'concepto' => $movimiento['descripcionCuentaPago'],
                        'total' => abs($importeMovimiento),
                        'mes' => $movimiento['mes'],
                        'descripcion' => $movimiento['observaciones'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => 'Contable - Abono',
                        'validado' => false,
                        'estatus_evento' => EstatusEvento::ACTIVO->value,
                        'categoria' => 'INGRESOS DEVENGADO PREVIAMENTE RECAUDADO EJERCICIOS ANTERIORES',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ],

                    [
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $movimiento['codigoAreaResponsable'],
                        'tipo_poliza' => 'D',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $movimiento['fechaAfectacion'],
                        'cuenta' => $cuentaIngresosClasificar[0]->Codigo_cuenta,
                        'concepto' => $cuentaIngresosClasificar[0]->Descripcion_cuenta,
                        'total' => abs($importeMovimiento),
                        'mes' => $movimiento['mes'],
                        'descripcion' => $movimiento['observaciones'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => 'Contable - Cargo',
                        'validado' => false,
                        'estatus_evento' => EstatusEvento::ACTIVO->value,
                        'categoria' => 'INGRESOS DEVENGADO PREVIAMENTE RECAUDADO EJERCICIOS ANTERIORES',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]
                ];
               
                Poliza::insert($polizas);
            }

                Poliza::where('evento', '=', $this->numeroEvento)
                    ->whereIn('categoria', ['INGRESOS DEVENGADO PREVIAMENTE RECAUDADO EJERCICIOS ANTERIORES'])
                    ->update(['estatus_evento' => EstatusEvento::FINALIZADO->value]);
          
            DB::commit();
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total, $this->numeroPolizaRemanente);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en devengado previamente recaudado ejercicios anteriores: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}
