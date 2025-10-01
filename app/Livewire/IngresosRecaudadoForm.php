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
use App\Enums\EstatusEvento;

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

    #[Validate('required', message: 'Fecha de afectación requerida')]
    public $fechaAfectacion = "";

    #[Validate('required', message: 'Cuenta de pago requerida')]
    public $cuentaPago = "";

    #[Validate('required', message: 'Solvencia presupuestal requerida')]
    public $solvenciaPresupuestal = "";

    #[Validate('required', message: 'Solvencia abono requerida')]
    public $solvenciaAbono = "";

    public $subcuentas = [];

    public $numeroPoliza;

    public $cambiarCuentaPagoSeleccionada = true;

    public $consultarRegistro = false;
    public $numeroPolizaRemanente;
    public $total;

    public function render()
    {
        try {
            //code...
            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->whereIn('interaccion_cuenta_conceptos.concepto_id', [19, 20, 21, 35, 39, 10115, 10116, 10117])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Abono')
                ->orderBy('cuentas.Codigo_cuenta')->get();
            $eventos =  Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'I')
                ->where('categoria', '=', 'INGRESOS DEVENGADO')
                ->where('estatus_evento', '=', EstatusEvento::ACTIVO->value)
                ->distinct()
                ->pluck('descripcion', 'evento');
            $this->cambiarCuentaPagoSeleccionada = false;
            $this->llenarCuentasPago();
            return view('livewire.ingresos-recaudado-form', ['eventos' => $eventos, 'cuentas' => $cuentas]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en Recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cambioEvento()
    {
        try {
            $this->llenarCamposEspecificos();
            $this->montoDelEvento = DB::select('EXEC ImporteTotalRecaudado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
            $this->cambiarCuentaPagoSeleccionada = false;
            $this->llenarCuentasPago();
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function obtenerSolvenciaPresupuestal()
    {
        try{
            if (!$this->cuenta || !$this->mes || !$this->selectCodigoAreaResponsable) return;
            $this->llenarCuentasPago();

            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)
                ->whereIn('concepto_id', [19, 20, 21, 35, 39])
                ->where('tipo_interaccion', '=', 'Presupuestal - Abono')
                ->first();

            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
                ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->where('Descripcion_cuenta', 'LIKE', '%(Devengado)%')
                ->first();

            $solvencia = DB::select('EXEC DevengadoCuentaArea @area = ?, @cuenta = ?, @anio = ?, @mes = ?, @evento = ?', array($departamento->Codigo_completo, $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $this->mes, $this->numeroEvento))[0]->TotalDevengado;
            $this->solvenciaPresupuestal = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputSolvenciaPresupuestal', amount:"{$this->solvenciaPresupuestal}");
            $this->dispatch('mostrarMensaje', mensaje: 'Solvencia cargada', tipo: 'success', tiempo: 1500);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al obtener la solvencia presupuestal en recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al obtener solvencia, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function obtenerSolvenciaContable()
    {
        try{
            $anioActual = Carbon::now()->year;
            $cuentaAbono = Cuenta::find($this->cuentaPago);
            $solvencia = DB::select('EXEC SolvenciaCuentasContables @cuenta = ?, @anio = ?', array($cuentaAbono->Codigo_cuenta, $anioActual))[0]->Solvencia;
            $this->solvenciaAbono = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputSolvenciaAbono', amount:"{$this->solvenciaAbono}");
            $this->dispatch('mostrarMensaje', mensaje: 'Solvencia cargada', tipo: 'success', tiempo: 1500);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al obtener solvencia de cuenta abono en recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al obtener solvencia, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCamposEspecificos(){
        try{      
            $descripcionEvento = Poliza::select('descripcion')
                ->where('evento', '=', $this->numeroEvento)
                ->where('tipo_poliza', '=', 'I')
                ->where('categoria', '=', 'INGRESOS DEVENGADO')
                ->get()[0]->descripcion;
        
            $areaEvento = Poliza::select('area')
                ->where('evento', '=', $this->numeroEvento)
                ->where('tipo_poliza', '=', 'I')
                ->where('categoria', '=', 'INGRESOS DEVENGADO')
                ->get()[0]->area;
        
            $idArea = CodigoDepartamento::select('id')
                ->where('Codigo_completo', '=', $areaEvento)
                ->get()[0]->id; 
        
            $this->observaciones = $descripcionEvento;
            $this->selectCodigoAreaResponsable = $idArea;   
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al llenar campos específicos en recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCuentasPago()
    {
        try {
            //code...
            if (!$this->cuenta) {
                return;
            }

            if ($this->cambiarCuentaPagoSeleccionada) {
                $this->cuentaPago = "";
            }

            $this->cambiarCuentaPagoSeleccionada = true;
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('interaccion_cuenta_conceptos.concepto_id', [19, 20, 21, 35, 39, 10114, 10115, 10116, 10117])
                ->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();
            $this->subcuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
                ->join('interaccion_cuenta_conceptos', function ($join) {
                    $join->on('interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                        ->where('tipo_interaccion', '=', 'Contable - Cargo');
                })
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get();
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar las cuentas de pago en recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas de pago, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
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
                'fechaAfectacion' => $this->fechaAfectacion,
                'evento' => $this->numeroEvento,
                'areaResponsableId' => $this->selectCodigoAreaResponsable,
                'codigoAreaResponsable' => $departamento->Codigo_completo,
                'descripcionAreaResponsable' => $departamento->Nombre,
                'cuentaId' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' => $cuenta->Descripcion_cuenta,
                'cuentaPagoId' => $this->cuentaPago,
                'codigoCuentaPago' => $cuentaPagoSeleccionada->Codigo_cuenta,
                'descripcionCuentaPago' => $cuentaPagoSeleccionada->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'montoEvento' => $this->montoDelEvento,
                'solvenciaPresupuestal' => $this->solvenciaPresupuestal,
                'solvenciaAbono' => $this->solvenciaAbono
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function limpiar()
    {
        $this->cuenta = "";
        $this->cuentaPago = "";
        $this->mes = "";
        $this->importe = "";
        $this->solvenciaPresupuestal = "";
        $this->solvenciaAbono = "";
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
        $this->solvenciaPresupuestal = $datosRegistro['solvenciaPresupuestal'];
        $this->solvenciaAbono = $datosRegistro['solvenciaAbono'];
        $this->dispatch('llenarFormulario', cuenta: $datosRegistro['cuenta'], cuentaPago: $datosRegistro['cuentaPago'], mes: $datosRegistro['mes'], 
        importe: $datosRegistro['importe'], area: $datosRegistro['area'], solvenciaPresupuestal: $datosRegistro['solvenciaPresupuestal'], solvenciaAbono: $datosRegistro['solvenciaAbono']);
    }

    public function finalizarRegistros()
    {
        $this->dispatch('finalizar-registros');
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total, $numeroPolizaRemanente)
    {
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
        $this->numeroPolizaRemanente = $numeroPolizaRemanente;
        $this->consultarRegistro = true;
    }
}
