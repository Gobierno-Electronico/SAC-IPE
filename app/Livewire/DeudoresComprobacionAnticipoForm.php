<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use Carbon\Carbon;
use Log;
use App\Models\Poliza;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use App\Enums\EstatusEvento;
use DB;


class DeudoresComprobacionAnticipoForm extends Component
{
    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoAreaResponsable = "";

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

    #[Validate('required', message: 'Evento requerido')]
    public $numeroEvento = "";

    #[Validate('required', message: 'Selector de pago de retenciones requerido')]
    public $selectorPagoRetenciones = "";

    #[Validate('required', message: 'Selector de banco requerido')]
    public $selectorBanco = "";

    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoDelEvento = "";

    public $tipoRegistro = "";
    public $cuentaContableAbono = "";
    public $cuentaBanco = "";
    public $importeBanco = "";

    public $consultarRegistro = false;
    public $numeroPoliza;
    public $total;
    public $tipoMovimiento;
    public $habilitarSelectorTipoRegistro;

    public $cuentasContableAbono = [];
    public $cambiarCuentaContableSeleccionada = true;

    public $PTTOEjercer = 0;

    public function render()
    {
        try {
            $eventos =  Poliza::select('evento', 'descripcion')
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->where('tipo_poliza', '=', 'D')
            ->where('categoria', '=', 'DEUDORES REINTEGRO ANTICIPOS')
            ->where('estatus_evento', '=', EstatusEvento::ACTIVO->value)
            ->distinct()
            ->pluck('descripcion', 'evento');

            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [10109])
            ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
            ->where('cuentas.Descripcion_cuenta', 'LIKE', '%Pagado%' )
            ->orderBy('cuentas.Descripcion_cuenta')->get();

            $this->cambiarCuentaContableSeleccionada = false;
            $this->llenarCuentasContableAbono();

            return view('livewire.deudores-comprobacion-anticipo-form', ['cuentas' => $cuentas, 'eventos' => $eventos]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar eventos y cuentas en deudores comprobación de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cambioEvento(){
        $this->limpiar(); 
        try{
            $this->montoDelEvento = DB::select('EXEC ImporteTotalComprobacionAnticipo @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el monto del evento en deudores comprobación de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarCantidadRelaciones()
    {
        $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('concepto_id', [10109])
        ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();

        $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
        ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
        ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
        ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Cargo')
        ->get()->toArray();

        if(count($interaccionCuentaCuentas) > 2)
        {
            $this->habilitarSelectorTipoRegistro = true;
        }else{
            $this->habilitarSelectorTipoRegistro = false;
        }

        $this->cargarPresupuestoPorEjercer();
    }

    public function llenarCuentasContableAbono()
    {

        if ($this->cambiarCuentaContableSeleccionada) {
            $this->cuentaContableAbono = "";
        }

        try{
            $this->cambiarCuentaContableSeleccionada = true;

            $this->cuentasContableAbono = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [10109])
            ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Abono')
            ->where('cuentas.Codigo_cuenta', 'LIKE', '%2.1.1.7.01.%' )
            ->orWhere('cuentas.Descripcion_cuenta', '=', 'Responsabilidad de Funcionarios y Empleados Ejercicio Actual')
            ->orderBy('cuentas.Descripcion_cuenta')->get();

            $this->cuentasContableAbono = $this->cuentasContableAbono->unique('Descripcion_cuenta');

        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar las cuentas contables en deudores comprobación de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas contables, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cargarPresupuestoPorEjercer()
    {
        try{
            if (!$this->cuenta || !$this->mes || !$this->selectCodigoAreaResponsable) return;

            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('interaccion_cuenta_conceptos.concepto_id', [10109])->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
            ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Por ejercer)%')->first();

            $solvencia = DB::select('EXEC SolvenciaCuentasPorEjercer @area = ?, @cuenta = ?, @anio = ?, @mes = ?', array($departamento->Codigo_completo, $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $this->mes))[0]->Solvencia;
            $this->PTTOEjercer = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputPTTOEjercer', amount: "{$this->PTTOEjercer}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar cargado', tipo: 'success', tiempo: 1500); 
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en deudores comprobación de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

     public function asignarCuentaContableAbono()
    {
        try{
            $descripcionPartida = Cuenta::select('Descripcion_cuenta')->where('id', '=', $this->cuenta)->get();
            $conceptoGeneralPartida = rtrim(explode('(', $descripcionPartida[0]->Descripcion_cuenta)[0]);
                    
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('interaccion_cuenta_conceptos.concepto_id', [10109])
            ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $cuentasContables = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
                ->join('interaccion_cuenta_conceptos', function ($join) {
                    $join->on('interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                        ->where('tipo_interaccion', '=', 'Contable - Abono');
                })
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->where('cuentas.Descripcion_cuenta', 'like', '%' . $conceptoGeneralPartida . '%')
                ->get(); 
            
            $this->cuentaContableAbono = $cuentasContables[0]->cuenta_id;
        }catch(\Throwable $th) {
            Log::error('Ocurrió un error al asignar cuenta contable en deudores comprobación de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al asignar cuenta contable, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    } 


    public function agregarRegistro()
    {
        try{
            if($this->selectorPagoRetenciones == 'SI' && $this->cuentaContableAbono == ""){
                $this->dispatch('mostrarMensaje', mensaje: 'Retención requerida', tipo: 'warning', tiempo: 3000);
                return;
            }

            if($this->selectorPagoRetenciones == 'NO'){
                $this->asignarCuentaContableAbono();
            }

            if($this->selectorBanco == 'SI' && $this->cuentaBanco == ""){
                $this->dispatch('mostrarMensaje', mensaje: 'Cuenta de banco requerida', tipo: 'warning', tiempo: 3000);
                return;
            }

            if($this->habilitarSelectorTipoRegistro == true && $this->tipoRegistro == ""){
                $this->dispatch('mostrarMensaje', mensaje: 'Tipo de registro requerido', tipo: 'warning', tiempo: 3000);
                return;
            }

            if($this->selectorBanco == 'SI'){
                if($this->importeBanco == ""){
                    $this->dispatch('mostrarMensaje', mensaje: 'Importe de banco requerido', tipo: 'warning', tiempo: 3000);
                    return;
                }else{
                    $this->importeBanco = floatval(str_replace(['$', ','], "", $this->importeBanco));
                    $this->importeBanco = ($this->importeBanco > 0)  ? $this->importeBanco : "";
                }
            }

            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();

            $cuenta = Cuenta::find($this->cuenta);
            $cuentaContableAbonoSeleccionada = Cuenta::find($this->cuentaContableAbono);
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);

            $registro = [
                'id' => 0,
                'codigoArea' => $this->selectCodigoArea,
                'observaciones' => $this->observaciones,
                'fechaAfectacion' => $this->fechaAfectacion,
                'evento' => $this->numeroEvento,
                'areaResponsableId' => $this->selectCodigoAreaResponsable,
                'codigoAreaResponsable' => $departamento->Codigo_completo,
                'descripcionAreaResponsable' => $departamento->Nombre,
                'cuentaId' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' => $cuenta->Descripcion_cuenta,
                'mes' => $this->mes,
                'cuentaContableId' => $this->cuentaContableAbono,
                'codigoCuentaContable' => $cuentaContableAbonoSeleccionada->Codigo_cuenta,
                'descripcionCuentaContable' => $cuentaContableAbonoSeleccionada->Descripcion_cuenta,
                'importe' => $this->importe,
                'montoEvento' => $this->montoDelEvento,
                'pttoEjercer' => $this->PTTOEjercer,
                'selectorPagoRetenciones' => $this->selectorPagoRetenciones,
                'tipoRegistro' => $this->tipoRegistro,
                'selectorBanco' => $this->selectorBanco,
                'cuentaBancoId' => $this->cuentaBanco,
                'importeBanco' => $this->importeBanco
            ];

            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al registrar en deudores comprobación de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function limpiar()
    {
        $this->PTTOEjercer = "";
        $this->cuenta = "";
        $this->mes = "";
        $this->importe = "";
        $this->tipoRegistro = "";
        $this->habilitarSelectorTipoRegistro = false;
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
        $this->cuenta = $datosRegistro['cuenta'];
        $this->cuentaContableAbono = $datosRegistro['cuentaContable'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->PTTOEjercer = $datosRegistro['pttoEjercer'];
        $this->selectorPagoRetenciones = $datosRegistro['selectorPagoRetenciones'];
        $this->tipoRegistro = $datosRegistro['tipoRegistro'];
        $this->selectorBanco = $datosRegistro['selectorBanco'];
        $this->cuentaBanco = $datosRegistro['cuentaBanco'];
        $this->importeBanco = $datosRegistro['importeBanco'];

        if($this->tipoRegistro != ''){
            $this->habilitarSelectorTipoRegistro = true;
        }

        $this->dispatch('llenarFormulario', presupuesto: $this->PTTOEjercer, importe: $this->importe, importeBanco: $this->importeBanco);
    }
}
