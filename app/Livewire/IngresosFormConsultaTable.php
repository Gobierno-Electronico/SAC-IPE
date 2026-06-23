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
use App\Enums\EstatusEvento;

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
    public $categoriaModulo;
    public $categoriaRemanente;

    public $tipoMovimiento;
    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }
    
    public function render()
    {
        $poliza = Poliza::where('numero_poliza', '=', $this->numeroPoliza)
            ->where('tipo_poliza', '=', $this->tipoPoliza)
            ->where('evento', '=', $this->numeroEvento)
            ->whereYear('fecha', '=', (string) $this->anio)
            ->first();

            if($poliza['validado'] == 1){ 
                $this->validado = true;
                $this->init();
            } 



        return view('livewire.ingresos-form-consulta-table');
    }

    public function init()
    {
        $poliza = $this->data()->first();
        $this->fecha = ($poliza) ? date('d-m-Y', strtotime($poliza->fecha)) : '01/01/' . (string) $this->anio;
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
            ->whereYear('fecha', '=', (string) $this->anio)
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
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('borrar', 'borró o intentó borrar un movimiento de ' . $this->categoriaModulo . ' con número de evento: ' . $this->numeroEvento, request());
            $movimientoValidado = Poliza::searchByYear('fecha', (string) $this->anio)
                ->where('tipo_poliza', '=', $this->tipoPoliza)
                ->where('evento', '=', $this->numeroEvento)
                ->where('numero_poliza', '=', $this->numeroPoliza)
                ->where('categoria', '=', $this->categoriaModulo)
                ->where('validado', '=', true)
                ->exists();
            if ($movimientoValidado && auth()->user()?->puede('botonBorrarMovimiento') !== true) {
                $this->dispatch('mostrarMensaje', mensaje: 'No tiene permiso para borrar movimientos validados', tipo: 'error', tiempo: 3000);
                return;
            }
            DB::beginTransaction();
            Poliza::searchByYear('fecha', (string) $this->anio)
                ->where('tipo_poliza', '=', $this->tipoPoliza)
                ->where('evento', '=', $this->numeroEvento)
                ->where('numero_poliza', '=', $this->numeroPoliza)
                ->where('categoria', '=', $this->categoriaModulo)
                ->when(!$movimientoValidado, function ($query) {
                    $query->where('validado', '=', false);
                })->delete();
            if ($this->numeroPolizaRemanente && $this->numeroPolizaRemanente > 0) {
                Poliza::searchByYear('fecha', (string) $this->anio)
                    ->where('tipo_poliza', '=', 'IAUX')
                    ->where('evento', '=', $this->numeroEvento)
                    ->where('categoria', '=', $this->categoriaRemanente)
                    ->where('numero_poliza', '=', $this->numeroPolizaRemanente)
                    ->when(!$movimientoValidado, function ($query) {
                        $query->where('validado', '=', false);
                    })->delete();
            }

            switch ($this->categoriaModulo) {
                case 'INGRESOS DEVENGADO PREVIAMENTE RECAUDADO':
                    Poliza::where('categoria', '=', 'INGRESOS POR CLASIFICAR')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', (string) $this->anio)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;

                case 'INGRESOS RECAUDADO':
                    Poliza::where('categoria', '=', 'INGRESOS DEVENGADO')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', (string) $this->anio)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;

                case 'INGRESOS COBRO ESPECIE':
                    Poliza::where('categoria', '=', 'INGRESOS DEVENGADO')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', (string) $this->anio)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
            }

            // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'INGRESOS')->where('tipo', '=', 'P')->delete();
            $this->validado = true;
            // $this->dispatch('mostrarMensaje', mensaje: 'Se borró el movimiento de Reclasificación/Recalendarización', tipo: 'success', tiempo: 3000);
            $this->dispatch('cancelar-movimiento');
            DB::commit();
            return redirect($this->urlFinalizar)->with(['message' => 'Se borró el movimiento de ' . $this->categoriaModulo, 'message_type' => 'success']);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al borrar el movimiento de ' . $this->categoriaModulo, tipo: 'error', tiempo: 3000);
        }
    }

    public function validar()
    {
        try {
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('validar', 'validó o intentó validar un movimiento de ' . $this->categoriaModulo . ' con número de evento: ' . $this->numeroEvento, request());
            DB::beginTransaction();
            Poliza::searchByYear('fecha', (string) $this->anio)
                ->where('tipo_poliza', '=', $this->tipoPoliza)
                ->where('evento', '=', $this->numeroEvento)
                ->where('categoria', '=', $this->categoriaModulo)
                ->where('numero_poliza', '=', $this->numeroPoliza)
                ->update(["validado" => true]);
            if ($this->numeroPolizaRemanente > 0) {
                Poliza::searchByYear('fecha', (string) $this->anio)
                    ->where('tipo_poliza', '=', 'IAUX')
                    ->where('evento', '=', $this->numeroEvento)
                    ->where('categoria', '=', $this->categoriaRemanente)
                    ->where('numero_poliza', '=', $this->numeroPolizaRemanente)
                    ->update(["validado" => true]);
            }
            // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'INGRESOS')->where('tipo', '=', 'P')->update(["validado" => true]);
            $this->validado = true;
            DB::commit();
            $this->dispatch('mostrarMensaje', mensaje: 'Se validó el movimiento de ' . $this->categoriaModulo, tipo: 'success', tiempo: 3000);
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al validar el movimiento de ' . $this->categoriaModulo, tipo: 'error', tiempo: 3000);
        }
    }


    public function edit($value) {}

    public function changeState($value) {}

    public function finalizar($tipo)
    {
        if(!$this->validado){
            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizar', 'concluyó o intentó concluir el ' . $tipo . ' con evento : ' . $this->numeroEvento, request());
            $this->dispatch('mostrarMensaje', mensaje: 'Se realizó el registro del ingreso de ' . $this->categoriaModulo . ' con éxito', tipo: 'success', tiempo: 5000);
            $this->numeroEvento = 0;
            $this->numeroPoliza = 0;
            return redirect($this->urlFinalizar)->with(['message' => 'Se realizó el registro del ingreso de ' . $this->categoriaModulo . ' con éxito', 'message_type' => 'success']);
        }else{
            return redirect($this->urlFinalizar);
        }
    }

    public function regresar()
    {
        return redirect($this->urlFinalizar);
    }
}
