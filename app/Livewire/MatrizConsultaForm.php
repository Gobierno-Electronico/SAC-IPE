<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use App\Clases\Column;
use App\Models\MatrizConversion;

class MatrizConsultaForm extends Component
{
    use WithPagination;

    public $tipoMatriz = '';
    public $perPage = 10;
    public $sortBy = '';
    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        $query = $this->query()
            ->when($this->tipoMatriz, fn($q) =>
                $q->where('categoria_matriz', $this->tipoMatriz)
            )
            ->when($this->sortBy, fn($q) =>
                $q->orderBy($this->sortBy, $this->sortDirection)
            );

        $datos = $this->tipoMatriz ? $query->paginate($this->perPage) : collect();

        return view('livewire.matriz-consulta', compact('datos'));
    }

    public function query(): Builder
    {
        return MatrizConversion::query();
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
        switch ($this->tipoMatriz) {
            case 'INGRESOS DEVENGADO-RECAUDADO SIMULTANEO':
                return [
                    Column::make('codigo_clasificador', 'CÓDIGO CLASIFICADOR'),
                    Column::make('concepto', 'CONCEPTO'),
                    Column::make('medio_recaudacion', 'MEDIO DE RECAUDACIÓN'),
                    Column::make('caracteristicas', 'CARACTERÍSTICAS'),
                    Column::make('codigo_cargo', 'CÓDIGO CARGO'),
                    Column::make('cuenta_cargo', 'CUENTA CARGO'),
                    Column::make('codigo_abono', 'CÓDIGO ABONO'),
                    Column::make('cuenta_abono', 'CUENTA ABONO'),
                ];
            case 'GASTOS DEVENGADO':
                return [
                    Column::make('codigo_clasificador', 'CÓDIGO CLASIFICADOR'),
                    Column::make('concepto', 'CONCEPTO'),
                    Column::make('tipo_gasto', 'TIPO DE GASTO'),
                    Column::make('caracteristicas', 'CARACTERÍSTICAS'),
                    Column::make('codigo_cargo', 'CÓDIGO CARGO'),
                    Column::make('cuenta_cargo', 'CUENTA CARGO'),
                    Column::make('codigo_abono', 'CÓDIGO ABONO'),
                    Column::make('cuenta_abono', 'CUENTA ABONO'),
                ];
            case 'GASTOS PAGADO':
                return [
                    Column::make('codigo_clasificador', 'CÓDIGO CLASIFICADOR'),
                    Column::make('concepto', 'CONCEPTO'),
                    Column::make('tipo_gasto', 'TIPO DE GASTO'),
                    Column::make('caracteristicas', 'CARACTERÍSTICAS'),
                    Column::make('medio_pago', 'MEDIO DE PAGO'),
                    Column::make('codigo_cargo', 'CÓDIGO CARGO'),
                    Column::make('cuenta_cargo', 'CUENTA CARGO'),
                    Column::make('codigo_abono', 'CÓDIGO ABONO'),
                    Column::make('cuenta_abono', 'CUENTA ABONO'),
                ];
            default:
                return [
                    Column::make('codigo_clasificador', 'CÓDIGO CLASIFICADOR'),
                    Column::make('concepto', 'CONCEPTO'),
                    Column::make('caracteristicas', 'CARACTERÍSTICAS'),
                    Column::make('codigo_cargo', 'CÓDIGO CARGO'),
                    Column::make('cuenta_cargo', 'CUENTA CARGO'),
                    Column::make('codigo_abono', 'CÓDIGO ABONO'),
                    Column::make('cuenta_abono', 'CUENTA ABONO'),
                ];
        }
    }
}
