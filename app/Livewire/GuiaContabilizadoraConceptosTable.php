<?php

namespace App\Livewire;

use App\Clases\Column;
use App\Models\ClasificadorDeConcepto;
use App\Models\Concepto;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class GuiaContabilizadoraConceptosTable extends Tabla
{
    public $perPage = 10;

    public $sortBy = '';

    public $clasificadorSeleccionado = '';

    public $searchBy = ['descripcion', 'documento_fuente', 'periodicidad'];

    public function query(): Builder
    {
        return Concepto::query();
    }

    public function columns(): array
    {
        if ($this->clasificadorSeleccionado != '') {

            return [
                Column::make('descripcion', 'Descripción'),
                Column::make('documento_fuente', 'Documento fuente'),
                Column::make('periodicidad', 'Periodicidad'),
                Column::make('id', 'Seleccionar')->component('acciones.seleccionConcepto'),

            ];
        } else {
            return [
                Column::make('descripcion', 'Descripción'),
                Column::make('documento_fuente', 'Documento fuente'),
                Column::make('periodicidad', 'Periodicidad'),
               

            ];
        }
    }


    public function data()
    {
        return $this
            ->query()
            ->when($this->sortBy !== '', function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })->search($this->searchBy, $this->searchTerm)
            ->interaccion('relacion_concepto_clasificadors', 'conceptos.id', 'concepto_id', 'clasificador_de_concepto_id', $this->clasificadorSeleccionado)
            ->select('conceptos.descripcion', 'conceptos.documento_fuente', 'conceptos.periodicidad', 'conceptos.id')
            ->paginate($this->perPage);
    }

    public function edit($value)
    {
    }

    public function changeState($value)
    {

    }

    #[On('clasificadorSeleccionado')]
    public function buscarPorClasificador($value)
    {
        $this->clasificadorSeleccionado = $value;
    }
}
