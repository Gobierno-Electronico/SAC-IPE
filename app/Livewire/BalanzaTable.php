<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Poliza;
use App\Clases\Column;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class BalanzaTable extends Tabla
{
    public $selectedYear = '';
    public $prevYear = '';
    public $cacheData = [];
    public $perPage = 10;
    public $searchBy = ['Codigo_cuenta', 'Cuenta_padre_ID', 'Descripcion_cuenta'];
    public $sortBy = '';
    public $grupo = '';
    public $prevGrupo = '';
    public $fecha;

    public $hora;

    public $numeroPoliza = 'Reporte';


    public function render()
    {
        return view('livewire.balanza-table');
    }

    public function query(): Builder
    {
        return Poliza::query();
    }

    public function data()
    {
        if (($this->prevYear !== $this->selectedYear) || ($this->prevGrupo !== $this->grupo)) {
            $this->prevYear = $this->selectedYear;
            $this->prevGrupo = $this->grupo;
            if ($this->grupo == 'TODOS') {
                $this->cacheData = DB::select("EXEC BalanzaArmonizada @anio = ?, @Grupo = ''", array($this->selectedYear));
            } else {
                $this->cacheData = DB::select('EXEC BalanzaArmonizada @anio = ?, @Grupo = ?', array($this->selectedYear, $this->grupo));
            }
        }
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $collection = collect($this->cacheData);
        if ($this->sortBy  !== '') {
            if ($this->sortDirection == "asc") {
                $collection = $collection->sortBy($this->sortBy);
            } else {
                $collection = $collection->sortByDesc($this->sortBy);
            }
        }
        $filtered = $collection->filter(function ($value, $key) {
            $contains = false;
            if (!$this->searchTerm) return true;
            foreach ($this->searchBy as $data => $term) {
                if (str_contains($value->$term, $this->searchTerm)) {
                    $contains = true;
                    continue;
                }
            }
            return $contains;
        });
        $filtered->all();
        $currentItems = array_slice($filtered->toArray(), $this->perPage * ($currentPage - 1), $this->perPage);
        return new LengthAwarePaginator($currentItems, count($filtered->toArray()), $this->perPage, $currentPage);
    }

    public function columns(): array
    {
        return [
            Column::make('Codigo_cuenta', 'Codigo Cuenta'),
            Column::make('Descripcion_cuenta', 'Descripción'),
            Column::make('TotalCargoSuma', 'Total Cargo')->component('columns.importe'),
            Column::make('TotalAbonoSuma', 'Total Abono')->component('columns.importe'),
            Column::make('EneroCargoAcumulado', 'Enero Cargo')->component('columns.importe'),
            Column::make('FebreroCargoAcumulado', 'Febrero Cargo')->component('columns.importe'),
            Column::make('MarzoCargoAcumulado', 'Marzo Cargo')->component('columns.importe'),
            Column::make('AbrilCargoAcumulado', 'Abril Cargo')->component('columns.importe'),
            Column::make('MayoCargoAcumulado', 'Mayo Cargo')->component('columns.importe'),
            Column::make('JunioCargoAcumulado', 'Junio Cargo')->component('columns.importe'),
            Column::make('JulioCargoAcumulado', 'Julio Cargo')->component('columns.importe'),
            Column::make('AgostoCargoAcumulado', 'Agosto Cargo')->component('columns.importe'),
            Column::make('SeptiembreCargoAcumulado', 'Septiembre Cargo')->component('columns.importe'),
            Column::make('OctubreCargoAcumulado', 'Octubre Cargo')->component('columns.importe'),
            Column::make('NoviembreCargoAcumulado', 'Noviembre Cargo')->component('columns.importe'),
            Column::make('DiciembreCargoAcumulado', 'Diciembre Cargo')->component('columns.importe'),
            Column::make('EneroAbonoAcumulado', 'Enero Abono')->component('columns.importe'),
            Column::make('FebreroAbonoAcumulado', 'Febrero Abono')->component('columns.importe'),
            Column::make('MarzoAbonoAcumulado', 'Marzo Abono')->component('columns.importe'),
            Column::make('AbrilAbonoAcumulado', 'Abril Abono')->component('columns.importe'),
            Column::make('MayoAbonoAcumulado', 'Mayo Abono')->component('columns.importe'),
            Column::make('JunioAbonoAcumulado', 'Junio Abono')->component('columns.importe'),
            Column::make('JulioAbonoAcumulado', 'Julio Abono')->component('columns.importe'),
            Column::make('AgostoAbonoAcumulado', 'Agosto Abono')->component('columns.importe'),
            Column::make('SeptiembreAbonoAcumulado', 'Septiembre Abono')->component('columns.importe'),
            Column::make('OctubreAbonoAcumulado', 'Octubre Abono')->component('columns.importe'),
            Column::make('NoviembreAbonoAcumulado', 'Noviembre Abono')->component('columns.importe'),
            Column::make('DiciembreAbonoAcumulado', 'Diciembre Abono')->component('columns.importe'),
        ];
    }

    public function edit($value)
    {
    }

    public function changeState($value)
    {
    }

    public function mount()
    {
        $this->selectedYear = Carbon::now()->year;
        $this->fecha = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->format('d/m/Y');
        $this->hora = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->format('H:i:s');
        $this->grupo = '1';
    }

    public function init()
    {
        $this->selectedYear = Carbon::now()->year;
        $this->fecha = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->format('d/m/Y');
        $this->hora = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->format('H:i:s');
    }
}
