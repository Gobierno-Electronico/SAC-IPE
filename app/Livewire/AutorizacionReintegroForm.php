<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use Log;
use DB;


class AutorizacionReintegroForm extends Component
{
    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Fecha requerida')]
    public $fechaRegistro = "";

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    public $causaIva = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Presupuesto devengado insuficiente')]
    #[Validate('numeric', message: 'Presupuesto devengado insuficiente')]
    #[Validate('min:1', message: 'Presupuesto devengado insuficiente')]
    public $presupuestoDevengado = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Cuenta de cargo requerida')]
    public $cuentaCargo = "";

    public $subcuentas = [];
    public $cambiarCuentaCargoSeleccionada = true;
    public $consultarRegistro = false;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $tipoMovimiento;


    public function render()
    {
        $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [30, 36])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
            ->where('cuentas.Descripcion_cuenta', 'LIKE', '%(Devengado)%')->orderBy('cuentas.Codigo_cuenta')->get();
        $this->cambiarCuentaCargoSeleccionada = false;
        $this->llenarCuentasCargo();
        return view('livewire.autorizacion-reintegro-form', ['cuentas' => $cuentas]);
    }

    public function llenarCuentasCargo()
    {

        if (!$this->cuenta) {
            return;
        }

        if ($this->cambiarCuentaCargoSeleccionada) {
            $this->cuentaCargo= "";
        }

        $this->cambiarCuentaCargoSeleccionada = true;
        $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('interaccion_cuenta_conceptos.concepto_id', [30, 36])
            ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
        $this->subcuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
            ->join('interaccion_cuenta_conceptos', function ($join) {
                $join->on('interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                    ->where('tipo_interaccion', '=', 'Contable - Cargo');
            })
            ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get();
    }

    public function agregarRegistro(){
        try {
            $this->importe = floatval(str_replace(['$',','],"",$this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            // $this->validate();
            // if($this->importe > $this->presupuestoDevengado){
            //     $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto devengado insuficiente', tipo: 'warning', tiempo: 3000);
            //     return;
            // }
            $cuenta = Cuenta::find($this->cuenta);
            $cuentaCargo = Cuenta::find($this->cuentaCargo);
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $registro = [
                'id' => 0,
                'codigoArea' => $this->selectCodigoArea,
                'observaciones' => $this->observaciones,
                'fechaRegistro' => $this->fechaRegistro,
                'areaResponsableId' => $this->selectCodigoAreaResponsable,
                'codigoAreaResponsable' =>$departamento->Codigo_completo,
                'descripcionAreaResponsable' =>$departamento->Nombre,
                'cuentaId' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' =>$cuenta->Descripcion_cuenta,
                'cuentaCargoId' => $this->cuentaCargo,
                'codigoCuentaCargo' => $cuentaCargo->Codigo_cuenta,
                'descripcionCuentaCargo' => $cuentaCargo->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                // 'pttoDevengado' => $this->presupuestoDevengado,
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error($e->getMessage());
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

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total) {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
    }


    public function limpiar(){
        $this->cuenta = "";
        $this->causaIva = "";
        $this->mes = "";
        $this->cuentaCargo = "";
        $this->presupuestoDevengado = 0;
        $this->importe = "";
        $this->selectCodigoAreaResponsable = "";
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario ($datosRegistro) {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->cuentaCargo = $datosRegistro['cuentaCargoId'];
        Log::info($this->cuentaCargo);
        // $this->presupuestoDevengado = $datosRegistro['devengado'];
        $this->dispatch('llenarFormulario', area: $this->selectCodigoAreaResponsable, cuenta: $this->cuenta, cuentaCargo: $this->cuentaCargo, mes: $this->mes, importe: $this->importe);
    }

    public function finalizarRegistros(){
        $this->dispatch('finalizar-registros');
    }
}
