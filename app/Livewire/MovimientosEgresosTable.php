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
/*
class MovimientosEgresosTable extends Tabla
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

    public function render()
    {
        $eventos = Poliza::select('evento', 'descripcion')
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->where('tipo_poliza', '=', 'E')
            ->distinct()
            ->get()
            ->sortBy(fn($item) => (int) $item->evento) // Ordenar en PHP convirtiendo a número
            ->pluck('descripcion', 'evento');

        return view('livewire.movimientos-egresos-table', ['eventos' => $eventos]);
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
        }, DB::select('EXEC dbo.ConsultaMovimientosEgresos @anio = ?', [$anioActual]));
    
        $collection = collect($this->data);
    
        // Aplicar filtros
        if ($this->eventoSeleccionado) {
            $collection = $collection->where('evento', $this->eventoSeleccionado);
        }
        if ($this->capituloSeleccionado) {
            $collection = $collection->where('capitulo', $this->capituloSeleccionado);
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
        // NUEVO: Agregar el total agrupado por evento y número de póliza
        $filtered = $filtered->map(function ($item) {

            $totalesPolizas = Poliza::select('evento', 'numero_poliza', 'total') 
            ->whereYear('fecha', '=', Carbon::now()->year) // Filtra por año actual
            ->where('evento', '=', $item['evento'])
            ->where('tipo_poliza', '=', 'E')
            ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')
            ->get();
        
            $sumaTotal = $totalesPolizas->sum('total'); // NUEVO: Sumar los valores del atributo 'total'
    
            $item['total_evento'] = '$' . number_format($sumaTotal, 2, '.', ','); // NUEVO: Formatear el total
            return $item;
        });
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
            Column::make('momentoContable', 'Momento contable'),
            Column::make('capitulo', 'Capítulo'),
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
        $this->tipoMovimiento = 'PolizaEgresos' . ucfirst(strtolower($this->data[$value]['momentoContable'])) . 'Capitulo' . $this->data[$value]['capitulo'];
        $numeroPoliza = $this->numeroPoliza;
        $this->numeroPolizaRemanente = DB::table('polizas')
            ->where('tipo_poliza', 'EAUX')
            ->where('evento', '=', $this->numeroEvento)
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->where('id', '>', function ($query) use ($numeroPoliza) {
                $query->select('id')
                    ->from('polizas')
                    ->where('numero_poliza', $numeroPoliza)
                    ->where('tipo_poliza', 'E')
                    ->limit(1);
            })
            ->orderBy('id', 'asc')
            ->pluck('numero_poliza')
            ->first();


        if ($this->numeroPolizaRemanente == NULL) {
            $this->numeroPolizaRemanente = 0;
        }

        switch ($this->data[$value]['momentoContable']) {
            case "DEVENGADO":
                $this->categoriaRemanente = 'EGRESOS COMPROMETIDO CAPITULO ' . $this->data[$value]['capitulo'] . ' REMANENTE DEVENGADO';
                break;
            case "EJERCIDO":
                $this->categoriaRemanente = 'EGRESOS DEVENGADO CAPITULO ' . $this->data[$value]['capitulo'] . ' REMANENTE EJERCIDO';
                break;
            case "PAGADO":
                $this->categoriaRemanente = 'EGRESOS EJERCIDO CAPITULO ' . $this->data[$value]['capitulo'] . ' REMANENTE PAGADO';
                break;
            default:
                $this->categoriaRemanente = 'SIN REMANENTE';
                $this->numeroPolizaRemanente = 0;
                break;
        }

        $this->consultarRegistro = true;
    }

    public function search() {}


    public function edit($value) {}

    public function changeState($value) {}
}
*/

class MovimientosEgresosTable extends Tabla
{
    public $perPage = 10;
    public $sortBy = 'evento';
    public $sortDirection = 'asc';
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

