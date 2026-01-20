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

class PresupuestoEGRESOSTable extends Tabla
{
    public $numeroPoliza = '';

    public $perPage = 10;

    public $sortBy = '';

    public $selectedYear = '';

    public $selectedChapter = '';
    
    public $selectedCOG = '';

    public $cuentaSeleccionada = '';

    public $clasificadorSeleccionado = '';

    public $conceptoSeleccionado = '';

    public $searchBy = ['concepto', 'area', 'codigo_departamentos.Nombre','tipo_poliza', 'numero_poliza', 'polizas.cuenta', 'total', 'mes', 'cuenta_clasificadores_egreso.COG', 'cuenta_clasificadores_egreso.CP', 'cuenta_clasificadores_egreso.CA', 'cuenta_clasificadores_egreso.CTG', 'cuenta_clasificadores_egreso.CFG'];

    public $validado = true;

    public $fecha = '';

    public $hora = '';
    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }
    
    public function render()
    {
        $COGS = Poliza::join('cuenta_clasificadores_egreso as ce', 'ce.codigoCuenta', '=', 'polizas.cuenta')
        ->where('tipo_poliza', 'P')
        ->whereYear('fecha', '=', (string) $this->anio)
        ->where('categoria', 'LIKE', '%INICIAL%')
        ->select('ce.COG')
        ->groupBy('ce.COG')
        ->orderBy('ce.COG')
        ->get();    
        // $this->selectedYear = Carbon::now()->year + 1;
        return view('livewire.presupuesto-egresos-table', ['COGS' => $COGS]);
    }
    public function query(): Builder
    {
        return Poliza::query();
    }

    public function columns(): array
    {
        // dd($this->selectedYear);
        return [
            Column::make('areaNombre', 'Área ejecutora'),
            // Column::make('tipo_poliza', 'Tipo de póliza'),
            // Column::make('numero_poliza', 'Número de póliza'),
            Column::make('COG', 'COG'),
            Column::make('CA', 'CA'),
            Column::make('CFG', 'CFG'),
            Column::make('CP', 'CP'),
            Column::make('CTG', 'CTG'),
            Column::make('cuentaBien', 'Cuenta'),
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
            ->join('codigo_departamentos', 'polizas.area', '=', 'codigo_departamentos.Codigo_completo')
            ->join('cuenta_capitulos', 'polizas.cuenta', '=', 'cuenta_capitulos.cuenta')
            ->leftJoin('cuenta_clasificadores_egreso', 'polizas.cuenta', '=', 'cuenta_clasificadores_egreso.codigoCuenta')
            ->select('polizas.*', 'polizas.cuenta as cuentaBien', 'codigo_departamentos.Nombre as areaNombre', 'cuenta_capitulos.cuenta', 'cuenta_clasificadores_egreso.*')
            ->when($this->sortBy !== '', function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })->search($this->searchBy, $this->searchTerm)
            ->when($this->selectedYear !== '', function ($query) {
                $query->searchByYear('fecha', $this->selectedYear);
            })
            ->when($this->selectedChapter !== '', function ($query) {
                $query->where('cuenta_capitulos.capitulo', '=', $this->selectedChapter);
            })
            ->when($this->selectedCOG !== '', function ($query){
                $query->where('cuenta_clasificadores_egreso.COG', '=', $this->selectedCOG);
            })
            ->where('tipo_poliza', '=', 'P')
            ->paginate($this->perPage);
    }



    public function validarPresupuestoInicial()
    {
        try {
            DB::beginTransaction();
            if ($this->selectedYear != '' && $this->selectedChapter != '') {
                Poliza::when($this->selectedYear !== '', function ($query) {
                    $query->searchByYear('fecha', $this->selectedYear);
                })->where('categoria', '=', 'INICIAL EGRESOS')->where('tipo_poliza', '=', 'P')->where('numero_poliza', '=', $this->numeroPoliza)->update(["validado" => true]);
                $usuariosController = new BitacoraController();
                $usuariosController->bitacora('validarPresupuestoInicial', 'validó o intentó validar el presupuesto inicial de egresos capítulo ' . $this->selectedChapter . ' del año ' . $this->selectedYear, request());
                $this->validado = true;
                DB::commit();
                $this->dispatch('mostrarMensaje', mensaje: 'Se validó el capítulo ' . $this->selectedChapter . ' del presupuesto de egresos del año ' . $this->selectedYear, tipo: 'success', tiempo: 3000);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al validar el presupuesto', tipo: 'error', tiempo: 3000);

        }

        // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'EGRESOS')->where('tipo', '=', 'P')->update(["validado" => true]);
    }

    public function selectYear()
    {
        $poliza = $this->data()->first();
        $this->validado = ($poliza) ? boolval($poliza->validado) : true;
        $this->fecha = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('d/m/Y') : '01/01/' . Carbon::now()->year;
        $this->hora = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('H:i:s') : '11:00:00';
        $this->numeroPoliza = ($poliza) ? $poliza->numero_poliza : 0;
    }

    public function selectCOG(){
        $poliza = $this->data()->first();
        $this->validado = ($poliza) ? boolval($poliza->validado) : true;
        $this->fecha = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('d/m/Y') : '01/01/' . Carbon::now()->year;
        $this->hora = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('H:i:s') : '11:00:00';
        $this->numeroPoliza = ($poliza) ? $poliza->numero_poliza : 0;
    }

    public function selectChapter()
    {
        $poliza = $this->data()->first();
        $this->validado = ($poliza) ? boolval($poliza->validado) : true;
        $this->fecha = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('d/m/Y') : '01/01/' . Carbon::now()->year;
        $this->hora = ($poliza) ? Carbon::createFromFormat('Y-m-d H:i:s', $poliza->created_at)->format('H:i:s') : '11:00:00';
        $this->numeroPoliza = ($poliza) ? $poliza->numero_poliza : 0;

        // switch ($) {
        //     case '2000':
        //         $numPoliza = 2;
        //         break;
        //     case '3000':
        //         $numPoliza = 3;
        //         break;
        //     default:
        //         session()->flash('message', 'El capítulo seleccionado no se encuentra programado');
        //         session()->flash('message_type', 'error');
        //         return back();
        // }
    }

    public function edit($value)
    {
    }

    public function init()
    {
        $this->selectedYear = (string) $this->anio;
        $this->selectYear();
        $this->selectedChapter = '';

    }

    public function borrar()
    {
        try {
            DB::beginTransaction();
            if ($this->validado)
                return;
            if ($this->selectedYear != '' && $this->selectedChapter != '') {
                Poliza::when($this->selectedYear !== '', function ($query) {
                    $query->searchByYear('fecha', $this->selectedYear);
                })->where('categoria', '=', 'INICIAL EGRESOS')->where('tipo_poliza', '=', 'P')->where('numero_poliza', '=', $this->numeroPoliza)->delete();
                // PresupuestoInicial::where('anio', '=', $this->selectedYear)->where('categoria', '=', 'EGRESOS')->where('tipo', '=', 'P')->delete();
                $usuariosController = new BitacoraController();
                $usuariosController->bitacora('borrar', 'borró o intentó borrar el presupuesto inicial de egresos capítulo ' . $this->selectedChapter . ' del año ' . $this->selectedYear, request());
                $this->validado = true;
                DB::commit();
                $this->dispatch('mostrarMensaje', mensaje: 'Se borró el capítulo ' . $this->selectedChapter . ' del presupuesto de egresos del año ' . $this->selectedYear, tipo: 'success', tiempo: 3000);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al validar el presupuesto inicial', tipo: 'error', tiempo: 3000);

        }
    }
    public function changeState($value)
    {
    }
}
