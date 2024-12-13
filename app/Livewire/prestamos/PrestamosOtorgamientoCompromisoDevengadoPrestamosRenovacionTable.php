<?php

namespace App\Livewire\prestamos;

use App\Livewire\Tabla;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Clases\Column;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;
use Log;
use DB;
use Carbon\Carbon;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Http\Controllers\BitacoraController;
use App\Models\Poliza;

class PrestamosOtorgamientoCompromisoDevengadoPrestamosRenovacionTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $totalDisponible = 0;

    public function render()
    {
        return view('livewire.prestamos.prestamos-otorgamiento-compromiso-devengado-prestamosRenovacion-table');
    }

    public function query(): Builder
    {
        return Poliza::query();
    }

    public function data()
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = array_slice($this->cacheData, $this->perPage * ($currentPage - 1), $this->perPage);
        return new LengthAwarePaginator($currentItems, count($this->cacheData), $this->perPage, $currentPage);
    }

    public function columns(): array
    {
        return [
            Column::make('area', 'Area'),
            Column::make('partida', 'Partida'),
            Column::make('mes', 'Mes'),
            Column::make('movimiento', 'Movimiento'),
            Column::make('pttoEjecutar', 'PPTO por ejecutar')->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try{

        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en compromiso-devengado préstamos renovación del capítulo 7000: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function edit($id)
    {
        try{

        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al editar en compromiso-devengado préstamos renovación del capítulo 7000: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function delete($id)
    {
        try{

        }catch(\Throwable $th) {
            Log::error('Ocurrió un error al eliminar en compromiso-devengado préstamos renovación del capítulo 7000: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    #[On('finalizar-registros')]
    public function finalizarRegistros()
    {
        try{

        }catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en compromiso-devengado préstamos renovación del capítulo 7000: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function changeState($value)
    {

    }

}