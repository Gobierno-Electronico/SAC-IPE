<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use Livewire\Attributes\On;
use App\Http\Controllers\BitacoraController;
class TiposPresupuestoForm extends Component
{
    public $tipo = "";
    public $seccion = "";
    public $filtro1 = "0";
    public $filtro2 = "0";
    public $valor1 = "";
    public $valor2 = "";
    public $valor3 = "";
    public $valor4 = "";
    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }

    public function render()
    {
        return view('livewire.tipos-presupuesto-form');
    }

    public function save() {

    }


    public function change(){
        // $this->valor1 = "";
        // $this->valor2 = "";
        // $this->valor3 = "";
        // $this->valor4 = "";
    }

    public function reporte(){
        // if($tipo = "solicitado-aprovado"){

        // }
        $nombre = "PresupuestoSolicitado";
        $fecha = Carbon::createFromFormat('Y-m-d H:i:s',Carbon::now())->format('d/m/Y');
        $hora = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->format('H:i:s');
        $params = "Anio;". (string) $this->anio . ",Tipo;P,Fecha;{$fecha},Hora;{$hora},Numero;1,";
        switch ($this->seccion) {
            case 'ubpp':
                $nombre .= "Area";
                if($this->filtro1 == "igual"){
                    $this->valor2 = $this->valor1;
                }
                $params .= "rango;{$this->filtro1},area1;{$this->valor1},area2;{$this->valor2}";
                break;
            case 'partida':
                $nombre .= "Cuenta";
                if($this->filtro1 == "igual"){
                    $this->valor2 = $this->valor1;
                }
                $params .= "rango;{$this->filtro1},cuenta1;{$this->valor1},cuenta2;{$this->valor2}";
                break;
            case 'capitulo':
                $nombre .= "Capitulo";
                if($this->filtro1 == "igual"){
                    $this->valor2 = $this->valor1;
                }
                $params .= "rango;{$this->filtro1},capitulo1;{$this->valor1},capitulo2;{$this->valor2}";
                break;
            case 'ubpp-partida':
                $nombre .= "AreaCuenta";
                // dd($this->filtro1, $this->valor4, $this->valor3, 'HOLA', $this->filtro2, $this->valor2, $this->valor1);
                if($this->filtro2 == "igual"){
                    $this->valor4 = $this->valor3;
                }
                if($this->filtro1 == "igual"){
                    $this->valor2 = $this->valor1;
                }
                $params .= "rangoCuenta;{$this->filtro2},rangoArea;{$this->filtro1},cuenta1;{$this->valor3},cuenta2;{$this->valor4},Area1;{$this->valor1},Area2;{$this->valor2}";
                break;
            default:
                break;
        }
        $wsUrl = "http://10.0.2.59:8080/Reporteador/webresources/service/report?name={$nombre}&params={$params}";
        $this->dispatch('descargar-reporte-tipo-presupuesto', url: $wsUrl);
        $bitacora = new BitacoraController();
        $bitacora->bitacora('reporte', 'generó o intentó generer el reporte de '.$nombre.' en el apartado de tipos de presupuesto', request());
    }

    public function cambioPresupuestos($presupuesto) {
        $this->tipo = $presupuesto;
    }

    public function cambioSeccion($seccion){
        $this->seccion = $seccion;
        $this->valor1 = "";
        $this->valor2 = "";
        $this->valor3 = "";
        $this->valor4 = "";
    }

}
