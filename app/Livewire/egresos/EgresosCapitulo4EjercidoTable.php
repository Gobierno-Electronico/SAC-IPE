<?php

namespace App\Livewire\egresos;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Clases\Column;
use Livewire\Attributes\On;
use App\Livewire\Tabla;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Poliza;

class EgresosCapitulo4EjercidoTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    
    public function render()
    {
        return view('livewire.egresos.egresos-capitulo4-ejercido-table');
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
            Column::make('partida', 'Partida'),
            Column::make('mes', 'Mes'),
            Column::make('movimiento', 'Movimiento'),
            Column::make('pptoDevengado', 'PPTO Devengado')->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    public function edit($id)
    {
        {
            try {
                //code...
                $this->recalcularDisponibilidad($id);
                foreach ($this->dataCompleta as $key => $registro) {
                    if ($registro['id'] == $id) {
                        $datosRegistro = [
                            'area' => $registro['areaResponsableId'],
                            'cuenta' => $registro['cuentaId'],
                            'cuentaPago' => $registro['cuentaPagoId'],
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
                $this->total = $totalActualizado;
                $this->dispatch('cambioTotal', total: $totalActualizado);
            } catch (\Throwable $th) {
                Log::error('Ocurrió un error al editar en ejercido: '. $th->getMessage());
                $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
            }
        }
    }

    public function delete($id)
    {
        try {
            //code...
            $this->recalcularDisponibilidad($id);
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
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al eliminar en ejercido: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al eliminar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function changeState($value)
    {

    }
}