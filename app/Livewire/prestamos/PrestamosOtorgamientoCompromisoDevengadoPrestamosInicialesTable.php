<?php

namespace App\Livewire\prestamos;

use App\Livewire\Tabla;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Clases\Column;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;

class PrestamosOtorgamientoCompromisoDevengadoPrestamosInicialesTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $totalDisponible = 0;

    public function render()
    {
        return view('livewire.prestamos.prestamos-otorgamiento-compromiso-devengado-prestamosIniciales-table');
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
          //  Column::make('disponibilidad', 'Disponibilidad')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try{
            $nuevoRegistro = [
                'id' => 0,
                'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                'mes' => $registro['mes'],
                'movimiento' => 'OTORGAMIENTO COMPROMISO DEVENGADO PRESTAMOS INICIALES', 
                'pttoEjecutar' => $registro['pttoEjecutar'],
                'importe' => $registro['importe'],
               // 'disponibilidad' => $this->totalDisponible,
            ];
            array_push($this->cacheData, $nuevoRegistro);
            array_push($this->dataCompleta, $registro);
            $this->total = 0;
            foreach ($this->cacheData as $key => $registro) {
                $this->cacheData[$key]['id'] = $key + 1; 
                $this->dataCompleta[$key]['id'] = $key + 1;
                $this->total += $registro['importe'];
            }
           // $this->dispatch('cambioTotal', total: $this->total);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en compromiso-devengado préstamos inicales del capítulo 7000: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function edit($id)
    {

    }

    public function delete($id)
    {

    }

    public function changeState($value)
    {

    }

}