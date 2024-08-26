<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use PDOException;
use Log;
use DB;

class DepositosBancosForm extends Component
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

    public function render()
    {
        try {
            //code...
            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->where('interaccion_cuenta_conceptos.concepto_id', '=', 13)->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Cargo')
                ->orderBy('cuentas.Codigo_cuenta')->get();
            return view('livewire.depositos-bancos-form', ['cuentas' => $cuentas]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en depósitos en bancos: ' . $th->getMessage());
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
            $registro = [
                'id' => 0,
                'codigoArea' => $this->selectCodigoArea,
                'observaciones' => $this->observaciones,
                'cuentaId' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' => $cuenta->Descripcion_cuenta,
                'mes' => $this->mes,
                'fechaAfectacion' => $this->fechaAfectacion,
                'importe' => $this->importe
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregarRegistro en depósitos en bancos: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    #[On('reiniciar')]
    public function reiniciar()
    {
        $this->limpiar();
        $this->consultarRegistro = false;
        $this->numeroEvento = 0;
        $this->numeroPoliza = 0;
        $this->total = 0;
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
        try {
            //code...
            $idCuenta = Cuenta::where('Codigo_cuenta', '=', $datosRegistro['codigoCuenta'])->value('id');
            $this->cuenta = $idCuenta;
            $this->mes = $datosRegistro['mes'];
            $this->importe = $datosRegistro['importe'];

            $this->dispatch('llenarFormulario', cuenta: $datosRegistro['codigoCuenta'], mes: $datosRegistro['mes'], importe: $datosRegistro['importe']);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al llenarFormulario en depósitos en bancos: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al llenar el formulario, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}
