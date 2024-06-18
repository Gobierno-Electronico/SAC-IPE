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

    public function edit($value)
    {
    }

    public function delete($value){
    }

    public function changeState($value)
    {
    }
}
