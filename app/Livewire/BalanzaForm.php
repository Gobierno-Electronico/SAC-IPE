<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Poliza;
use App\Clases\Column;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class BalanzaForm extends Component
{
  
    public $fechaInicio = '';
    public $fechaFin = '';

    public function render()
    {
        return view('livewire.balanza-form');
    }

    public function generar($formato)
    {
        try {
            $subtituloFechas = "Balanza Armonizada del " .
                Carbon::parse($this->fechaInicio)
                ->locale('es')
                ->translatedFormat('d/F/Y') . " al " .
                Carbon::parse($this->fechaFin)
                ->locale('es')
                ->translatedFormat('d/F/Y');
            $fecha = Carbon::now()->format('d/m/Y');
            $hora = Carbon::now()->format('h:i A');
            $params = "FechaInicio;{$this->fechaInicio},FechaFin;{$this->fechaFin},Fecha;{$fecha},Hora;{$hora}, SubtituloFechas;{$subtituloFechas}&formato={$formato}";
            $this->dispatch('descargar', Params: $params);
        } catch (\Throwable $th) {
            $this->dispatch('mostrarMensaje', mensaje: $th->getMessage(), tipo: 'warning', tiempo: 3000);
        }
    }

    public function save() {}


    public function change() {}

}
