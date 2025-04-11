<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use App\Clases\Column;
use App\Http\Controllers\BitacoraController;
use Carbon\Carbon;
use Log;
use DB;
use App\Models\Cuenta;
use App\Models\CodigoDepartamento;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class RegistroPolizaDiarioTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $totalCargo = 0;
    public $totalAbono = 0;
    public $numeroPoliza;
    public $numeroEvento;

    public function render()
    {
        return view('livewire.registro-poliza-diario-table');
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
            Column::make('cuenta', 'Cuenta'),
            Column::make('tipoInteraccion', 'Tipo de interacción'),
            Column::make('importeCargo', 'Importe cargo')->component('columns.importe'),
            Column::make('importeAbono', 'Importe abono')->component('columns.importe'),
            Column::make('mes', 'Mes'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    public function edit($id)
    {
        try {
            foreach ($this->dataCompleta as $key => $registro) {
                if ($registro['id'] == $id) {
                    $datosRegistro = [
                        'cuenta' => $registro['idCuenta'],
                        'tipoInteraccion' => $registro['tipoInteraccion'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe']
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
            // Recalculamos los totales solo después de eliminar el registro
            $totalCargoActualizado = array_sum(array_column(
                array_filter($this->cacheData, fn($item) => $item['tipoInteraccion'] == 'Contable - Cargo'),
                'importe'
            ));

            $totalAbonoActualizado = array_sum(array_column(
                array_filter($this->cacheData, fn($item) => $item['tipoInteraccion'] == 'Contable - Abono'),
                'importe'
            ));

            $this->totalCargo = $totalCargoActualizado;
            $this->totalAbono = $totalAbonoActualizado;
            $this->dispatch('cambioTotal', totalCargo: $totalCargoActualizado, totalAbono: $totalAbonoActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al editar en poliza diario: ' . $th->getMessage());
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
            // Recalculamos los totales solo después de eliminar el registro
            $totalCargoActualizado = array_sum(array_column(
                array_filter($this->cacheData, fn($item) => $item['tipoInteraccion'] == 'Contable - Cargo'),
                'importe'
            ));

            $totalAbonoActualizado = array_sum(array_column(
                array_filter($this->cacheData, fn($item) => $item['tipoInteraccion'] == 'Contable - Abono'),
                'importe'
            ));

            $this->totalCargo = $totalCargoActualizado;
            $this->totalAbono = $totalAbonoActualizado;
            $this->dispatch('cambioTotal', totalCargo: $totalCargoActualizado, totalAbono: $totalAbonoActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al eliminar en poliza diario: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al eliminar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function changeState($value) {}

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try {

            $importeCargo = 0;
            $importeAbono = 0;
            if($registro['tipoInteraccion'] == 'Contable - Cargo'){
                $importeCargo = $registro['importe'];
            }else{
                $importeAbono = $registro['importe'];
            }
            $nuevoRegistro = [
                'id' => 0,
                'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                'cuenta' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                'tipoInteraccion' => $registro['tipoInteraccion'],
                'mes' => $registro['mes'],
                'movimiento' => 'DIVERSOS CONCEPTOS',
                'importe' => $registro['importe'],
                'importeCargo' => $importeCargo,
                'importeAbono' => $importeAbono
            ];

            array_push($this->cacheData, $nuevoRegistro);
            array_push($this->dataCompleta, $registro);
            $this->totalCargo = 0;
            $this->totalAbono = 0;
            foreach ($this->cacheData as $key => $registro) {
                $this->cacheData[$key]['id'] = $key + 1; // El ID comienza en 1
                $this->dataCompleta[$key]['id'] = $key + 1;
                if ($registro['tipoInteraccion'] == 'Contable - Cargo') {
                    $this->totalCargo += $registro['importe'];
                } else {
                    $this->totalAbono += $registro['importe'];
                }
            }
            $this->dispatch('cambioTotal', totalCargo: $this->totalCargo, totalAbono: $this->totalAbono);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en poliza diario: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    #[On('finalizar-registros')]
    public function finalizarRegistros()
    {
        if (empty($this->cacheData)) {
            $this->dispatch('mostrarMensaje', mensaje: 'Tabla sin registros', tipo: 'error', tiempo: 3000);
            return;
        }
        if ($this->totalCargo != $this->totalAbono) {
            $this->dispatch('mostrarMensaje', mensaje: 'Los totales Cargo y Abono deben estar balanceados', tipo: 'error', tiempo: 3000);
            return;
        }

        try {
            $idUsuarioRegistrante = Auth::id();
            $numerosPolizas = Poliza::select('numero_poliza')
                ->where('tipo_poliza', '=', 'D')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            sort($numerosPolizas);
            $this->numeroPoliza = (int)end($numerosPolizas) + 1;

            $numerosEvento = Poliza::select('evento')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('evento')
                ->pluck('evento')
                ->toArray();
            sort($numerosEvento);
            $this->numeroEvento = (int)end($numerosEvento) + 1;

            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar una poliza diario con evento: ' . $this->numeroEvento, request());

            DB::beginTransaction();

            $anioActual = Carbon::now()->year;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);

            $polizas = [];
            foreach ($this->dataCompleta as $movimiento) {
                array_push($polizas, [
                    'idUsuarioRegistrante' => $idUsuarioRegistrante,
                    'area' => $movimiento['codigoAreaResponsable'],
                    'tipo_poliza' => 'D',
                    'numero_poliza' =>  $this->numeroPoliza,
                    'fecha' => $movimiento['fechaAfectacion'],
                    'cuenta' => $movimiento['codigoCuenta'],
                    'concepto' => $movimiento['descripcionCuenta'],
                    'total' => abs($movimiento['importe']),
                    'mes' => $movimiento['mes'],
                    'descripcion' => $movimiento['observaciones'],
                    'evento' => $this->numeroEvento,
                    'tipo_interaccion' => $movimiento['tipoInteraccion'],
                    'validado' => false,
                    'estatus_evento' => true,
                    'categoria' => 'DIARIO DIVERSOS CONCEPTOS',
                    'created_at' => $fecha,
                    'updated_at' => $fecha
                ]);
            }
            Poliza::insert($polizas);
            DB::commit();
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->totalCargo, $this->totalAbono);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en Poliza Diario: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}
