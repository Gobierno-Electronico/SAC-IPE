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

class MovimientosCancelacionesTable extends Tabla
{

    public $perPage = 10;

    public $sortBy = '';

    public $searchBy = ['evento', 'fechaAfectacion', 'fechaRegistro', 'descripcion', 'momentoContable'];

    public $data = [];

    public $capituloSeleccionado = '';

    public $consultarRegistro = false;

    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $totalPoliza;
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
            ->where('tipo_poliza', '=', 'D')
            ->where('categoria', 'LIKE', '%CANCELACIÓN%')
            ->distinct()
            ->get()
            ->sortBy(fn($item) => (int) $item->evento) // Ordenar en PHP convirtiendo a número
            ->pluck('descripcion', 'evento');

        return view('livewire.movimientos-cancelaciones-table', ['eventos' => $eventos]);
    }

    public function query(): Builder
    {
        return Poliza::query();
    }

    public function actualizarFiltros()
    {
        $this->eventoSeleccionado = $this->eventoSeleccionado;
        $this->capituloSeleccionado = $this->capituloSeleccionado;
    }

    public function data()
    {
        $anioActual = Carbon::now()->year;
        $contador = 0;
    
        // Obtener datos desde la consulta SQL
        $this->data = array_map(function ($entrada) use (&$contador) {
            
            $entrada = (array) $entrada;
            
            // Convertir a número y dividir entre 2
            $entrada['total'] = floatval($entrada['total']) / 2;
            
            // Formatear el número
            $entrada['total'] = '$' . number_format($entrada['total'], 2, '.', ',');
            
            $entrada['id'] = $contador++;
        
            return $entrada;      
        }, DB::select('EXEC dbo.ConsultaCancelacionesEgresos @anio = ?', [$anioActual]));
    
        $collection = collect($this->data);
    
        // Aplicar filtros
        if ($this->eventoSeleccionado) {
            $collection = $collection->where('evento', $this->eventoSeleccionado);
        }
        
        if ($this->sortBy !== '') {
            $collection = $this->sortDirection == "asc"
                ? $collection->sortBy($this->sortBy)
                : $collection->sortByDesc($this->sortBy);
        }
    
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
    
        $filtered = $collection->filter(function ($value) {
            if (!$this->searchTerm) return true;
    
            foreach ($this->searchBy as $term) {
                if (isset($value[$term]) && str_contains(strtolower($value[$term]), strtolower($this->searchTerm))) {
                    return true;
                }
            }
    
            return false;
        });
        // // NUEVO: Agregar el total agrupado por evento y número de póliza
        // $filtered = $filtered->map(function ($item) {

        //     $totalesPolizas = Poliza::select('evento', 'numero_poliza', 'total') 
        //     ->whereYear('fecha', '=', Carbon::now()->year) // Filtra por año actual
        //     ->where('evento', '=', $item['evento'])
        //     ->where('tipo_poliza', '=', 'E')
        //     ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')
        //     ->where('categoria', 'like', '%CANCELACIÓN%') // Filtra categoría que contenga 'comprometido'
        //     ->get();
        
            
        //     // $sumaTotal = $totalesPolizas->sum('total');
        //     // dd($totalesPolizas);
        //     $sumaTotal = $totalesPolizas->sum('total'); // NUEVO: Sumar los valores del atributo 'total'
    
        //     $item['total_evento'] = '$' . number_format($sumaTotal, 2, '.', ','); // NUEVO: Formatear el total
        //     return $item;
        // });
        // dd($filtered);

    
        // Paginación manual
        $currentItems = array_slice($filtered->toArray(), $this->perPage * ($currentPage - 1), $this->perPage);
    
        return new LengthAwarePaginator($currentItems, count($filtered), $this->perPage, $currentPage);
    }
    
    

    public function columns(): array
    {
        return [
            Column::make('evento', 'Evento'),
            Column::make('numero_poliza', 'Número de Póliza'),
            Column::make('descripcion', 'Descripción'),
            Column::make('fechaAfectacion', 'Fecha de afectación'),
            Column::make('fechaRegistro', 'Fecha de registro'),
            Column::make('total', 'Total Evento'),
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
        $this->tipoMovimiento = 'PolizaCancelacionCompromisoCap1'; 

        $this->consultarRegistro = true;
    }

    public function search() {}


    public function edit($value) {}

    public function changeState($value) {}
}
