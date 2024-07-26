<?php

namespace App\Livewire;
use Livewire\Attributes\On;
use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use App\Clases\Column;
use App\Http\Controllers\BitacoraController;
use Illuminate\Pagination\LengthAwarePaginator;

class IngresosRecaudadoTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $numeroPoliza;
    public $numeroEvento;

    public function render(){
        return view('livewire.ingresos-recaudado-table');
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
            Column::make('', 'Area'),
            Column::make('', 'Partida'),
            Column::make('', 'Mes'),
            Column::make('', 'Movimiento'),
            Column::make('', 'PPTO por ejecutar'),
            Column::make('', 'Importe')->component('columns.importe'),
            Column::make('', 'Disponibilidad'),
            Column::make('', 'Remanente'),
            Column::make('', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    public function edit($id)
    {
        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                $datosRegistro = [
                    'area' => $registro['areaResponsableId'],
                    'cuenta' => $registro['cuentaId'],
                    'mes' => $registro['mes'],
                    'importe' => $registro['importe']
                ];

                unset($this->dataCompleta[$key]);
                $this->dispatch('llenar-formulario', $datosRegistro);
                break;
            }
        }

        foreach ($this->cacheData as $key => $registro) {
            if ($registro['id'] == $id) {
                unset($this->cacheData[$key]);
                break;
            }
        }
        // Recalculamos los totales solo después de eliminar el registro
        $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
        $this->dispatch('cambioTotal', total: $totalActualizado);
    }

    public function delete($id)
    {
        foreach ($this->cacheData as $key => $registro) {
            if ($registro['id'] == $id) {
                unset($this->cacheData[$key]);
                break;
            }
        }

        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                unset($this->dataCompleta[$key]);
                break;
            }
        }
        // Recalculamos los totales solo después de eliminar el registro
        $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
        $this->dispatch('cambioTotal', total: $totalActualizado);
    }

    public function changeState($value)
    {
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        if ($this->total + $registro['importe'] > $registro['montoEvento']) {
            $this->dispatch('mostrarMensaje', mensaje: 'Monto total del evento superado', tipo: 'error', tiempo: 3000);
            return;
        }
        $anioActual = Carbon::now()->year;
    }
}
