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
use Illuminate\Support\Collection;

class DetalleAfectacionTable extends Tabla
{
    #[Reactive]
    public $searchBy = ['cuenta', 'concepto', 'total', 'descripcion', 'tipo_interaccion', 'mes', 'categoria', 'validado'];
    public $sortBy = '';
    public $perPage = 10;
    public $evento;
    public $numeroPolizaIngresos;
    public $numeroPolizaEgresos;
    public $fecha;
    public $hora;
    public int $anio;

    public function render()
    {
        return view('livewire.detalle-afectacion-table');
    }

    public function mount(){
        $this->anio = (int) session('anioSeleccionado', now()->year);

        $poliza = $this->data()->first();

        $this->fecha = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('d/m/Y') : '01/01/' . (string) $this->anio;
        $this->hora = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('H:i:s') : '11:00:00';

        $this->numeroPolizaIngresos = Poliza::where('evento', '=', $this->evento)
                                    ->where('categoria', 'like', '%INGRESOS')
                                    ->pluck('numero_poliza')
                                    ->first();
        $this->numeroPolizaEgresos = Poliza::where('evento', '=', $this->evento)
                                    ->where('categoria', 'like', '%EGRESOS')
                                    ->pluck('numero_poliza')
                                    ->first();

    }

    public function query(): Builder
    {
        return Poliza::query();
    }

    public function data()
    {
        return $this
        ->query()
        ->select('polizas.*')
        ->when($this->sortBy !== '', function ($query) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        })
        ->search($this->searchBy, $this->searchTerm)
        ->where('evento', '=', $this->evento)
        ->where(function ($query) {
                $query->where('categoria', 'like', '%AMPLIACION%')
                    ->orWhere('categoria', 'like', '%REDUCCION%');
            })
        ->whereYear('fecha', '=', $this->anio)
        ->paginate($this->perPage); 
    }

    

    public function columns(): array
    {
        return [
            Column::make('cuenta', 'Cuenta'),  
            Column::make('concepto', 'Concepto'), 
            Column::make('total', 'Total')->component('columns.importe'), 
            Column::make('descripcion', 'Descripción'),    
            Column::make('tipo_interaccion', 'Interacción'),
            Column::make('mes', 'Mes'),   
            Column::make('categoria', 'Tipo de afectación'),
            Column::make('validado', 'Validado')->component('columns.validado')
        ];
    }

    public function edit($value)
    {
    }


    public function changeState($value)
    {
    }
}
