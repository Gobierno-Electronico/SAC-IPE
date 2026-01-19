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

class MovimientosIngresosTable extends Tabla
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
    public $numeroPolizaRemanente;
    public $categoriaModulo;
    public $categoriaRemanente;

    public $eventoSeleccionado;
    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }

    public function render()
    {
        $eventos = Poliza::select('evento', 'descripcion')
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->where('tipo_poliza', '=', 'I')
            ->distinct()
            ->get()
            ->sortBy(fn($item) => (int) $item->evento) // Ordenar en PHP convirtiendo a número
            ->pluck('descripcion', 'evento');
        return view('livewire.movimientos-ingresos-table', ['eventos' => $eventos]);
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
            $entrada['total'] = floatval($entrada['total']) / 2;
            $entrada['total'] = '$' . number_format($entrada['total'], 2, '.', ',');
            $entrada['id'] = $contador++;
            return $entrada;
        }, DB::select('EXEC dbo.ConsultaMovimientosIngresos @anio = ?', array($anioActual)));

        $collection = collect($this->data);
        if ($this->eventoSeleccionado) {
            $collection = $collection->where('evento', $this->eventoSeleccionado);
        }
        if ($this->sortBy !== '') {
            if (($this->sortBy == "fechaRegistro") || ($this->sortBy == "fechaAfectacion")) {
                if ($this->sortDirection == "asc") {
                    $collection = $collection->sortBy(function ($item) {
                        return Carbon::createFromFormat('d-m-Y', $item['fechaRegistro']);
                    });
                } else {
                    $collection = $collection->sortByDesc(function ($item) {
                        return Carbon::createFromFormat('d-m-Y', $item['fechaRegistro']);
                    });
                }
            } else {
                if ($this->sortDirection == "asc") {
                    $collection = $collection->sortBy($this->sortBy);
                } else {
                    $collection = $collection->sortByDesc($this->sortBy);
                }
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

        $filtered = $filtered->map(function ($item) {

            $totalesPolizas = Poliza::select('evento', 'numero_poliza', 'total')
                ->whereYear('fecha', '=', Carbon::now()->year) // Filtra por año actual
                ->where('evento', '=', $item['evento'])
                ->where('tipo_poliza', '=', 'I')
                ->get();

            $sumaTotal = $totalesPolizas->sum('total');

            $item['total_evento'] = '$' . number_format($sumaTotal, 2, '.', ','); // NUEVO: Formatear el total
            return $item;
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
            Column::make('total_evento', 'Monto del evento'),
            Column::make('total', 'Total por Póliza'), // NUEVA COLUMNA
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
        $this->tipoMovimiento = 'PolizaIngresos' . str_replace(' ', '', ucwords(strtolower($this->data[$value]['momentoContable'])));

        //extraemos el número de póliza de remanente que corresponde al registro del número de póliza que ya tenemos
        $numeroPoliza = $this->numeroPoliza;
        $this->numeroPolizaRemanente = DB::table('polizas')
            ->where('tipo_poliza', 'IAUX')
            ->where('evento', '=', $this->numeroEvento)
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->where('id', '>', function ($query) use ($numeroPoliza) {
                $query->select('id')
                    ->from('polizas')
                    ->where('numero_poliza', $numeroPoliza)
                    ->where('tipo_poliza', 'I')
                    ->limit(1);
            })
            ->orderBy('id', 'asc')
            ->pluck('numero_poliza')
            ->first();


        if ($this->numeroPolizaRemanente == NULL) {
            $this->numeroPolizaRemanente = 0;
        }

        switch ($this->data[$value]['momentoContable']) {
            case "RECAUDADO":
                $this->categoriaRemanente = 'INGRESOS DEVENGADO REMANENTE RECAUDADO';
                break;
            case "COBRO ESPECIE":
                $this->categoriaRemanente = 'INGRESOS DEVENGADO REMANENTE COBRO ESPECIE';
                break;
            case "DEVENGADO PREVIAMENTE RECAUDADO":
                $this->categoriaRemanente = 'INGRESOS POR CLASIFICAR REMANENTE DEVENGADO PREVIAMENTE RECAUDADO';
                break;
            default:
                $this->categoriaRemanente = 'SIN REMANENTE';
                $this->numeroPolizaRemanente = 0;
                break;
        }

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
