<?php

namespace App\Livewire;

use Livewire\Component;
use App\Clases\Column;
use App\Models\Bitacora;
use Illuminate\Database\Eloquent\Builder;

class BitacorasTable extends Tabla
{
    public $perPage = 10;

    public $sortBy = '';

    public $searchBy = ['direccionIp', 'nombreUsuario', 'descripcionProceso'];

    public $fecha = '';

    public function render()
    {
        // $this->selectedYear = Carbon::now()->year + 1;
        return view('livewire.bitacoras-table');
    }

    public function query(): Builder
    {
        return Bitacora::query();
    }

    public function columns(): array
    {
        return [
            Column::make('direccionIp', 'Dispositivo IP'),
            Column::make('nombreUsuario', 'Nombre del usuario'),
            Column::make('descripcionProceso', 'Proceso realizado'),
            Column::make('created_at', 'Fecha de realización'),
        ];
    }

    
    public function data()
    {
        return $this
            ->query()
            ->when($this->sortBy !== '', function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })
            ->when($this->fecha !== '', function ($query) {
                $query->whereDate('created_at', $this->fecha);
            })
            ->search($this->searchBy,$this->searchTerm)
            ->paginate($this->perPage);
    }

    public function search() {
        $this->resetPage();
    }


    public function edit($value)
    {
        return view('bitacoras.lista');
    }

    public function changeState($value)
    {}
}
