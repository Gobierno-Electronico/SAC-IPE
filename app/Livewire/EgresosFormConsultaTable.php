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
use App\Livewire\Tabla;
use Log;
use App\Enums\EstatusEvento;

class EgresosFormConsultaTable extends Tabla
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

    public $motivoLiberacion;
    public $liberado = false;


    public function render()
    {
        try{
            $poliza = Poliza::where('numero_poliza', '=', $this->numeroPoliza)
                ->where('tipo_poliza', '=', $this->tipoPoliza)
                ->where('evento', '=', $this->numeroEvento)
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->first();

            if ($poliza['validado'] == 1) {
                $this->validado = true;
                $this->init();
            }
            
            $polizaLiberacion = Poliza::where('numero_poliza', '=', $this->numeroPolizaRemanente)
            ->where('tipo_poliza', '=', 'EAUX')
            ->where('categoria', 'LIKE', '%'. 'LIBERACION' .'%')
            ->where('evento', '=', $this->numeroEvento)
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->first();

            if($polizaLiberacion != NULL){
                $this->liberado = true;
            }

            return view('livewire.egresos-form-consulta-table');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al verificar validación de poliza ' . $this->categoriaModulo, tipo: 'error', tiempo: 3000);
        }
    }

    public function init()
    {
        $poliza = $this->data()->first();
        $this->fecha = ($poliza) ? date('d-m-Y', strtotime($poliza->fecha)) : '01/01/' . Carbon::now()->year;
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
            ->whereYear('fecha', '=', Carbon::now()->year)
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

    public function liberarRemanente()
    {
        if (empty($this->motivoLiberacion)) {
            $this->dispatch('mostrarMensaje', mensaje: 'El motivo de liberación es obligatorio', tipo: 'error', tiempo: 4000);
            return;
        }

        try {
            DB::beginTransaction();
            $cuentasComprometidas = Poliza::where('evento', '=', $this->numeroEvento)
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', 'LIKE', '%' . 'COMPROMETIDO' . '%')
                ->get();
    
            $cuentasRemanente =  Poliza::where('evento', '=', $this->numeroEvento)
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'EAUX')
                ->where('categoria', 'LIKE', '%' . 'REMANENTE DEVENGADO' . '%')
                ->where('numero_poliza', '=', $this->numeroPolizaRemanente)
                ->get();
    
            $polizasLiberacionRemanente = collect();

            foreach($cuentasComprometidas as $comprometida){
                foreach($cuentasRemanente as $remanente){
                    if($comprometida->cuenta == $remanente->cuenta){
                        $nuevoTotal = $comprometida->total - $remanente->total;
                        $comprometida->total = $nuevoTotal;
                        $comprometida->save();
    
    
                        $remanenteLiberado = $remanente->replicate();
    
                        if($remanenteLiberado->tipo_interaccion == 'Presupuestal - Cargo'){
                            $remanenteLiberado->tipo_interaccion = 'Presupuestal - Abono';
                        }else{
                            $remanenteLiberado->tipo_interaccion = 'Presupuestal - Cargo';
                        }
                        $remanenteLiberado->descripcion = $this->motivoLiberacion;
                        $separacionConceptosCategoria = explode(' ', $this->categoriaModulo);
                        $numeroCapitulo = end($separacionConceptosCategoria);
                        $remanenteLiberado->categoria = 'LIBERACION EGRESOS COMPROMETIDO CAPITULO ' . $numeroCapitulo . ' REMANENTE DEVENGADO';
                        $polizasLiberacionRemanente->push($remanenteLiberado);
                    }
                }
            }
            foreach ($polizasLiberacionRemanente as $polizaRemanenteLiberado) {
                $polizaRemanenteLiberado->save();
            }

            //desactivamos el comprometido del evento ya que no tendrá más recurso por devengar
            Poliza::where('categoria', 'LIKE', '%'.'EGRESOS COMPROMETIDO CAPITULO'.'%')
                ->where('evento', '=', $this->numeroEvento)
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->update(['estatus_evento' => EstatusEvento::FINALIZADO->value]);
            
            $this->validar();

            DB::commit();

            $this->dispatch('mostrarMensaje', mensaje: 'Liberación exitosa', tipo: 'success', tiempo: 3000);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error al liberar remanente'. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al liberar remanente de egresos', tipo: 'error', tiempo: 3000);
        }

    }

    public function borrar()
    {
        try {
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('borrar', 'borró o intentó borrar un movimiento de ' . $this->categoriaModulo . ' con número de evento: ' . $this->numeroEvento, request());
            DB::beginTransaction();
            if ($this->validado)
                return;
            Poliza::searchByYear('fecha', Carbon::now()->year)
                ->where('tipo_poliza', '=', $this->tipoPoliza)
                ->where('numero_poliza', '=', $this->numeroPoliza)
                ->where('evento', '=', $this->numeroEvento)
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('categoria', '=', $this->categoriaModulo)
                ->where('numero_poliza', '=', $this->numeroPoliza)
                ->where('validado', '=', false)->delete();
            if ($this->numeroPolizaRemanente && $this->numeroPolizaRemanente > 0) {
                Poliza::searchByYear('fecha', Carbon::now()->year)
                    ->where('tipo_poliza', '=', 'EAUX')
                    ->where('evento', '=', $this->numeroEvento)
                    ->whereYear('fecha', '=', Carbon::now()->year)
                    ->where('categoria', '=', $this->categoriaRemanente)
                    ->where('numero_poliza', '=', $this->numeroPolizaRemanente)
                    ->where('validado', '=', false)->delete();
            }

            switch ($this->categoriaModulo) {
                case "EGRESOS DEVENGADO CAPITULO 1":
                    Poliza::where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 1')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
                case "EGRESOS EJERCIDO CAPITULO 1":
                    Poliza::where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 1')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
                case "EGRESOS PAGADO CAPITULO 1":
                    Poliza::where('categoria', '=', 'EGRESOS EJERCIDO CAPITULO 1')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;


                case "EGRESOS DEVENGADO CAPITULO 2y3":
                    Poliza::where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 2y3')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
                case "EGRESOS EJERCIDO CAPITULO 2y3":
                    Poliza::where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 2y3')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
                case "EGRESOS PAGADO CAPITULO 2y3":
                    Poliza::where('categoria', '=', 'EGRESOS EJERCIDO CAPITULO 2y3')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;


                case "EGRESOS DEVENGADO CAPITULO 4":
                    Poliza::where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 4')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
                case "EGRESOS EJERCIDO CAPITULO 4":
                    Poliza::where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 4')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
                case "EGRESOS PAGADO CAPITULO 4":
                    Poliza::where('categoria', '=', 'EGRESOS EJERCIDO CAPITULO 4')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;


                case "EGRESOS DEVENGADO CAPITULO 5":
                    Poliza::where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 5')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
                case "EGRESOS EJERCIDO CAPITULO 5":
                    Poliza::where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 5')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
                case "EGRESOS PAGADO CAPITULO 5":
                    Poliza::where('categoria', '=', 'EGRESOS EJERCIDO CAPITULO 5')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;


                case "EGRESOS DEVENGADO CAPITULO 7":
                    Poliza::where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 7')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
                case "EGRESOS EJERCIDO CAPITULO 7":
                    Poliza::where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 7')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::ACTIVO->value]);
                    break;
                case "EGRESOS PAGADO CAPITULO 7":
                    Poliza::where('categoria', '=', 'EGRESOS EJERCIDO CAPITULO 7')
                        ->where('evento', '=', $this->numeroEvento)
                        ->whereYear('fecha', '=', Carbon::now()->year)
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
            Poliza::searchByYear('fecha', Carbon::now()->year)
                ->where('tipo_poliza', '=', $this->tipoPoliza)
                ->where('evento', '=', $this->numeroEvento)
                ->where('categoria', '=', $this->categoriaModulo)
                ->where('numero_poliza', '=', $this->numeroPoliza)
                ->update(["validado" => true]);
            if ($this->numeroPolizaRemanente > 0) {
                Poliza::searchByYear('fecha', Carbon::now()->year)
                    ->where('tipo_poliza', '=', 'EAUX') //CHECAR
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
        if (!$this->validado) {
            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizar', 'concluyó o intentó concluir el ' . $tipo . ' con evento : ' . $this->numeroEvento, request());
            $this->dispatch('mostrarMensaje', mensaje: 'Se realizó el registro del ingreso de ' . $this->categoriaModulo . ' con éxito', tipo: 'success', tiempo: 5000);
            $this->numeroEvento = 0;
            $this->numeroPoliza = 0;
            return redirect($this->urlFinalizar)->with(['message' => 'Se realizó el registro del ingreso de ' . $this->categoriaModulo . ' con éxito', 'message_type' => 'success']);
        } else {
            $this->regresar();
        }
    }

    public function regresar()
    {
        return redirect($this->urlFinalizar);
    }
}
