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

class PolizaInicialTable extends Tabla
{
    public $numeroPoliza = '';

    public $perPage = 10;

    public $sortBy = '';

    public $selectedYear = '';

    public $searchBy = ['concepto', 'cuenta', 'total', 'evento'];

    public $validado = true;

    public $fecha = '';

    public $hora = '';

    public function render()
    {
        // $this->selectedYear = Carbon::now()->year + 1;
        return view('livewire.poliza-inicial-table');
    }
    public function query(): Builder
    {
        return Poliza::query();
    }

    public function columns(): array
    {
        // dd($this->selectedYear);
        return [
            Column::make('cuenta', 'Cuenta'),
            Column::make('concepto', 'Concepto'),
            Column::make('total', 'Total')->component('columns.importe'),
            Column::make('evento', 'No. de evento'),
            // Column::make('mes', 'mes'),
            Column::make('validado', 'validado')->component('columns.validado'),
        ];
    }


    public function data()
    {
        return $this
            ->query()
            ->select('polizas.*')
            ->when($this->sortBy !== '', function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })->search($this->searchBy, $this->searchTerm)
            ->when($this->selectedYear !== '', function ($query) {
                $query->searchByYear('fecha', $this->selectedYear);
            })
            ->where('tipo_poliza', '=', 'SI')
            ->paginate($this->perPage);
    }



    public function validarPolizaInicial()
    {
        try {
            DB::beginTransaction();
            if ($this->selectedYear != '') {
                Poliza::when($this->selectedYear !== '', function ($query) {
                    $query->searchByYear('fecha', $this->selectedYear);
                })->where('tipo_poliza', '=', 'SI')->where('numero_poliza', '=', $this->numeroPoliza)->update(["validado" => true]);
                $usuariosController = new BitacoraController();
                $usuariosController->bitacora('validarPresupuestoInicial', 'validó o intentó validar la póliza inicial del año ' . $this->selectedYear, request());
                $this->validado = true;
                DB::commit();
                $this->dispatch('mostrarMensaje', mensaje: 'Se validó la póliza inicial del año ' . $this->selectedYear, tipo: 'success', tiempo: 3000);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al validar la póliza inicial', tipo: 'error', tiempo: 3000);

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
        $this->selectedYear = Carbon::now()->year;
        $this->selectYear();
    }

    public function borrar()
    {
        try {
            DB::beginTransaction();
            if ($this->validado)
                return;
            if ($this->selectedYear != '') {
                Poliza::when($this->selectedYear !== '', function ($query) {
                    $query->searchByYear('fecha', $this->selectedYear);
                })->where('tipo_poliza', '=', 'SI')->where('numero_poliza', '=', $this->numeroPoliza)->delete();
                // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'EGRESOS')->where('tipo', '=', 'P')->delete();
                $usuariosController = new BitacoraController();
                $usuariosController->bitacora('borrar', 'borró o intentó borrar la póliza inicial del año ' . $this->selectedYear, request());
                $this->validado = true;
                DB::commit();
                $this->dispatch('mostrarMensaje', mensaje: 'Se borró la póliza inicial del año ' . $this->selectedYear, tipo: 'success', tiempo: 3000);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al validar la póliza inicial', tipo: 'error', tiempo: 3000);

        }
    }
    public function changeState($value)
    {
    }
}
