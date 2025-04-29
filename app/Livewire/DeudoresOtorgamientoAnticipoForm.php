<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use Carbon\Carbon;
use Log;


class DeudoresOtorgamientoAnticipoForm extends Component
{
    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Fecha de afectación requerida')]
    public $fechaAfectacion = "";

    public $consultarRegistro = false;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $tipoMovimiento;

    public function render()
    {
        try {
            $cuentas = [];  
            return view('livewire.deudores-otorgamiento-anticipo-form', ['cuentas' => $cuentas]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en deudores otorgamiento de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
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
