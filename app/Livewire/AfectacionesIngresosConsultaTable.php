<?php

namespace App\Livewire;

use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Clases\Column;
use App\Http\Controllers\BitacoraController;
use Livewire\Attributes\Reactive;
use Carbon\Carbon;
use Log;

class AfectacionesIngresosConsultaTable extends Tabla
{
    #[Reactive]
    public $tipo;
    public $registros = [];
    public $numeroEvento;
    public $numeroPoliza;
    public $searchBy = ['concepto', 'area', 'tipo_poliza', 'numero_poliza', 'Codificacion_rubro_ingreso', 'cuenta', 'total', 'mes'];
    public $fecha;
    public $hora;
    public $validado = false;
    public $concepto;
    public $estado;
    public $estadoOriginal;
    public $sortBy = 'cuenta';

    public $totalPrevio;
    public $total;


    public function render()
    {

        return view('livewire.afectaciones-ingresos-consulta-table');
    }

    public function init()
    {
        $poliza = $this->data()->first();
        $this->fecha = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('d/m/Y') : '01/01/' . (string) $this->anio;
        $this->hora = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('H:i:s') : '11:00:00';
        $this->concepto = ($poliza) ? $poliza->descripcion : 'SIN CONCEPTO';
        $this->sortBy = $this->estado == 'INGRESOS' ? 'CRI' : 'cuenta';
    }

    public function query(): Builder
    {
        return Poliza::query();
    }

    public function data()
    {
        if ($this->estado == 'INGRESOS') {
            $datos = $this
                ->query()
                ->join('clasificador_rubro_ingreso', 'polizas.cuenta', '=', 'clasificador_rubro_ingreso.Cuenta_contable')
                ->when($this->sortBy !== '', function ($query) {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                })
                ->select('polizas.*', 'clasificador_rubro_ingreso.Codificacion_rubro_ingreso as CRI')
                ->where('tipo_poliza', '=', 'D')
                ->where('numero_poliza', '=', $this->numeroPoliza)
                ->where('evento', '=', $this->numeroEvento)
                ->search($this->searchBy, $this->searchTerm)
                ->paginate($this->perPage);
            return $datos;
        } else {
            $datos = $this
                ->query()
                ->when($this->sortBy !== '', function ($query) {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                })
                ->where('tipo_poliza', '=', 'D')
                ->where('numero_poliza', '=', $this->numeroPoliza)
                ->where('evento', '=', $this->numeroEvento)
                ->search($this->searchBy, $this->searchTerm)
                ->paginate($this->perPage);
            return $datos;
        }
    }

    public function columns(): array
    {
        if ($this->estado == 'INGRESOS') {
            return [
                Column::make('area', 'Area'),
                Column::make('CRI', 'Rubro'),
                Column::make('cuenta', 'Cuenta'),
                Column::make('concepto', 'Concepto'),
                Column::make('mes', 'Mes'),
                Column::make('total', 'Total')->component('columns.importe'),
                Column::make('evento', 'No. de evento'),
                Column::make('validado', 'Validado')->component('columns.validado'),

            ];
        } else {
            return [
                Column::make('area', 'Area'),
                Column::make('cuenta', 'Cuenta'),
                Column::make('concepto', 'Concepto'),
                Column::make('mes', 'Mes'),
                Column::make('total', 'Total')->component('columns.importe'),
                Column::make('evento', 'No. de evento'),
                Column::make('validado', 'Validado')->component('columns.validado'),

            ];
        }
    }

    public function borrar()
    {
        try {
            DB::beginTransaction();
            if ($this->validado)
                return;
            // dd($this->numeroEvento);
            Poliza::searchByYear('fecha', (string) $this->anio)->where('tipo_poliza', '=', 'D')->where('evento', '=', $this->numeroEvento)->delete();
            // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'INGRESOS')->where('tipo', '=', 'P')->delete();
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('borrar', 'borró o intentó borrar la ampliación con número de evento: ' . $this->numeroEvento, request());
            $this->validado = true;
            $this->dispatch('mostrarMensaje', mensaje: 'Se borró el movimiento de ampliación', tipo: 'success', tiempo: 3000);
            $this->dispatch('cancelar-movimiento');

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al borrar el movimiento de ampliación', tipo: 'error', tiempo: 3000);
        }
    }

    public function validar()
    {
        try {
            DB::beginTransaction();
            $categoria = ($this->tipo == "Ampliación") ? 'AMPLIACION ' . $this->estado : 'REDUCCION ' . $this->estado;
            Poliza::searchByYear('fecha', (string) $this->anio)->where('categoria', '=', $categoria)->where('tipo_poliza', '=', 'D')->where('evento', '=', $this->numeroEvento)->update(["validado" => true]);

            // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'INGRESOS')->where('tipo', '=', 'P')->update(["validado" => true]);
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('validarPresupuestoInicial', 'validó o intentó validar la ampliación con número de evento: ' . $this->numeroEvento, request());
            $this->validado = true;
            DB::commit();
            $this->dispatch('mostrarMensaje', mensaje: 'Se validó la ampliación de ingresos', tipo: 'success', tiempo: 3000);
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al validar la ampliación', tipo: 'error', tiempo: 3000);
        }
    }

    public function continuarAmpliacion($estado)
    {
        $this->dispatch('continuar-ampliacion', estado: $estado, totalPrevio: $this->total);
    }

    public function edit($value)
    {
    }

    public function changeState($value)
    {
    }

    public function finalizar($tipo) {
        $bitacora = new BitacoraController();
        $bitacora->bitacora('agregarRegistro', 'concluyó o intentó concluir la ' .$tipo. ' con evento : '.$this->numeroEvento, request());
        $this->dispatch('mostrarMensaje', mensaje: 'Se realizó la ' . $tipo . ' con éxito', tipo: 'success', tiempo: 5000);
        $this->dispatch('cancelar-movimiento');
        $this->dispatch('reiniciar-estado');
    }
}
