<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use Carbon\Carbon;
use Log;


class DeudoresReintegroAnticipoForm extends Component
{
    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Evento requerido')]
    public $numeroEvento = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Fecha de afectación requerida')]
    public $fechaAfectacion = "";

    public $consultarRegistro = false;
    public $numeroPoliza;
    public $total;
    public $tipoMovimiento;

    public function render()
    {
        try {
            $cuentas = [];  
            $eventos = [];
            $cuentasCargo = []; 
            return view('livewire.deudores-reintegro-anticipo-form', ['cuentas' => $cuentas, 'eventos' => $eventos, 'cuentasCargo' => $cuentasCargo]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en deudores reintegro de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cambioEvento(){
        $this->limpiar(); 
        try{
/*             $this->llenarCamposEspecificos();
            $this->montoDelEvento = DB::select('EXEC ImporteTotalCapitulo2y3Devengado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
            $this->llenarPartidasPresupuestales(); */
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el monto del evento en deudores reintegro de anticipos: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function agregarRegistro()
    {
        
    }

    public function limpiar()
    {
        $this->cuenta = "";
        $this->mes = "";
        $this->importe = "";
        $this->dispatch('limpiar');
    }

    public function finalizarRegistros()
    {
        $this->dispatch('finalizar-registros');
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total)
    {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $idCuenta = Cuenta::where('Codigo_cuenta', '=', $datosRegistro['codigoCuenta'])->value('id');
        $this->cuenta = $idCuenta;
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];

        $this->dispatch('llenarFormulario', cuenta: $datosRegistro['codigoCuenta'], mes: $datosRegistro['mes'], importe: $datosRegistro['importe']);
    }
}
