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
use DB;
use Log;

class AutorizacionDevolucionForm extends Component
{
    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message:'Fecha requerida')]
    public $fechaRegistro = "";

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Presupuesto devengado insuficiente')]
    #[Validate('numeric', message: 'Presupuesto devengado insuficiente')]
    #[Validate('min:1', message: 'Presupuesto devengado insuficiente')]
    public $presupuestoDevengado = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";
    public $consultarRegistro = false;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $tipoMovimiento;

    public $causaIva = 0;
    public $agregarIVA = "";

    public function render()
    {
        $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [22,23,24,25])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
            ->where('cuentas.Descripcion_cuenta', 'LIKE', '%(Devengado)%')->orderBy('cuentas.Codigo_cuenta')->get();
        return view('livewire.autorizacion-devolucion-form', ['cuentas' => $cuentas]);
    }

    public function cambioPresupuesto() {
        if(!$this->cuenta || !$this->mes || !$this->selectCodigoAreaResponsable) return;
        $this->limpiarImporteIva();
        $anioActual = Carbon::now()->year;
        $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
        $cuenta = Cuenta::find($this->cuenta);
        $solvencia = DB::select('EXEC DevengadoCuentaArea @area = ?, @cuenta = ?, @anio = ?, @mes = ?', array($departamento->Codigo_completo, $cuenta->Codigo_cuenta, $anioActual, $this->mes))[0]->TotalDevengado;
        $this->presupuestoDevengado = ($solvencia > 0) ? floatval($solvencia) : 0;
        $this->dispatch('formato_importe', id: 'inputPTODevengado', amount: "{$this->presupuestoDevengado}");
        $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar cargado', tipo : 'success', tiempo: 1500);
    }

    public function agregarRegistro(){
        try {
            if($this->causaIva > 0){
                if($this->agregarIVA != ""){
                    if($this->agregarIVA == 'NO'){
                        $this->causaIva = 0;
                    }
                }else{
                    $this->dispatch('mostrarMensaje', mensaje: 'Selección agregar IVA requerido', tipo: 'warning', tiempo: 3000);
                    return;
                }
            }
            $this->importe = floatval(str_replace(['$',','],"",$this->importe));
            $this->causaIva = floatval(str_replace(['$',','],"",$this->causaIva));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();
            if($this->importe > $this->presupuestoDevengado){
                $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto devengado insuficiente', tipo: 'warning', tiempo: 3000);
                return;
            }
            $cuenta = Cuenta::find($this->cuenta);
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
                'mes' => $this->mes,
                'importe' => $this->importe,
                'pttoDevengado' => $this->presupuestoDevengado,
                'iva' => $this->causaIva
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

    public function verificarCausaIVA() {
        if(!$this->cuenta) return;
        $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('interaccion_cuenta_conceptos.concepto_id', [22,23,24,25])->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
        $interaccionCuentasCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
        ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
        ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();

        foreach ($interaccionCuentasCuentas as $key => $dataCuenta) {
            if(str_contains($dataCuenta['Descripcion_cuenta'], 'IVA')){
                if($this->importe == ""){
                    $this->dispatch('limpiarIVA');
                }else{
                    $importeFormateado = str_replace(['$',','], '', $this->importe);          
                    $this->causaIva = $importeFormateado * 0.16;
                    $this->dispatch('formato_importe', id: 'inputIva', amount: "{$this->causaIva}");
                }
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
        $this->mes = "";
        $this->presupuestoDevengado = 0;
        $this->importe = "";
        $this->causaIva = 0;
        $this->agregarIVA = "";
        $this->dispatch('limpiar');
    }

    public function limpiarImporteIva(){
        $this->causaIva = 0;
        $this->importe = "";
        $this->dispatch('limpiarImporteIva');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario ($datosRegistro) {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->presupuestoDevengado = $datosRegistro['devengado'];
        $this->causaIva = $datosRegistro['iva'];
        $this->dispatch('llenarFormulario', presupuesto: $this->presupuestoDevengado, iva: $this->causaIva, importe: $this->importe);
    }

    public function finalizarRegistros(){
        $this->dispatch('finalizar-registros');
    }
}
