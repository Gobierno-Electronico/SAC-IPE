<?php

namespace App\Livewire;

use App\Clases\Column;
use App\Models\ClasificadorDeConcepto;
use Illuminate\Database\Eloquent\Builder;

class GuiaContabilizadoraClasificadoresTable extends Tabla
{
    public $perPage = 10;

    public $sortBy = '';

    public $selectedClasificadorDeConcepto = '';



    public $searchBy = ['codigo_clasificador', 'descripcion_clasificador'];

    public function query(): Builder
    {
        return ClasificadorDeConcepto::query();
    }

    public function columns(): array
    {
        return [
            Column::make('codigo_clasificador', 'Codigo'),
            Column::make('descripcion_clasificador', 'Descripción'),
            Column::make('id','Seleccionar')->component('acciones.seleccion'),

        ];
    }

    public function edit($value)
    {
    }

    public function changeState($value)
    {
       
    }

}
