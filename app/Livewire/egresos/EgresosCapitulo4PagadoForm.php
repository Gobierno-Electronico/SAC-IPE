<?php

namespace App\Livewire\egresos;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use Log;

class EgresosCapitulo4PagadoForm extends Component
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

    #[Validate('required', message: 'Banco requerido')]
    public $cuentaBanco = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    public function render() 
    {
        try{
            $partidasPresupuestales = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [40, 43, 46, 48, 49, 51])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
            ->orderBy('cuentas.Codigo_cuenta')->get();

            $cuentasBanco = ['Banco1', 'Banco2'];
            $eventos = ['pruebaEvento1', 'pruebaEvento2'];
            return view('livewire.egresos.egresos-capitulo4-pagado-form', [
                'partidasPresupuestales' => $partidasPresupuestales, 
                'cuentasBanco' => $cuentasBanco,
                'eventos' => $eventos]);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar cuentas en Devengado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }
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