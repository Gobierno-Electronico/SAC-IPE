<?php

namespace App\Livewire;

use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Clases\Column;
use App\Http\Controllers\BitacoraController;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class ConsultaAmpliacionesReduccionesTable extends Tabla
{
    public $searchBy = ['evento', 'descripcion', 'numeroAfectaciones', 'totalAfectaciones'];
    public $sortBy = 'evento';
    public $perPage = 10;
    public $data = [];
    public int $anio;

    public function render()
    {
        // dd($this->searchTerm);
        return view('livewire.consulta-ampliaciones-reducciones-table');
    }
    public function query(): Builder
    {
        return Poliza::query();
    }

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }

    public function data()
    {
        $this->data = array_map(function ($entrada) {
            $entrada =  (array) $entrada;
            $entrada['totalAfectaciones'] = '$' . number_format($entrada['totalAfectaciones'], 2, '.', ',');
            return $entrada;
        }, DB::select('EXEC dbo.ConsultaAmpliacionesReducciones @anio = ?', [(string) $this->anio]));
        $collection = collect($this->data);
        if ($this->sortBy !== '') {
            if ($this->sortDirection == "asc") {
                $collection = $collection->sortBy($this->sortBy);
            } else {
                $collection = $collection->sortByDesc($this->sortBy);
            }
        }
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $filtered = $collection->filter(function ($value, $key) {
            $contains = false;
            
            if (!$this->searchTerm) return true;
            foreach ($this->searchBy as $data => $term) {
                // Verifica si $term existe en $value (array)
                if (isset($value[$term]) && str_contains(strtolower($value[$term]), strtolower($this->searchTerm))) {
                    $contains = true;
                    continue;
                }
            }
            
            return $contains;
        });
        
        $currentItems = array_slice($filtered->toArray(), $this->perPage * ($currentPage - 1), $this->perPage);  
        return new LengthAwarePaginator($currentItems, count($filtered), $this->perPage, $currentPage);
    }

    public function columns(): array
    {
        return [
            Column::make('evento', 'No. Evento'),  
            Column::make('descripcion', 'Descripción'),    
            Column::make('numeroAfectaciones', 'No. Afectaciones'),    
            Column::make('totalAfectaciones', 'Total de las afectaciones'),
            Column::make('evento', 'Acciones')->component('columns.accionesConsultaAmpliacionReduccion'),
        ];
    }

    public function visualizarConsultaAmpliacionReduccion($evento){
        $bitacora = new BitacoraController();
        $bitacora->bitacora('visualizarConsultaAmpliacionReduccion', 'visualizó o intentó visualizar el detalle de la afectación con evento: '.$evento, request());
        return redirect('/presupuesto/verDetalleAfectacion/'.$evento);
    }

    public function edit($value)
    {
    }


    public function changeState($value)
    {
    }
}
