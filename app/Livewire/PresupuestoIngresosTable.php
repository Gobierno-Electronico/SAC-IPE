<?php

namespace App\Livewire;

use App\Clases\Column;
use App\Models\ClasificadorDeConcepto;
use App\Models\Concepto;
use App\Models\Cuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\InteraccionCuentaCuenta;
use App\Models\Poliza;
use App\Models\PresupuestoInicial;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use App\Http\Controllers\BitacoraController;
use Carbon\Carbon;
use DB;

class PresupuestoIngresosTable extends Tabla
{
    public $numeroPoliza = '';

    public $perPage = 10;

    public $sortBy = '';

    public $selectedYear = '';

    public $cuentaSeleccionada = '';

    public $clasificadorSeleccionado = '';

    public $conceptoSeleccionado = '';

    public $searchBy = ['concepto', 'area', 'codigo_departamentos.Nombre', 'tipo_poliza', 'numero_poliza', 'Codificacion_rubro_ingreso', 'cuenta', 'total', 'mes', 'Codificacion_fuente_financiamiento'];

    public $validado = true;

    public $fecha = '';

    public $hora = '';

    public function render()
    {
        // $this->selectedYear = Carbon::now()->year + 1;
        return view('livewire.presupuesto-ingresos-table');
    }
    public function query(): Builder
    {
        return Poliza::query();
    }

    public function columns(): array
    {
        // dd($this->selectedYear);
        return [
            Column::make('areaNombre', 'Área recaudadora'),
            // Column::make('tipo_poliza', 'Tipo de póliza'),
            // Column::make('numero_poliza', 'Número de póliza'),
            Column::make('CFF', 'Origen de recurso'),
            Column::make('CRI', 'CRI'),
            Column::make('cuenta', 'Cuenta'),
            Column::make('concepto', 'Concepto'),
            Column::make('total', 'Total')->component('columns.importe'),
            Column::make('evento', 'No. de evento'),
            Column::make('mes', 'mes'),
            Column::make('validado', 'validado')->component('columns.validado'),
        ];
    }


    public function data()
    {
        return $this
            ->query()
            ->join('clasificador_rubro_ingreso', 'polizas.cuenta', '=', 'clasificador_rubro_ingreso.Cuenta_contable')
            ->join('clasificador_fuente_financiamiento', 'polizas.cuenta', '=', 'clasificador_fuente_financiamiento.Cuenta_contable')
            ->join('codigo_departamentos', 'polizas.area', '=', 'codigo_departamentos.Codigo_completo')
            ->select('polizas.*', 'clasificador_rubro_ingreso.Codificacion_rubro_ingreso as CRI', 'clasificador_fuente_financiamiento.Codificacion_fuente_financiamiento as CFF', 'codigo_departamentos.Nombre as areaNombre')
            ->when($this->sortBy !== '', function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })->search($this->searchBy, $this->searchTerm)
            ->when($this->selectedYear !== '', function ($query) {
                $query->searchByYear('fecha', $this->selectedYear);
            })
            ->where('categoria', '=', 'INICIAL INGRESOS')
            ->where('tipo_poliza', '=', 'P')
            ->paginate($this->perPage);
    }



    public function validarPresupuestoInicial()
    {
        try {
            DB::beginTransaction();
            Poliza::when($this->selectedYear !== '', function ($query) {
                $query->searchByYear('fecha', $this->selectedYear);
            })->where('categoria', '=', 'INICIAL INGRESOS')->where('tipo_poliza', '=', 'P')->update(["validado" => true]);

            // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'INGRESOS')->where('tipo', '=', 'P')->update(["validado" => true]);
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('validarPresupuestoInicial', 'validó o intentó validar el presupuesto inicial de ingresos del año ' . $this->selectedYear, request());
            $this->validado = true;
            DB::commit();
            $this->dispatch('mostrarMensaje', mensaje: 'Se validó el presupuesto de ingresos del año ' . $this->selectedYear, tipo: 'success', tiempo: 3000);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al validar el presupuesto inicial', tipo: 'error', tiempo: 3000);
        }
    }

    public function selectYear()
    {
        $poliza = $this->data()->first();
        $this->validado = ($poliza) ? boolval($poliza->validado) : true;
        $this->fecha = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('d/m/Y') : '01/01/' . Carbon::now()->year;
        $this->hora = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('H:i:s') : '11:00:00';
        $this->numeroPoliza = ($poliza) ? $poliza->numero_poliza : 0;
    }

    public function edit($value)
    {
    }

    public function init()
    {
        $this->selectedYear = (string) $this->anio;
        $this->selectYear();
    }

    public function borrar()
    {
        try {
            DB::beginTransaction();
            if ($this->validado)
                return;
            Poliza::when($this->selectedYear !== '', function ($query) {
                $query->searchByYear('fecha', $this->selectedYear);
            })->where('categoria', '=', 'INICIAL INGRESOS')->where('tipo_poliza', '=', 'P')->delete();
            // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'INGRESOS')->where('tipo', '=', 'P')->delete();
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('borrar', 'borró o intentó borrar el presupuesto inicial de ingresos del año ' . $this->selectedYear, request());
            $this->validado = true;
            $this->dispatch('mostrarMensaje', mensaje: 'Se borró el presupuesto de ingresos del año ' . $this->selectedYear, tipo: 'success', tiempo: 3000);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al borrar el presupuesto inicial', tipo: 'error', tiempo: 3000);
        }
    }
    public function changeState($value)
    {
    }
}