    public function render()
    {
        $eventos = Poliza::select('evento', 'descripcion')
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->where('tipo_poliza', '=', 'E')
            ->distinct()
            ->get()
            ->sortBy(fn($item) => (int) $item->evento) 
            ->pluck('descripcion', 'evento');
            
        return view('livewire.movimientos-egresos-table', ['eventos' => $eventos]);
    }
    
    public function query(): Builder
    {
        return Poliza::query();
    }
    
    public function actualizarFiltros()
    {
        $this->resetPage();
    }

    public function data()
    {
        $query = Poliza::select(
            'evento',
            'descripcion',
            'categoria',
            DB::raw("SUBSTRING(categoria, CHARINDEX(' ', categoria) + 1, CHARINDEX(' ', categoria, CHARINDEX(' ', categoria) + 1) - CHARINDEX(' ', categoria) - 1) AS momentoContable"),
            DB::raw("LTRIM(RTRIM(SUBSTRING(categoria, PATINDEX('%[0-9]%', categoria), LEN(categoria) - PATINDEX('%[0-9]%', categoria) + 1))) AS capitulo"),
            DB::raw('SUM(total) AS total'),
            DB::raw("CONVERT(varchar(10), CONVERT(date, MIN(fecha)), 105) AS fechaAfectacion"),
            DB::raw("CONVERT(varchar(10), CONVERT(date, MIN(created_at)), 105) AS fechaRegistro"),
            'numero_poliza',
            'estatus_evento',
            DB::raw("
        '{\"evento\":' + CONVERT(varchar(10), ISNULL(evento, 'null')) + 
        ', \"numero_poliza\":' + CONVERT(varchar(10), ISNULL(numero_poliza, 'null')) + '}' AS identificador
        "),
        )
        ->where('tipo_poliza', 'E')
        ->whereYear('fecha', now()->year)
        ->groupBy(
            'evento',
            'descripcion',
            'numero_poliza',
            'categoria',
            'estatus_evento'
        );

        if ($this->eventoSeleccionado) {
            $query->where('evento', $this->eventoSeleccionado);
        }

        if ($this->capituloSeleccionado) {
            $query->where(DB::raw("LTRIM(RTRIM(SUBSTRING(categoria, PATINDEX('%[0-9]%', categoria), LEN(categoria) - PATINDEX('%[0-9]%', categoria) + 1)))"), $this->capituloSeleccionado);
        }

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('evento', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('descripcion', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('numero_poliza', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere(DB::raw("CONVERT(varchar(10), CONVERT(date, fecha), 105)"), 'like', '%' . $this->searchTerm . '%')
                  ->orWhere(DB::raw("CONVERT(varchar(10), CONVERT(date, created_at), 105)"), 'like', '%' . $this->searchTerm . '%')
                  ->orWhere(DB::raw("SUBSTRING(categoria, CHARINDEX(' ', categoria) + 1, CHARINDEX(' ', categoria, CHARINDEX(' ', categoria) + 1) - CHARINDEX(' ', categoria) - 1)"), 'like', '%' . $this->searchTerm . '%');
            });
        }
        
        if ($this->sortBy) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        $paginated = $query->paginate($this->perPage);

        $paginated->getCollection()->transform(function ($item) {
            $total_base = (float) $item->total / 2;
            $item->total = '$' . number_format($total_base, 2, '.', ',');
            
            $total_evento = Poliza::where('evento', $item->evento)
                ->whereYear('fecha', now()->year)
                ->where('tipo_poliza', 'E')
                ->where('tipo_interaccion', 'Presupuestal - Cargo')
                ->sum('total');
                
            $item->total_evento = '$' . number_format($total_evento, 2, '.', ',');


            return $item;
        });
        
        return $paginated;
    }

    public function columns(): array
    {
        return [
            Column::make('evento', 'Evento'),
            Column::make('numero_poliza', 'Número de Póliza'),
            Column::make('descripcion', 'Descripción'),
            Column::make('momentoContable', 'Momento contable'),
            Column::make('capitulo', 'Capítulo'),
            Column::make('fechaAfectacion', 'Fecha de afectación'),
            Column::make('fechaRegistro', 'Fecha de registro'),
            Column::make('total_evento', 'Monto del evento'),
            Column::make('total', 'Total por Póliza'),
            Column::make('estatus_evento', 'Estado del momento contable')->component('columns.estado'),
           // Column::make('numero_poliza', 'Acciones')->component('columns.accionVerMovimiento'),
            Column::make('identificador', 'Acciones')->component('columns.accionVerMovimiento'),
        ];
    }

    public function verMovimiento($identificador)
    {
        $data = is_string($identificador) ? json_decode($identificador, true) : $identificador;
        $evento = $data['evento'];
        $numeroPoliza = $data['numero_poliza'];
      
        $movimiento = Poliza::select(
            'evento',
            'descripcion',
            'categoria',
            DB::raw("SUBSTRING(categoria, CHARINDEX(' ', categoria) + 1, CHARINDEX(' ', categoria, CHARINDEX(' ', categoria) + 1) - CHARINDEX(' ', categoria) - 1) AS momentoContable"),
            DB::raw("LTRIM(RTRIM(SUBSTRING(categoria, PATINDEX('%[0-9]%', categoria), LEN(categoria) - PATINDEX('%[0-9]%', categoria) + 1))) AS capitulo"),
            DB::raw('SUM(total) AS total'),
            'numero_poliza'
        )
        ->where('tipo_poliza', 'E')
        ->whereYear('fecha', now()->year)
        ->where('evento', $evento) 
         ->where('numero_poliza', $numeroPoliza) 
        ->groupBy(
            'evento',
            'descripcion',
            'numero_poliza',
            'categoria',
            'estatus_evento'
        )->first(); 

        if (!$movimiento) {
            return;
        }

        $this->numeroEvento = $movimiento->evento;
        $this->numeroPoliza = $movimiento->numero_poliza;
        $this->total = '$' . number_format((float) $movimiento->total / 2, 2, '.', ','); 
        $this->descripcion = $movimiento->descripcion;
        $this->categoriaModulo = $movimiento->categoria;
        $this->tipoMovimiento = 'PolizaEgresos' . ucfirst(strtolower($movimiento->momentoContable)) . 'Capitulo' . $movimiento->capitulo;
        $numeroPoliza = $this->numeroPoliza;

        $this->numeroPolizaRemanente = DB::table('polizas')
        ->where('tipo_poliza', 'EAUX')
        ->where('evento', '=', $this->numeroEvento)
        ->whereYear('fecha', '=', Carbon::now()->year)
        ->where('id', '>', function ($query) use ($numeroPoliza) {
            $query->select('id')
                ->from('polizas')
                ->where('numero_poliza', $numeroPoliza)
                ->where('tipo_poliza', 'E')
                ->limit(1);
        })
        ->orderBy('id', 'asc')
        ->pluck('numero_poliza')
        ->first();

        if ($this->numeroPolizaRemanente == NULL) {
            $this->numeroPolizaRemanente = 0;
        }

        switch ($movimiento->momentoContable) {
            case "DEVENGADO":
                $this->categoriaRemanente = 'EGRESOS COMPROMETIDO CAPITULO ' . $movimiento->capitulo . ' REMANENTE DEVENGADO';
                break;
            case "EJERCIDO":
                $this->categoriaRemanente = 'EGRESOS DEVENGADO CAPITULO ' . $movimiento->capitulo . ' REMANENTE EJERCIDO';
                break;
            case "PAGADO":
                $this->categoriaRemanente = 'EGRESOS EJERCIDO CAPITULO ' . $movimiento->capitulo . ' REMANENTE PAGADO';
                break;
            default:
                $this->categoriaRemanente = 'SIN REMANENTE';
                $this->numeroPolizaRemanente = 0;
                break;
        }

        $this->consultarRegistro = true;
    }

    public function search() {}
    public function edit($value) {}
    public function changeState($value) {}
}