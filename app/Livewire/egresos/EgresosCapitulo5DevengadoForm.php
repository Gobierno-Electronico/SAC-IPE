<?php

namespace App\Livewire\egresos;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;

class EgresosCapitulo5DevengadoForm extends Component
{
    public $consultarRegistro = false;

    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Fecha de afectación requerida')]
    public $fechaAfectacion = "";

    #[Validate('required', message: 'Evento requerido')]
    public $numeroEvento = "";

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Partida presupuestal requerida')]
    public $partidaPresupuestal = "";

    #[Validate('required', message: 'Cuenta contable requerida')]
    public $cuentaContable = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoDelEvento = "";

    public $PTTOComprometido = 0;

    public $partidasPresupuestales = [];
    public $cuentasContables = [];

    public function render() 
    {
        $partidasPresupuestales = ['prueba1', 'prueba2'];
        $cuentasContables = ['prueba1', 'prueba2'];
        $eventos = ['pruebaEvento1', 'pruebaEvento2'];
        return view('livewire.egresos.egresos-capitulo5-devengado-form', ['cuentas' => $cuentasContables, 'partidas' => $partidasPresupuestales, 'eventos' => $eventos]);
    }

    public function cambioEvento(){

    }

    public function agregarRegistro()
    {
        try{
            $this->validate();
        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }
    }

    public function finalizarRegistro()
    {
        
    }

}