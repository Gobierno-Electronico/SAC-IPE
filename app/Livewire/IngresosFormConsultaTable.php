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

class IngresosFormConsultaTable extends Tabla
{
    public $tipo;
    public $registros = [];
    public $numeroEvento;
    public $numeroPoliza;
    public $searchBy = [];
    public $fecha;
    public $hora;
    public $validado = false;
    public $concepto;
    public $estado;
    public $estadoOriginal;
    public $totalAumentado = 0;
    public $totalDisminuido = 0;
    public $tipoPoliza;
    public $observaciones;
    public $urlFinalizar;
    public $numeroPolizaRemanente;

    public $tipoMovimiento;

    public function render()
    {

        return view('livewire.ingresos-form-consulta-table');
    }

    public function init()
    {
        $poliza = $this->data()->first();
        $this->fecha = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('d/m/Y') : '01/01/' . Carbon::now()->year;
        $this->hora = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('H:i:s') : '11:00:00';
        $this->concepto = ($poliza) ? $poliza->descripcion : 'SIN CONCEPTO';
        $this->sortBy = 'cuenta';
    }

    public function query(): Builder
    {
        return Poliza::query();
    }

    public function data()
    {
        $datos = $this
            ->query()
            ->when($this->sortBy !== '', function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })
            ->where('tipo_poliza', '=', $this->tipoPoliza)
            ->where('numero_poliza', '=', $this->numeroPoliza)
            ->where('evento', '=', $this->numeroEvento)
            ->search($this->searchBy, $this->searchTerm)
            ->paginate($this->perPage);
        return $datos;
    }

    public function columns(): array
    {
            return [
                Column::make('cuenta', 'Cuenta'),
                Column::make('concepto', 'Descripción'),
                Column::make('mes', 'Mes'),
                Column::make('total', 'Importe')->component('columns.importe'),
                Column::make('evento', 'Evento'),
                Column::make('validado', 'Validado')->component('columns.validado'),
            ];
    }

    public function borrar()
    {
        try {
            DB::beginTransaction();
            if ($this->validado)
                return;
            // dd($this->numeroEvento);
            Poliza::searchByYear('fecha', Carbon::now()->year)->where('tipo_poliza', '=', $this->tipoPoliza)->where('evento', '=', $this->numeroEvento)->where('validado','=', false)->delete();
            if ($this->numeroPolizaRemanente && $this->numeroPolizaRemanente > 0) {
                Poliza::searchByYear('fecha', Carbon::now()->year)->where('tipo_poliza', '=', 'IAUX')->where('evento', '=', $this->numeroEvento)->where('validado','=', false)->delete();
            }
            // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'INGRESOS')->where('tipo', '=', 'P')->delete();
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('borrar', 'borró o intentó borrar la Reclasificación/Recalendarización con número de evento: ' . $this->numeroEvento, request());
            $this->validado = true;
            // $this->dispatch('mostrarMensaje', mensaje: 'Se borró el movimiento de Reclasificación/Recalendarización', tipo: 'success', tiempo: 3000);
            $this->dispatch('cancelar-movimiento');
            DB::commit();
            return redirect($this->urlFinalizar)->with(['message' => 'Se borró el movimiento de Reclasificación/Recalendarización', 'message_type' => 'success'])->setStatusCode(422);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al borrar el movimiento', tipo: 'error', tiempo: 3000);
        }
    }

    public function validar()
    {
        try {
            DB::beginTransaction();
            Poliza::searchByYear('fecha', Carbon::now()->year)->where('tipo_poliza', '=', $this->tipoPoliza)->where('evento', '=', $this->numeroEvento)->update(["validado" => true]);
            if ($this->numeroPolizaRemanente > 0) {
                Poliza::searchByYear('fecha', Carbon::now()->year)->where('tipo_poliza', '=','IAUX')->where('evento', '=', $this->numeroEvento)->update(["validado" => true]);
            }
            // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'INGRESOS')->where('tipo', '=', 'P')->update(["validado" => true]);
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('validarPresupuestoInicial', 'validó o intentó validar la Reclasificación/Recalendarización con número de evento: ' . $this->numeroEvento, request());
            $this->validado = true;
            DB::commit();
            $this->dispatch('mostrarMensaje', mensaje: 'Se validó la ampliación de ingresos', tipo: 'success', tiempo: 3000);
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al validar la ampliación', tipo: 'error', tiempo: 3000);
        }
    }


    public function edit($value)
    {
    }

    public function changeState($value)
    {
    }

    public function finalizar($tipo)
    {
        $bitacora = new BitacoraController();
        $bitacora->bitacora('agregarRegistro', 'concluyó o intentó concluir el' . $tipo . ' con evento : ' . $this->numeroEvento, request());
        $this->dispatch('mostrarMensaje', mensaje: 'Se realizó el registro del ingreso por clasificar con éxito', tipo: 'success', tiempo: 5000);
        $this->dispatch('reiniciar');
        $this->numeroEvento = 0;
        $this->numeroPoliza = 0;

    }
}
