<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\On;
class LibroMayor extends Component
{

    public $selectedYear = '';
    public $fecha1 = '';
    public $fecha2 = '';
    public $meses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];



    public function render()
    {
        return view('livewire.libro-mayor');
    }

    public function save() {

    }


    public function change(){
    }

    public function generar(){
        $mes1 = Carbon::parse($this->fecha1)->month;
        $mes2 = Carbon::parse($this->fecha2)->month;
        $dia1 = Carbon::parse($this->fecha1)->day;
        $dia2 = Carbon::parse($this->fecha2)->day;
        $tituloFecha = "";
        if($mes1 === $mes2){
            if($dia1 === $dia2){
                $tituloFecha = "DEL {$dia1} DE {$this->meses[$mes1-1]} DEL {$this->selectedYear}";
            }
            else{
                $tituloFecha = "DEL {$dia1} AL {$dia2} DE {$this->meses[$mes1-1]} DEL {$this->selectedYear}";
            }
        }
        else{
            $tituloFecha = "DEL {$dia1} DE {$this->meses[$mes1-1]} AL {$dia2} DE {$this->meses[$mes2-1]} DEL {$this->selectedYear}";
        }

        $params = "Fecha1;{$this->fecha1},Fecha2;{$this->fecha2},Anio;{$this->selectedYear},TituloFechas;{$tituloFecha}";
        $this->dispatch('descargar', Params: $params);
    
    }
}
