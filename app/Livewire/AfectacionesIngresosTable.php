<?php

namespace App\Livewire;

use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Clases\Column;
use App\Models\CodigoDepartamento;
use App\Http\Controllers\BitacoraController;
use App\Models\Cuenta;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;

class AfectacionesIngresosTable extends Tabla
{
    #[Reactive]
    public $selectCodigoDepartamento;

    #[Reactive]
    public $codigoCuentaCargo;
    #[Reactive]
    public $codigoCuentaAbono;

    #[Reactive]
    public $tipo;

    #[Reactive]
    public $estado;

    #[Reactive]
    public $observaciones;


    #[Reactive]
    public $codigoCuentaCargoEgreso;
    #[Reactive]
    public $codigoCuentaAbonoEgreso;

    #[Reactive]
    public $numeroEvento;
    public $mensaje = '';

    public $mesSeleccionado;

    public $importe;


    public $cacheData = [];
    public $registros = [];

    public $total = 0;
    public $totalPrevio;
    public $totalProceso = 0;
    public int $anio;


    public function render()
    {
        // dd($this->searchTerm);
        return view('livewire.afectaciones-ingresos-table');
    }

    public function query(): Builder
    {
        return Poliza::query();
    }

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }

    public function data()
    {
        if (count($this->cacheData) > 0) {
            return $this->cacheData;
        }
        if ($this->estado == 'INGRESOS') {
            $departamento = CodigoDepartamento::find($this->selectCodigoDepartamento);
            $cuenta = Cuenta::where("Codigo_cuenta", "=", $this->codigoCuentaAbono)->first();
            $resultados = [];
            if ($departamento && $cuenta && count($this->cacheData) < 1) {
                // 1. Ejecutamos el Store Procedure
                $resultados = DB::select('EXEC AfectacionesLiquidasTabla @area = ?, @cuenta = ?, @anio = ?', [
                    $departamento->Codigo_completo,
                    $cuenta->Codigo_cuenta,
                    $this->anio
                ]);

                // 2. Si no hay datos, generamos la estructura de 12 meses en cero
                if (empty($resultados)) {
                    $numeroPolizaPresupuestal = DB::table('polizas')
                        ->where('categoria', 'INICIAL INGRESOS')
                        ->where('tipo_poliza', 'P')
                        ->value('numero_poliza');
                    $meses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];

                    foreach ($meses as $mes) {
                        $nuevoObjeto = new \stdClass();
                        $nuevoObjeto->area = $departamento->Codigo_completo;
                        $nuevoObjeto->tipo_poliza = "P";
                        $nuevoObjeto->numero_poliza = $numeroPolizaPresupuestal; // Valor por defecto
                        $nuevoObjeto->cuenta = $cuenta->Codigo_cuenta;
                        $nuevoObjeto->mes = $mes;
                        $nuevoObjeto->Anio = (string)$this->anio;
                        $nuevoObjeto->TotalSumado = ".0000000000";
                        $nuevoObjeto->tipo_interaccion = "Presupuestal - Abono"; // Cambiado para Ingresos
                        $nuevoObjeto->categoria = "INICIAL INGRESOS";        // Cambiado para Ingresos
                        $nuevoObjeto->TotalSolvencia = ".0000000000";
                        $nuevoObjeto->PresupuestoActual = ".0000000000";
                        $nuevoObjeto->Importe = 0;
                        $nuevoObjeto->SolvenciaFinal = 0;
                        $nuevoObjeto->PresupuestoFinal = 0;

                        $resultados[] = $nuevoObjeto;
                    }
                } else {
                    // 3. Si vienen datos, inicializamos los campos de cálculo
                    $resultados = array_map(function ($entrada) {
                        $entrada->Importe = 0;
                        $entrada->SolvenciaFinal = 0;
                        $entrada->PresupuestoFinal = 0;
                        return $entrada;
                    }, $resultados);
                }
            }

            return $this->cacheData = $resultados;
        } else {
            $departamento = CodigoDepartamento::find($this->selectCodigoDepartamento);
            $cuenta = Cuenta::where("Codigo_cuenta", "=", $this->codigoCuentaCargoEgreso)->first();
            $resultados = [];
            if ($departamento && $cuenta && count($this->cacheData) < 1) {
                // 1. Intentamos obtener los datos de la DB
                $resultados = DB::select('EXEC AfectacionesLiquidasTabla @area = ?, @cuenta = ?, @anio = ?', [
                    $departamento->Codigo_completo,
                    $cuenta->Codigo_cuenta,
                    $this->anio
                ]);

                // 2. Si la DB no regresó nada, construimos el array por defecto
                if (empty($resultados)) {
                    $meses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
                    $numeroPolizaPresupuestalEgresos = DB::table('polizas')
                        ->where('categoria', 'INICIAL EGRESOS')
                        ->where('tipo_poliza', 'P')
                        ->value('numero_poliza');
                    foreach ($meses as $mes) {
                        $nuevoObjeto = new \stdClass();
                        $nuevoObjeto->area = $departamento->Codigo_completo;
                        $nuevoObjeto->tipo_poliza = "P";
                        $nuevoObjeto->numero_poliza = $numeroPolizaPresupuestalEgresos;
                        $nuevoObjeto->cuenta = $cuenta->Codigo_cuenta;
                        $nuevoObjeto->mes = $mes;
                        $nuevoObjeto->Anio = (string)$this->anio;
                        $nuevoObjeto->TotalSumado = ".0000000000";
                        $nuevoObjeto->tipo_interaccion = "Presupuestal - Cargo";
                        $nuevoObjeto->categoria = "INICIAL EGRESOS";
                        $nuevoObjeto->TotalSolvencia = ".0000000000";
                        $nuevoObjeto->PresupuestoActual = ".0000000000";
                        $nuevoObjeto->Importe = 0;
                        $nuevoObjeto->SolvenciaFinal = 0;
                        $nuevoObjeto->PresupuestoFinal = 0;

                        $resultados[] = $nuevoObjeto;
                    }
                } else {
                    // 3. Si hay datos, aplicamos el mapeo
                    $resultados = array_map(function ($entrada) {
                        $entrada->Importe = 0;
                        $entrada->SolvenciaFinal = 0;
                        $entrada->PresupuestoFinal = 0;
                        return $entrada;
                    }, $resultados);
                }
            }
            return $this->cacheData = $resultados;
        }
    }

    public function columns(): array
    {
        return [
            Column::make('mes', 'Mes')->component('columns.seleccionMesAfectaciones'),
            Column::make('PresupuestoActual', 'Presupuesto actual')->component('columns.importe'),
            Column::make('TotalSolvencia', 'Solvencia actual')->component('columns.importe'),
            Column::make('Importe', 'Importe')->component('columns.importe'),
            Column::make('PresupuestoFinal', 'Presupuesto final')->component('columns.importe'),
            Column::make('SolvenciaFinal', 'Solvencia final')->component('columns.importe'),

        ];
    }

    public function edit($value) {}

    public function changeState($value) {}

    public function agregar()
    {

        if (!$this->mesSeleccionado) {
            $this->dispatch('mensaje', mensaje: 'Seleccione un mes de la tabla', tipo: 'warning');
            return;
        }
        $this->importe = preg_replace('/[^0-9.]/', '', $this->importe);
        if ($this->importe == "") {
            $this->dispatch('mensaje', mensaje: 'Ingrese un importe', tipo: 'warning');
            return;
        }

        $key = array_search($this->mesSeleccionado, array_column($this->cacheData, 'mes'));
        if (($this->tipo == "Reducción" && $this->cacheData[$key]->TotalSolvencia < $this->importe)) {
            $this->dispatch('mensaje', mensaje: 'No hay solvencia suficiente para aplicar la reducción al presupuesto de ' . strtolower($this->mesSeleccionado), tipo: 'warning');
            return;
        }
        if (is_string($this->importe)) {
            $valor = str_replace(['$', ','], '', $this->importe);
            $this->importe = (float)$valor;
        }
        $importeConSigno = ($this->tipo == "Reducción") ? -$this->importe : $this->importe;
        $this->cacheData[$key]->Importe = $this->importe;
        $this->cacheData[$key]->PresupuestoFinal = $this->cacheData[$key]->PresupuestoActual + $importeConSigno;
        $this->cacheData[$key]->SolvenciaFinal = $this->cacheData[$key]->TotalSolvencia + $importeConSigno;
        $this->total = 0;
        if ($this->importe == 0) {
            $this->cacheData[$key]->PresupuestoFinal = 0;
            $this->cacheData[$key]->SolvenciaFinal = 0;
        }
        foreach ($this->cacheData as $key => $value) {
            $this->total += $value->Importe;
        }
        $this->total = number_format($this->total, 2, '.', ',');
        $this->total = '$' . $this->total;
        $this->dispatch('actualizar-total', total: $this->total);
        // $this->cacheData[$key] = $this->observaciones;
    }

    public function seleccionarMes($mes)
    {
        $this->mesSeleccionado = $mes;
        $this->dispatch('seleccionar-mes', mes: $mes);
    }

    #[On('clean')]
    public function limpiar()
    {
        $this->importe = "";
        $this->mesSeleccionado = "";
        $this->cacheData = [];
        $this->total = 0;
    }

    #[On('cancelar-movimiento')]
    public function cancelarMovimiento()
    {
        $this->registros = [];
    }

    public function agregarRegistro()
    {
        $bitacora = new BitacoraController();
        $bitacora->bitacora('agregarRegistro', 'agregó o intentó agregar un registro a una ' . $this->tipo . ' que está generando', request());
        array_push($this->registros, $this->cacheData);
        $this->dispatch('suma-total', total: $this->total);
        $this->dispatch("clean");
        $this->dispatch("reset-data");
        $this->dispatch('mensaje', mensaje: 'Registro guardado', tipo: 'info');
        $total = 0;
        foreach ($this->registros as $registro) {
            foreach ($registro as $value) {
                $total += $value->Importe;
            }
        }
        $this->totalProceso = $total;
        $this->totalPrevio = floatval(str_replace(["$", ","], "", $this->totalPrevio));

    }

    public function finalizarRegistros()
    {
        if (!$this->observaciones) {
            $this->dispatch('mensaje', mensaje: 'Ingrese las observaciones de la ampliación', tipo: 'warning');
            return;
        }
        if ($this->totalPrevio > 0 && $this->totalProceso !== $this->totalPrevio) {
            $mensaje = "Balance erroneo entre ingresos y egresos, ";
            $mensaje .= ($this->estado == 'INGRESOS') ? "importe ingresos: $" . number_format($this->totalProceso, 2, '.', ',') . " importe egresos: $" . number_format($this->totalPrevio, 2, '.', ',')
                : "importe ingresos: $" . number_format($this->totalPrevio, 2, '.', ',') . " importe egresos: $" . number_format($this->totalProceso, 2, '.', ',');
            $this->dispatch('mensaje', mensaje: $mensaje, tipo: 'warning', tiempo: 10000);
            return;
        }
        $this->dispatch("finalizarRegistrosIngresos", registros: $this->registros);
    }

    function borrar()
    {
        try {
            $movimientoValidado = Poliza::searchByYear('fecha', (string) $this->anio)
                ->where('tipo_poliza', '=', 'D')
                ->where('evento', '=', $this->numeroEvento)
                ->where('validado', '=', true)
                ->exists();
            if ($movimientoValidado && auth()->user()?->puede('botonBorrarMovimiento') !== true) {
                $this->dispatch('mostrarMensaje', mensaje: 'No tiene permiso para borrar movimientos validados', tipo: 'error', tiempo: 3000);
                return;
            }
            DB::beginTransaction();
            Poliza::searchByYear('fecha', (string) $this->anio)->where('tipo_poliza', '=', 'D')->where('evento', '=', $this->numeroEvento)->delete();
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

    public function movimientoValidado(): bool
    {
        if (!$this->numeroEvento) {
            return false;
        }

        return Poliza::searchByYear('fecha', (string) $this->anio)
            ->where('tipo_poliza', '=', 'D')
            ->where('evento', '=', $this->numeroEvento)
            ->where('validado', '=', true)
            ->exists();
    }
}
