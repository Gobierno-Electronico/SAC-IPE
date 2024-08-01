<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use App\Models\Poliza;
use Carbon\Carbon;
use Log;
use DB;

class IngresosRecaudadoForm extends Component

{
    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Evento requerido')]
    public $numeroEvento = '';

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoDelEvento = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Fecha requerida')]
    public $fechaRegistro = "";

    #[Validate('required', message: 'Cuenta de pago requerida')]
    public $cuentaPago = "";

    public $subcuentas = [];

    public $numeroPoliza;

    public $cambiarCuentaPagoSeleccionada = true;

    public function render()
    {
        $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [19, 20, 21, 35, 39])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Abono')
            ->orderBy('cuentas.Codigo_cuenta')->get();
        $eventos =  Poliza::select('evento', 'descripcion')->whereYear('fecha', '=', Carbon::now()->year)->where('tipo_poliza', '=', 'I')
            ->where('categoria', '=', 'INGRESOS DEVENGADO')->distinct()->pluck('descripcion', 'evento');
        $this->cambiarCuentaPagoSeleccionada = false;
        $this->llenarCuentasPago();
        return view('livewire.ingresos-recaudado-form', ['eventos' => $eventos, 'cuentas' => $cuentas]);
    }

    public function cambioEvento()
    {
        $this->montoDelEvento = DB::select('EXEC ImporteTotalRecaudado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
        $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
        $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
        $this->cambiarCuentaPagoSeleccionada = false;
        $this->llenarCuentasPago();
    }

    public function llenarCuentasPago()
    {
        if (!$this->cuenta) {
            return;
        }

        if ($this->cambiarCuentaPagoSeleccionada) {
            $this->cuentaPago = "";
        }

        $this->cambiarCuentaPagoSeleccionada = true;
        $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('interaccion_cuenta_conceptos.concepto_id', [19, 20, 21, 35, 39])
            ->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();
        $this->subcuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
            ->join('interaccion_cuenta_conceptos', function ($join) {
                $join->on('interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                    ->where('tipo_interaccion', '=', 'Contable - Cargo');
            })
            ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get();
    }

    public function agregarRegistro()
    {
        try {
            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();
            $cuenta = Cuenta::find($this->cuenta);
            $cuentaPagoSeleccionada = Cuenta::find($this->cuentaPago);
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $registro = [
                'id' => 0,
                'codigoArea' => $this->selectCodigoArea,
                'observaciones' => $this->observaciones,
                'fechaRegistro' => $this->fechaRegistro,
                'evento' => $this->numeroEvento,
                'areaResponsableId' => $this->selectCodigoAreaResponsable,
                'codigoAreaResponsable' => $departamento->Codigo_completo,
                'descripcionAreaResponsable' => $departamento->Nombre,
                'cuentaId' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' => $cuenta->Descripcion_cuenta,
                //VERIFICAR 
                'cuentaPagoId' => $this->cuentaPago,
                'codigoCuentaPago' => $cuentaPagoSeleccionada->Codigo_cuenta,
                'descripcionCuentaPago' => $cuentaPagoSeleccionada->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'montoEvento' => $this->montoDelEvento
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        } catch (\Illuminate\Validation\ValidationException $exception) {
            Log::error($exception->getMessage());
            if ($exception->validator) {
                $errors = $exception->validator->errors()->all();
                foreach ($errors as $value) {
                    $this->dispatch('mostrarMensaje', mensaje: $value, tipo: 'warning', tiempo: 3000);
                }
            } else {
                throw $exception;
            }
        }
    }

    public function limpiar()
    {
        $this->cuenta = "";
        $this->cuentaPago = "";
        $this->mes = "";
        $this->importe = "";
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->cuentaPago = $datosRegistro['cuentaPago'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->dispatch('llenarFormulario', cuenta: $datosRegistro['cuenta'], cuentaPago: $datosRegistro['cuentaPago'], mes: $datosRegistro['mes'], importe: $datosRegistro['importe'], area: $datosRegistro['area']);
    }
}
