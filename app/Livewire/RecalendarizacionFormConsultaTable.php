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

class RecalendarizacionFormConsultaTable extends Tabla
{
    public $tipo;
    public $registros = [];
    public $numeroEvento;
    public $numeroPoliza;
    public $searchBy = ['concepto', 'area', 'tipo_poliza', 'numero_poliza', 'cuenta', 'total', 'mes'];
    public $fecha;
    public $hora;
    public $validado = false;
    public $concepto;
    public $estado;
    public $estadoOriginal;
    public $totalAumentado = 0;
    public $totalDisminuido = 0;
    public $urlFinalizar = "";
    public $categoriaModulo = "";
    public $tipoMovimiento;

    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }

    public function render()
    {

        $poliza = Poliza::where('numero_poliza', '=', $this->numeroPoliza)
            ->where('tipo_poliza', '=', 'D')
            ->where('evento', '=', $this->numeroEvento)
            ->whereYear('fecha', '=', (string) $this->anio)
            ->first();

        if ($poliza['validado'] == 1) {
            $this->validado = true;
            $this->init();
        }
        return view('livewire.recalendarizacion-form-consulta-table');
    }

    public function init()
    {
        $poliza = $this->data()->first();
        $this->fecha = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('d/m/Y') : '01/01/' . (string) $this->anio;
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
            ->where('tipo_poliza', '=', 'D')
            ->where('numero_poliza', '=', $this->numeroPoliza)
            ->whereYear('fecha', '=', (string) $this->anio)
            ->where('evento', '=', $this->numeroEvento)
            ->search($this->searchBy, $this->searchTerm)
            ->paginate($this->perPage);
        return $datos;
    }

    public function columns(): array
    {

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

    public function borrar()
    {
        try {
            DB::beginTransaction();
            if ($this->validado)
                return;
            // dd($this->numeroEvento);
            Poliza::searchByYear('fecha', (string) $this->anio)->where('tipo_poliza', '=', 'D')->where('evento', '=', $this->numeroEvento)
            ->where('categoria', '=', $this->categoriaModulo)
            ->delete();
            // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'INGRESOS')->where('tipo', '=', 'P')->delete();
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('borrar', 'borró o intentó borrar el movimiento de ' . $this->categoriaModulo . ' con número de evento: ' . $this->numeroEvento, request());
            $this->validado = true;
            // $this->dispatch('mostrarMensaje', mensaje: 'Se borró el movimiento de Reclasificación/Recalendarización', tipo: 'success', tiempo: 3000);
            $this->dispatch('cancelar-movimiento');

            switch($this->categoriaModulo){
                case 'DEUDORES REINTEGRO ANTICIPOS':
                    Poliza::where('categoria', '=', 'DEUDORES OTORGAMIENTO ANTICIPOS')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', (string) $this->anio)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
                case 'DEUDORES COMPROBACION ANTICIPOS':
                    Poliza::where('categoria', '=', 'DEUDORES REINTEGRO ANTICIPOS')                    
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', (string) $this->anio)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);

                    Poliza::where('categoria', '=', 'DEUDORES OTORGAMIENTO ANTICIPOS')                    
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', (string) $this->anio)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
            }

            DB::commit();
            return redirect($this->urlFinalizar)->with(['message' => 'Se borró el movimiento de ' . $this->categoriaModulo, 'message_type' => 'success']);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al borrar el movimiento', tipo: 'error', tiempo: 3000);
        }
    }

    public function validar()
    {
        try {
            DB::beginTransaction();
            Poliza::searchByYear('fecha', (string) $this->anio)->where('tipo_poliza', '=', 'D')->where('evento', '=', $this->numeroEvento)->update(["validado" => true]);
            // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'INGRESOS')->where('tipo', '=', 'P')->update(["validado" => true]);
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('validarPresupuestoInicial', 'validó o intentó validar el movimiento de ' .  $this->categoriaModulo . ' con número de evento: ' . $this->numeroEvento, request());
            $this->validado = true;
            DB::commit();
            $this->dispatch('mostrarMensaje', mensaje: 'Se validó el movimiento de '. $this->categoriaModulo, tipo: 'success', tiempo: 3000);
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al validar el movimiento de ' .$this->categoriaModulo, tipo: 'error', tiempo: 3000);
        }
    }


    public function edit($value) {}

    public function changeState($value) {}

    public function finalizar($tipo)
    {
        $bitacora = new BitacoraController();
        $bitacora->bitacora('agregarRegistro', 'concluyó o intentó concluir el registro de' . $this->categoriaModulo . ' con evento : ' . $this->numeroEvento, request());
        $this->numeroEvento = 0;
        $this->numeroPoliza = 0;
        return redirect($this->urlFinalizar)->with(['message' => 'Se realizó el registro de ' . $this->categoriaModulo . ' con éxito', 'message_type' => 'success']);
    }
    public function regresar()
    {
        return redirect($this->urlFinalizar);
    }
}
