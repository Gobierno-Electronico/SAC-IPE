<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use DB;
use App\Models\Cuenta;

class EstadoCuenta extends Component
{

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = null;

    #[Validate('required', message: 'Fecha de inicio requerida')]
    public $fechaInicio = '';

    #[Validate('required', message: 'Fecha de fin requerida')]
    public $fechaFin = '';

    public $filtroDescripcion;

    public $meses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];



    public function render()
    {
        try{
            $cuentas = Cuenta::all();
            return view('livewire.estado-cuenta', ['cuentas' => $cuentas]);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar cuentas en reportes Estado de cuenta: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }
    }

    public function save() {

    }


    public function change(){
    }

    public function generarEstadoCuenta()
    {
        $this->validate();
        dd($this->cuenta, $this->fechaInicio, $this->fechaFin);
    }
}
