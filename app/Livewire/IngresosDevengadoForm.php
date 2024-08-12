<?php

namespace App\Livewire;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use DB;
use Carbon\Carbon;
use Log;
use Illuminate\Database\Eloquent\Builder;

class IngresosDevengadoForm extends Component
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

    #[Validate('required', message: 'Presupuesto por ejecutar insuficiente')]
    #[Validate('numeric', message: 'Presupuesto por ejecutar insuficiente')]
    #[Validate('min:1', message: 'Presupuesto por ejecutar insuficiente')]
    public $PTTOEjecutar = 0;

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    public $causaIva = 0;
    public $agregarIVA = "";

    public $consultarRegistro = false;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    
    public $tipoMovimiento;

    public function render()
    {
        $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [15,16,17,18,38])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Abono')
            ->orderBy('cuentas.Codigo_cuenta')->get();
        $this->verificarCausaIVA();
        return view('livewire.ingresos-devengado-form', ['cuentas' => $cuentas]);
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
            if(($this->importe) > $this->PTTOEjecutar){
                $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar insuficiente', tipo: 'warning', tiempo: 3000);
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
                'pttoEjecutar' => $this->PTTOEjecutar,
                'iva' => $this->causaIva,
                'agregarIVA' => $this->agregarIVA,
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

    public function cambioPresupuesto() {
        if(!$this->cuenta || !$this->mes || !$this->selectCodigoAreaResponsable) return;
        $this->limpiarImporteIva();
    
        $anioActual = Carbon::now()->year;
        $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
        $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('interaccion_cuenta_conceptos.concepto_id', [15,16,17,18, 38])->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();
        $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
            ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Por ejecutar)%')->first();
        $solvencia = DB::select('EXEC SolvenciaCuentaArea @area = ?, @cuenta = ?, @anio = ?, @mes = ?', array($departamento->Codigo_completo, $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $this->mes))[0]->Solvencia;
        $this->PTTOEjecutar = ($solvencia > 0) ? floatval($solvencia) : 0;
        $this->dispatch('formato_importe', id: 'inputPTTOEjecutar', amount: "{$this->PTTOEjecutar}");
        $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar cargado', tipo : 'success', tiempo: 1500);
    }

    public function verificarCausaIVA() {
        if(!$this->cuenta) return;
        $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('interaccion_cuenta_conceptos.concepto_id', [15,16,17,18, 38])->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();
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

    public function limpiar(){
        $this->cuenta = "";
        $this->causaIva = 0;
        $this->mes = "";
        $this->PTTOEjecutar = 0;
        $this->importe = "";
        $this->agregarIVA = "";
        $this->dispatch('limpiar');
        $this->dispatch('limpiarIVA');
    }

    public function limpiarImporteIva(){
        $this->causaIva = 0;
        $this->importe = "";
        $this->dispatch('limpiarImporteIva');
    }

    #[On('reiniciar')]
    public function reiniciar() {
        $this->limpiar();
        $this->consultarRegistro = false;
        $this->numeroEvento = 0;
        $this->numeroPoliza = 0;
        $this->total = 0;
    }

    #[On('llenar-formulario')]
    public function llenarFormulario ($datosRegistro) {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->PTTOEjecutar = $datosRegistro['ejecutar'];
        $this->agregarIVA = $datosRegistro['agregarIVA'];
        $this->verificarCausaIVA();
        $this->dispatch('llenarFormulario', presupuesto: $this->PTTOEjecutar, iva: $this->causaIva, importe: $this->importe);
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
}
