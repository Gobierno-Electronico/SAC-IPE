<?php

namespace App\Livewire;


use App\Clases\Column;
use App\Models\Poliza;
use App\Livewire\Tabla;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use DB;
use Log;

class MovimientosDeudoresTable extends Tabla
{

    public $perPage = 10;

    public $sortBy = '';

    public $searchBy = ['evento', 'fechaAfectacion', 'fechaRegistro', 'descripcion'];

    public $data = [];

    public $consultarRegistro = false;

    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $descripcion;
    public $tipoMovimiento;
    public $numeroPolizaRemanente;
    public $categoriaModulo;
    public $categoriaRemanente;

    public $eventoSeleccionado;


    public function render()
    {
        $eventos = Poliza::select('evento', 'descripcion')
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->where('tipo_poliza', '=', 'D')
            ->where('categoria', 'LIKE', '%DEUDORES%')
            ->distinct()
            ->get()
            ->sortBy(fn($item) => (int) $item->evento) // Ordenar en PHP convirtiendo a número
            ->pluck('descripcion', 'evento');
        return view('livewire.movimientos-deudores-table', ['eventos' => $eventos]);
    }

    public function query(): Builder
    {
        return Poliza::query();
    }

    public function actualizarEvento()
    {
        $this->eventoSeleccionado = $this->eventoSeleccionado;
    }

    public function data()
    {
        $anioActual = Carbon::now()->year;
        $contador = 0;
        $this->data = array_map(function ($entrada) use (&$contador) {
            $entrada =  (array) $entrada;
            // Convertir a número y dividir entre 2
            $entrada['total'] = floatval($entrada['total']);
            $entrada['total'] = '$' . number_format($entrada['total'], 2, '.', ',');
            if (isset($entrada['tipoRegistro']) && $entrada['tipoRegistro'] === 'COMPROBACION') {
                $entrada['tipoRegistro'] = 'COMPROBACIÓN';
            }

            $entrada['id'] = $contador++;
            return $entrada;
        }, DB::select('EXEC dbo.ConsultaMovimientosDeudores @anio = ?', array($anioActual)));

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
    public function actualizarFiltros()
    {
        $this->eventoSeleccionado = $this->eventoSeleccionado;
    }

    public function columns(): array
    {
        return [
            Column::make('evento', 'Evento'),
            Column::make('numero_poliza', 'Número de Póliza'),
            Column::make('descripcion', 'Descripción'),
            Column::make('tipoRegistro', 'Categoria'),
            Column::make('fechaAfectacion', 'Fecha de afectación'),
            Column::make('fechaRegistro', 'Fecha de registro'),
            Column::make('total', 'Total por Póliza'),
            Column::make('estatus_evento', 'Estatus de evento')->component('columns.estado'),
            Column::make('id', 'Acciones')->component('columns.accionVerMovimiento'),

        ];
    }

    public function verMovimiento($value)
    {
        $partesCategoria = explode(' ', $this->data[$value]['categoria']);

        $this->numeroEvento = $this->data[$value]['evento'];
        $this->numeroPoliza = $this->data[$value]['numero_poliza'];
        $this->total = $this->data[$value]['total'];
        $this->descripcion = $this->data[$value]['descripcion'];
        $this->categoriaModulo = $this->data[$value]['categoria'];
        $this->tipoMovimiento = 'PolizaAnticipos' . ucfirst(strtolower($partesCategoria[1]));

        $this->consultarRegistro = true;
    }

    public function search()
    {
        // $this->resetPage();
    }


    public function edit($value)
    {
        // return view('bitacoras.lista');
    }

    public function changeState($value) {}
}
