<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\CodigoDepartamento;
use Carbon\Carbon;
use Log;


class RegistroPolizaDiarioForm extends Component
{
    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = " ";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Fecha de afectación requerida')]
    public $fechaAfectacion = "";

    #[Validate('required', message: 'Tipo de interacción requerida')]
    public $tipoInteraccion = "";


    public $consultarRegistro = false;
    public $numeroEvento;
    public $numeroPoliza;
    public $totalCargo;
    public $totalAbono;
    public $total;

    public function render()
    {
        try {
            $cuentas = Cuenta::where('Codigo_cuenta', 'NOT LIKE', '8.%')
                ->where('Nivel', '=', '6')
                ->orderBy('Codigo_cuenta')->get();
            return view('livewire.registro-poliza-diario-form', ['cuentas' => $cuentas]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en registro de póliza diario: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function agregarRegistro()
    {
        try {
            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();
            $cuenta = Cuenta::find($this->cuenta);
            $departamento = CodigoDepartamento::find($this->selectCodigoArea);
            $registro = [
                'id' => 0,
                'observaciones' => $this->observaciones,
                'fechaAfectacion' => $this->fechaAfectacion,
                'codigoAreaResponsable' => $departamento->Codigo_completo,
                'descripcionAreaResponsable' => $departamento->Nombre,
                'tipoInteraccion' => $this->tipoInteraccion,
                'idCuenta' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' => $cuenta->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en poliza diario: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function limpiar()
    {
        $this->cuenta = "";
        $this->mes = "";
        $this->importe = "";
        $this->tipoInteraccion = "";
        $this->dispatch('limpiar');
    }

    public function finalizarRegistros()
    {
        $this->dispatch('finalizar-registros');
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $totalCargo, $totalAbono)
    {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $totalCargo + $totalAbono;
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro) {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->tipoInteraccion = $datosRegistro['tipoInteraccion'];
        $this->dispatch('llenarFormulario', cuenta: $datosRegistro['cuenta'], mes: $datosRegistro['mes'], importe: $datosRegistro['importe'], tipoInteraccion: $datosRegistro['tipoInteraccion']);
    }
}
