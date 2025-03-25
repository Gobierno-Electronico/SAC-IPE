<?php

namespace App\Livewire;

use Livewire\Attributes\Reactive;
use Livewire\Component;

class AfectacionesIngresosConsulta extends Component
{
    #[Reactive]
    public $tipo;
    public $observaciones;
    public $registros = [];
    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $estado;
    public $estadoOriginal;
    public $totalPrevio;

    public function render()
    {
        return view('livewire.afectaciones-ingresos-consulta');
    }
}
