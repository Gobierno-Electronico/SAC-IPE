<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use App\Clases\Column;
use App\Models\ClasificacionAdministrativa;
use App\Models\ClasificacionProgramatica;
use App\Models\ClasificadorFuncionalGasto;
use App\Models\ClasificadorTipoGasto;
use App\Models\ClasificadorObjetoGasto;
use App\Models\ClasificadorFuenteFinanciamiento;
use App\Models\ClasificadorRubroIngreso;
use Carbon\Carbon;

class ClasificadoresTable extends Component
{
    use WithPagination;

    public $tipo;
    public $titulo;
    public $fecha;
    public $hora;
    public $searchTerm = '';
    public $sortBy = '';
    public $sortDirection = 'asc';
    public $perPage = 10;

    public $searchBy = []; 

    protected $paginationTheme = 'bootstrap';       

    public function render()
    {
        $query = $this->query()
            ->when($this->sortBy, fn($q) => $q->orderBy($this->sortBy, $this->sortDirection))
            ->when($this->searchTerm, function($q) {
                foreach ($this->searchBy as $field) {
                    $q->orWhere($field, 'like', "%{$this->searchTerm}%");
                }
            });

        $this->fecha = Carbon::now()->format('d-m-Y');
        $this->hora = Carbon::now()->format('h:i A');
        $datos = $query->paginate($this->perPage);

        return view('livewire.clasificadores-table', compact('datos'));
    }

    public function query(): Builder
    {
        switch ($this->tipo) {
            case 'CA':
                return ClasificacionAdministrativa::query();
            case 'CP':
                return ClasificacionProgramatica::query();
            case 'CFG':
                return ClasificadorFuncionalGasto::query();
            case 'CTG':
                return ClasificadorTipoGasto::query();
            case 'COG':
                return ClasificadorObjetoGasto::query();
            case 'CFF':
                return ClasificadorFuenteFinanciamiento::query()
                    ->whereNull('created_at')
                    ->where('Nombre', 'NOT LIKE', '%(Estimado)%')
                    ->where('Nombre', 'NOT LIKE', '%(Por ejecutar)%')
                    ->where('Nombre', 'NOT LIKE', '%(Devengado)%')
                    ->where('Nombre', 'NOT LIKE', '%(Recaudado)%')
                    ->where('Nombre', 'NOT LIKE', '%(Modificado)%');
            case 'CRI':
                return ClasificadorRubroIngreso::query()
                    ->whereNull('created_at')
                    ->where('Nombre', 'NOT LIKE', '%(Estimado)%')
                    ->where('Nombre', 'NOT LIKE', '%(Por ejecutar)%')
                    ->where('Nombre', 'NOT LIKE', '%(Devengado)%')
                    ->where('Nombre', 'NOT LIKE', '%(Recaudado)%')
                    ->where('Nombre', 'NOT LIKE', '%(Modificado)%');
            default:
                return ClasificacionAdministrativa::query();
        }
    }

    public function sort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function columns(): array
    {
        switch ($this->tipo) {
            case 'COG':
                return [
                    Column::make('codigo', 'Código clasificador'),
                    Column::make('nombre', 'Nombre clasificador'),
                    Column::make('cuenta', 'Cuenta')
                ];
            case 'CRI':
                return [
                    Column::make('Codificacion_rubro_ingreso', 'Código clasificador'),
                    Column::make('Nombre', 'Nombre clasificador'),
                    Column::make('Cuenta_contable', 'Cuenta'),
                    Column::make('Cuenta_registro', 'Cuenta de registro')->component('columns.cuentaRegistro')
                ];
            case 'CFF':
                return [
                    Column::make('Codificacion_fuente_financiamiento', 'Código clasificador'),
                    Column::make('Nombre', 'Nombre clasificador'),
                    Column::make('Cuenta_contable', 'Cuenta'),
                    Column::make('Cuenta_registro', 'Cuenta de registro')->component('columns.cuentaRegistro')
                ];
            default:
                return [
                    Column::make('codigo', 'Código clasificador'),
                    Column::make('nombre', 'Nombre clasificador'),
                ];
        }
    }
}
