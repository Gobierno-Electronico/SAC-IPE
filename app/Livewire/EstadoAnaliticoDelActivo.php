<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\On;

class EstadoAnaliticoDelActivo extends Component
{

    public $fechaInicio = '';
    public $fechaFin = '';

    public function render()
    {
        return view('livewire.estado-analitico-del-activo');
    }

    public function save() {}


    public function change() {}

    public function generar($formato)
    {
        try {
            $subtituloFechas = "Estado Analítico del Activo del " .
                Carbon::parse($this->fechaInicio)
                ->locale('es')
                ->translatedFormat('d/F/Y') . " al " .
                Carbon::parse($this->fechaFin)
                ->locale('es')
                ->translatedFormat('d/F/Y');
            $params = "FechaInicio;{$this->fechaInicio},FechaFin;{$this->fechaFin},SubtituloFechas;{$subtituloFechas}&formato={$formato}";
            $this->dispatch('descargar', Params: $params);
        } catch (\Throwable $th) {
            $this->dispatch('mostrarMensaje', mensaje: $th->getMessage(), tipo: 'warning', tiempo: 3000);
        }
    }
}
