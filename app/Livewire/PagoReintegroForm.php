<?php

namespace App\Livewire;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\Poliza;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use Carbon\Carbon;
class PagoReintegroForm extends Component
{
    public $numeroPoliza;
    public $consultarRegistro = false;
    public $total;

    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea;

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Fecha requerida')]
    public $fechaRegistro = "";

    #[Validate('required', message: 'Evento requerido')]
    public $numeroEvento = '';

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";
    
    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    public $montoDelEvento = "";

    public function render(){
        $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
        ->whereIn('interaccion_cuenta_conceptos.concepto_id', [31, 32])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
        ->where('cuentas.Descripcion_cuenta', 'LIKE', '%(Recaudado)%')->orderBy('cuentas.Codigo_cuenta')->get();

        $eventos =  Poliza::select('evento', 'descripcion')->whereYear('fecha', '=', Carbon::now()->year)->where('tipo_poliza', '=', 'I')
        ->where('categoria','=','INGRESOS AUTORIZACION REINTEGRO')->distinct()->pluck('descripcion', 'evento'); 

        return view('livewire.pago-reintegro-form', ['cuentas' => $cuentas, 'eventos' => $eventos]);
    }
    
    public function cambioEvento(){
        $this->montoDelEvento = DB::select('EXEC ImporteTotalPagoReintegro @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
        $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
        $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo : 'success', tiempo: 1500);
    }
    
    public function agregarRegistro(){
        try{
            $this->importe = floatval(str_replace(['$',','],"",$this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();
            $cuenta = Cuenta::find($this->cuenta);
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $registro = [
                'id' => 0,
                'codigoArea' => $this->selectCodigoArea,
                'observaciones' => $this->observaciones,
                'fechaRegistro' => $this->fechaRegistro,
                'evento' => $this->numeroEvento,
                'areaResponsableId' => $this->selectCodigoAreaResponsable,
                'codigoAreaResponsable' =>$departamento->Codigo_completo,
                'descripcionAreaResponsable' =>$departamento->Nombre,
                'cuentaId' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' =>$cuenta->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'montoEvento' => $this->montoDelEvento
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        }catch(\Illuminate\Validation\ValidationException $exception){
            Log::error($exception->getMessage());
            if($exception->validator){
                $errors = $exception->validator->errors()->all();
                foreach ($errors as $value) {
                    $this->dispatch('mostrarMensaje', mensaje: $value, tipo: 'warning', tiempo: 3000);
                }
            }
            else{
                throw $exception;
            }
        }
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

    #[On('llenar-formulario')]
    public function llenarFormulario ($datosRegistro) {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->dispatch('llenarFormulario', cuenta: $datosRegistro['cuenta'], mes: $datosRegistro['mes'], importe: $datosRegistro['importe'], area: $datosRegistro['area']);
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total) {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
    }

}
