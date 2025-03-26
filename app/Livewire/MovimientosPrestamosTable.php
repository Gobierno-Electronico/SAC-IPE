<?php

namespace App\Livewire;


use App\Clases\Column;
use App\Models\Poliza;
use App\Livewire\Tabla;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use DB;
use DragonCode\Support\Facades\Helpers\Arr;
use Log;

class MovimientosPrestamosTable extends Tabla
{

    public $perPage = 10;

    public $sortBy = '';

    public $searchBy = ['evento', 'fechaAfectacion', 'fechaRegistro', 'descripcion', 'momentoContable'];

    public $data = [];

    public $consultarRegistro = false;

    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $descripcion;
    public $tipoMovimiento;
    public $categoriaModulo;
    public $eventoSeleccionado;
    

    public function render()
    {
        $eventos = Poliza::select('evento', 'descripcion')
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->where('tipo_poliza', '=', 'D')
            ->distinct()
            ->get()
            ->sortBy(fn($item) => (int) $item->evento) // Ordenar en PHP convirtiendo a número
            ->pluck('descripcion', 'evento');
        return view('livewire.movimientos-prestamos-table', ['eventos' => $eventos]);
    }

    public function query(): Builder
    {
        return Poliza::query();
    }

    public function actualizarFiltros()
    {
        $this->eventoSeleccionado = $this->eventoSeleccionado;
    }

    public function data()
    {
        $anioActual = Carbon::now()->year;
        $contador = 0;
        $this->data = array_map(function ($entrada) use (&$contador) {
            $entrada =  (array) $entrada;
            $entrada['total'] = '$' . number_format($entrada['total'], 2, '.', ',');
            $entrada['id'] = $contador++;
            return $entrada;
        }, DB::select('EXEC dbo.ConsultaMovimientosPrestamos @anio = ?', array($anioActual)));
        $collection = collect($this->data);
        if ($this->eventoSeleccionado) {
            $collection = $collection->where('evento', $this->eventoSeleccionado);
        }
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
            Column::make('evento', 'Evento'),
            Column::make('numero_poliza', 'Número de Póliza'),
            Column::make('descripcion', 'Descripción'),
            Column::make('momentoContable', 'Momento contable'),
            Column::make('fechaAfectacion', 'Fecha de afectación'),
            Column::make('fechaRegistro', 'Fecha de registro'),
            Column::make('total', 'Monto del evento'),
            Column::make('estatus_evento', 'Estado del momento contable')->component('columns.estado'),
            Column::make('id', 'Acciones')->component('columns.accionVerMovimiento'),

        ];
    }

    public function verMovimiento($value)
    {

        $this->numeroEvento = $this->data[$value]['evento'];
        $this->numeroPoliza = $this->data[$value]['numero_poliza'];
        $this->total = $this->data[$value]['total'];
        $this->descripcion = $this->data[$value]['descripcion'];
        $this->categoriaModulo = $this->data[$value]['categoria'];
        $nombrePoliza = '';
        if(str_contains($this->categoriaModulo, 'RECAUDADO') && !str_contains($this->categoriaModulo, 'PAGADO')){
            $nombrePoliza = 'PolizaRecuperacion';
        }else{
            $nombrePoliza = 'PolizaOtorgamiento';
        }
        $this->tipoMovimiento = $nombrePoliza . str_replace(' ', '', ucwords(strtolower($this->data[$value]['momentoContable'])));
       
        $this->consultarRegistro = true;
    }

    public function search() {}


    public function edit($value) {}

    public function changeState($value) {}
}
