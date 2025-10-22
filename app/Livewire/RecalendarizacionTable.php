<?php

namespace App\Livewire;

use App\Clases\Column;
use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use App\Models\ClasificadorObjetoGasto;
use App\Models\CodigoDepartamento;
use App\Models\Cuenta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;
use App\Http\Controllers\BitacoraController;

class RecalendarizacionTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $totalAumentado = 0;
    public $totalDisminuido = 0;
    public $numeroPoliza;
    public $numeroEvento;

    public function render()
    {
        return view('livewire.recalendarizacion-table');
    }

    public function query(): Builder
    {
        return Poliza::query();
    }

    public function columns(): array
    {
        return [
            Column::make('areaNombre', 'Área'),
            Column::make('COG', 'Partida'),
            Column::make('mes', 'mes'),
            Column::make('afectacion', 'Afectación'),
            Column::make('inicial', 'Inicial')->component('columns.importe'),
            Column::make('aumentado', 'Importe aumentado')->component('columns.importe'),
            Column::make('disminuido', 'Importe dismiuido')->component('columns.importe'),
            Column::make('final', 'Final')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesRecalendarizacion'),
        ];
    }

    public function data()
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = array_slice($this->cacheData, $this->perPage * ($currentPage - 1), $this->perPage);
        return new LengthAwarePaginator($currentItems, count($this->cacheData), $this->perPage, $currentPage);
    }

    public function edit($id)
    {
        foreach ($this->cacheData as $key => $registro) {
            if ($registro['id'] == $id) {
                $importe = 0;
                $afectacion = '';
                $areaResponsable = $registro['areaNombre'];
                $cog = $registro['COG'];
                $mes = $registro['mes'];
                $movimiento = $registro['afectacion'];
                $solvencia = $registro['inicial'];

                if ($registro['aumentado'] > 0) {
                    $importe = $registro['aumentado'];
                    $afectacion = 'Aumento';
                } else {
                    $importe = $registro['disminuido'];
                    $afectacion = 'Disminucion';
                }
                unset($this->cacheData[$key]);
                $this->dispatch('llenar-formulario', $areaResponsable, $cog, $mes, $movimiento, $solvencia, $afectacion, $importe);
                break;
            }
        }

        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                unset($this->dataCompleta[$key]);
                break;
            }
        }
        // Recalculamos los totales solo después de eliminar el registro
        $totalAumentadoActualizado = array_sum(array_column($this->cacheData, 'aumentado'));
        $totalDisminuidoActualizado = array_sum(array_column($this->cacheData, 'disminuido'));

        $this->dispatch('cambioTotales', aumento: $totalAumentadoActualizado, disminucion: $totalDisminuidoActualizado);
    }

    public function eliminarRegistro($id)
    {
        foreach ($this->cacheData as $key => $registro) {
            if ($registro['id'] == $id) {
                unset($this->cacheData[$key]);
                break;
            }
        }

        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                unset($this->dataCompleta[$key]);
                break;
            }
        }

        // Recalculamos los totales solo después de eliminar el registro
        $totalAumentadoActualizado = array_sum(array_column($this->cacheData, 'aumentado'));
        $totalDisminuidoActualizado = array_sum(array_column($this->cacheData, 'disminuido'));

        $this->dispatch('cambioTotales', aumento: $totalAumentadoActualizado, disminucion: $totalDisminuidoActualizado);
    }

    public function changeState($value)
    {
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        $cog = ClasificadorObjetoGasto::where('codigo', '=', $registro['cog'])->first();
        $area = CodigoDepartamento::find($registro['areaResponsable']);
        $registro['importe'] = ($registro['afectacion'] == "disminucion") ? -$registro['importe'] : $registro['importe'];
        $nuevoRegistro = [
            'id' => 0,
            'areaNombre' => $area->Codigo_completo . " " . $area->Nombre,
            'COG' => $cog->codigo . " " . $cog->nombre,
            'mes' => $registro['mes'],
            'afectacion' => ($registro['movimiento'] == "reclasificacion") ? "Reclasificación" : "Recalendarización",
            'inicial' => $registro['solvencia'],
            'aumentado' => ($registro['afectacion'] == "aumento") ? $registro['importe'] : 0,
            'disminuido' => ($registro['afectacion'] == "disminucion") ? abs($registro['importe']) : 0,
            'final' => $registro['solvencia'] + $registro['importe']
        ];
        array_push($this->cacheData, $nuevoRegistro);
        array_push($this->dataCompleta, $registro);
        // Asignamos el ID a cada registro en $this->cacheData
        foreach ($this->cacheData as $key => $registro) {
            $this->cacheData[$key]['id'] = $key + 1; // El ID comienza en 1
            $this->dataCompleta[$key]['id'] = $key + 1;
        }
        $this->totalAumentado = 0;
        $this->totalDisminuido = 0;
        foreach ($this->cacheData as $registro) {
            $this->totalAumentado += $registro['aumentado'];
            $this->totalDisminuido += $registro['disminuido'];
        }
        $this->dispatch('cambioTotales', aumento: $this->totalAumentado, disminucion: $this->totalDisminuido);
    }

    #[On('finalizar-registros')]
    public function finalizarRegistros()
    {
        if (bccomp($this->totalAumentado, $this->totalDisminuido, 2) !== 0) {
            $this->dispatch('mostrarMensaje', mensaje: 'Balance erroneo, los totales deben coincidir', tipo: 'warning', tiempo: 3000);
            return;
        }
        $idUsuarioRegistrante = Auth::id();
        $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) as numero_poliza')
            ->where('tipo_poliza', '=', 'D')
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->distinct()
            ->orderBy('numero_poliza')
            ->pluck('numero_poliza')
            ->toArray();
        sort($numerosPolizas);
        $this->numeroPoliza = (int)end($numerosPolizas) + 1;
        $numerosEvento = Poliza::selectRaw('CAST(evento AS INT) as evento')
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->distinct()
            ->orderBy('evento')
            ->pluck('evento')
            ->toArray();
        sort($numerosEvento);
        if (!empty($numerosEvento)) {
            $this->numeroEvento = (int)end($numerosEvento) + 1;
        } else {
            $this->numeroEvento = 1;
        }
        $anioActual = Carbon::now()->year;
        $fecha = Carbon::now('America/Mexico_City');
        $fecha->year($anioActual);
        foreach ($this->dataCompleta as $movimiento) {
            $responsable = CodigoDepartamento::find($movimiento['areaResponsable']);
            $cuenta = Cuenta::join('CuentasCOG', 'CuentasCOG.codigoCuenta', '=', 'cuentas.Codigo_cuenta')->select('cuentas.*')->where('Descripcion_cuenta', 'like', '%Ejercer%')->where('COG', '=', $movimiento['cog'])->orderBy('COG')->first();
           Log::info($movimiento['fechaAfectacion']);
            $poliza = new Poliza([
                'idUsuarioRegistrante' => $idUsuarioRegistrante,
                'area' => $responsable->Codigo_completo,
                'tipo_poliza' => 'D',
                'numero_poliza' =>  $this->numeroPoliza,
                'fecha' => $movimiento['fechaAfectacion'],
                'cuenta' => $cuenta->Codigo_cuenta,
                'concepto' => $cuenta->Descripcion_cuenta,
                'total' => abs($movimiento['importe']),
                'mes' => $movimiento['mes'],
                'descripcion' => $movimiento['observaciones'],
                'evento' => $this->numeroEvento,
                'tipo_interaccion' => $movimiento['afectacion'] == 'aumento' ? 'Presupuestal - Cargo' : 'Presupuestal - Abono',
                'validado' => false,
                'categoria' => strtoupper($movimiento['movimiento'] . ' ' . $movimiento['afectacion']),
                'created_at' => $fecha,
                'updated_at' => $fecha
            ]);
            $poliza->save();
        }
        $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->totalAumentado, $this->totalDisminuido);
    }

    #[On('reiniciar')]
    public function finalizar()
    {
        $this->cacheData = [];
        $this->dataCompleta = [];
        $this->totalAumentado = 0;
        $this->totalDisminuido = 0;
        $this->numeroPoliza = 0;
        $this->numeroEvento = 0;
    }
}
