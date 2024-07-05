<?php

namespace App\Livewire;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;

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

    #[Validate('required', message:'Fecha requerida')]
    public $fechaRegistro = "";

    public $consultarRegistro = false;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;

    public function render()
    {
        $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->where('interaccion_cuenta_conceptos.concepto_id', '=', 13)->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Cargo')
            ->orderBy('cuentas.Codigo_cuenta')->get();
        return view('livewire.depositos-bancos-form', ['cuentas' => $cuentas]);
    }

    public function agregarRegistro(){
        try {
            $this->importe = floatval(str_replace(['$',','],"",$this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();
            $cuenta = Cuenta::find($this->cuenta);
            $registro = [
                'id' => 0,
                'codigoArea' => $this->selectCodigoArea,
                'observaciones' => $this->observaciones,
                'cuentaId' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' =>$cuenta->Descripcion_cuenta,
                'mes' => $this->mes,
                'fechaRegistro' => $this->fechaRegistro,
                'importe' => $this->importe
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        } catch (\Illuminate\Validation\ValidationException $e) {
            if($e->validator){
                $errors = $e->validator->errors()->all();
                foreach ($errors as $value) {
                    $this->dispatch('mostrarMensaje', mensaje: $value, tipo: 'warning', tiempo: 3000);
                }
            }
            else{
                throw $e;
            }
        }
    }

    #[On('reiniciar')]
    public function reiniciar() {
        $this->limpiar();
        $this->consultarRegistro = false;
        $this->numeroEvento = 0;
        $this->numeroPoliza = 0;
        $this->total = 0;
    }

    public function limpiar(){
        $this->cuenta = "";
        $this->mes = "";
        $this->importe = "";
        $this->dispatch('limpiar');
    }

    public function finalizarRegistros(){
        $this->dispatch('finalizar-registros');
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total) {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
    }
    #[On('llenar-formulario')]
    public function llenarFormulario ($datosRegistro) {
        $idCuenta = Cuenta::where('Codigo_cuenta', '=', $datosRegistro['codigoCuenta'])->value('id');
        $this->cuenta = $idCuenta;
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];

        $this->dispatch('llenarFormulario', cuenta: $datosRegistro['codigoCuenta'], mes: $datosRegistro['mes'], importe: $datosRegistro['importe']);
    }

}
