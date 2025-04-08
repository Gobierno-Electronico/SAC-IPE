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
            Column::make('importe', 'Importe')->component('columns.importe'),
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
            $nuevoRegistro = [
                'id' => 0,
                'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                'cuenta' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                'tipoInteraccion' => $registro['tipoInteraccion'],
                'mes' => $registro['mes'],
                'movimiento' => 'DIVERSOS CONCEPTOS',
                'importe' => $registro['importe'],

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
    public function finalizarRegistros() {
        if (empty($this->cacheData)) {
            $this->dispatch('mostrarMensaje', mensaje: 'Tabla sin registros', tipo: 'error', tiempo: 3000);
            return;
        }

        try {
            
        } catch (\Throwable $th) {
            
        }
    }
}
