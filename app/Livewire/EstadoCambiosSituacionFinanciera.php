<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\On;

class EstadoCambiosSituacionFinanciera extends Component
{

    public $fechaInicio = '';
    public $fechaFin = '';

    public function render()
    {
        return view('livewire.estado-cambios-situacion-financiera');
    }

    public function save() {}


    public function change() {}

    public function generar($formato)
    {
        try {
            $fecha = Carbon::now()->format('d/m/Y');
            $hora = Carbon::now()->format('h:i A');
            $subtituloFechas = "Estado de Cambios en la Situación Financiera del " .
                Carbon::parse($this->fechaInicio)
                ->locale('es')
                ->translatedFormat('d/F/Y') . " al " .
                Carbon::parse($this->fechaFin)
                ->locale('es')
                ->translatedFormat('d/F/Y');
            $params = "FechaInicio;{$this->fechaInicio},FechaFin;{$this->fechaFin},SubtituloFechas;{$subtituloFechas},Fecha;{$fecha},Hora;{$hora}&formato={$formato}";
            $this->dispatch('descargar', Params: $params);
        } catch (\Throwable $th) {
            $this->dispatch('mostrarMensaje', mensaje: $th->getMessage(), tipo: 'warning', tiempo: 3000);
        }
    }
}
