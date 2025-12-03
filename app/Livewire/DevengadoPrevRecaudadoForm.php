<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\CodigoDepartamento;
use App\Models\Poliza;
use Carbon\Carbon;
use Log;
use DB;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Enums\EstatusEvento;


class DevengadoPrevRecaudadoForm extends Component
{
    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoPorClasificar = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Fecha de afectación requerida')]
    public $fechaAfectacion = "";

    #[Validate('required', message: 'Solvencia presupuestal requerida')]
    public $solvenciaPresupuestal = "";

    #[Validate('required', message: 'Documento fuente requerido')]
    public $documentoFuente = "";


    // #[Validate('required', message: 'Cuenta de pago requerida')]
    // public $cuentaPago = "";

    public $subcuentas = [];

    public $cambiarCuentaPagoSeleccionada = true;

    public $causaIva = 0;
    public $agregarIVA = "";

    public $consultarRegistro = false;
    public $numeroPoliza;
    public $numeroEvento;
    public $numeroPolizaRemanente;
    public $total;

    public function render()
    {
        try {
            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->where('interaccion_cuenta_conceptos.concepto_id', '=', 14)->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Abono')
                ->where('cuentas.Descripcion_cuenta', 'LIKE', '%(Devengado)%')->orderBy('cuentas.Codigo_cuenta')->get();

            $this->cambiarCuentaPagoSeleccionada = false;
            // $this->llenarCuentasPago();
            $this->verificarCausaIVA();

            $solvenciaPorClasificar = DB::select('EXEC SolvenciaIngresosPorClasificarGeneral')[0]->Total;
            $this->montoPorClasificar = ($solvenciaPorClasificar > 0) ? $solvenciaPorClasificar : 0;

            return view('livewire.devengado-prev-recaudado-form', ['cuentas' => $cuentas, 'montoPorClasificar' => $this->montoPorClasificar]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en Devengado previamente recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function obtenerSolvenciaPresupuestal()
    {
        try{
            $this->verificarCausaIVA();
            if (!$this->cuenta || !$this->mes || !$this->selectCodigoAreaResponsable) return;
    
            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->where('concepto_id', '=', 14)->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
                    ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Por ejecutar)%')->first();
    
            $solvencia = DB::select('EXEC SolvenciaCuentaArea @area = ?, @cuenta = ?, @anio = ?, @mes = ?', array($departamento->Codigo_completo, $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $this->mes))[0]->Solvencia;
            $this->solvenciaPresupuestal = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputSolvenciaPresupuestal', amount:"{$this->solvenciaPresupuestal}");
            $this->dispatch('mostrarMensaje', mensaje: 'Solvencia cargada', tipo: 'success', tiempo: 1500);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al obtener la solvencia presupuestal en Devengado previamente recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al obtener solvencia, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function agregarRegistro()
    {
        try {
            if ($this->causaIva > 0) {
                if ($this->agregarIVA != "") {
                    if ($this->agregarIVA == 'NO') {
                        $this->causaIva = 0;
                    }
                } else {
                    $this->dispatch('mostrarMensaje', mensaje: 'Selección agregar IVA requerido', tipo: 'warning', tiempo: 3000);
                    return;
                }
            }

            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->causaIva = floatval(str_replace(['$', ','], "", $this->causaIva));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();
            $cuenta = Cuenta::find($this->cuenta);
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $registro = [
                'id' => 0,
                'codigoArea' => $this->selectCodigoArea,
                'observaciones' => $this->observaciones,
                'areaResponsableId' => $this->selectCodigoAreaResponsable,
                'codigoAreaResponsable' => $departamento->Codigo_completo,
                'descripcionAreaResponsable' => $departamento->Nombre,
                'cuentaId' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' => $cuenta->Descripcion_cuenta,
                'mes' => $this->mes,
                'fechaAfectacion' => $this->fechaAfectacion,
                'importe' => $this->importe,
                'montoPorClasificar' => $this->montoPorClasificar,
                'iva' => $this->causaIva,
                'agregarIVA' => $this->agregarIVA,
                'solvenciaPresupuestal' => $this->solvenciaPresupuestal,
                'documentoFuente' => $this->documentoFuente
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en Devengado previamente recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarCausaIVA()
    {
        try {
            if (!$this->cuenta) return;
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('interaccion_cuenta_conceptos.concepto_id', [14])->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();
            $interaccionCuentasCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
                ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();

            foreach ($interaccionCuentasCuentas as $key => $dataCuenta) {
                if (str_contains($dataCuenta['Descripcion_cuenta'], 'IVA')) {
                    if ($this->importe == "") {
                        $this->dispatch('limpiarIVA');
                    } else {

                        $importeFormateado = str_replace(['$', ','], '', $this->importe);
                        $this->causaIva = ($importeFormateado / 1.16) * 0.16;
                        $this->dispatch('formato_importe', id: 'inputIva', amount: "{$this->causaIva}");
                    }
                } else {
                    $this->causaIva = 0;
                    $this->agregarIVA = "";
                    $this->dispatch('limpiarIVA');
                }
            }
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al calcular IVA en Devengado previamente recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al calcular IVA, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
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
        $this->causaIva = 0;
        $this->agregarIVA = "";
        $this->dispatch('limpiar');
        $this->dispatch('limpiarIVA');
    }


    public function finalizarRegistros()
    {
        $this->dispatch('finalizar-registros');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        try {
            //code...
            $this->cuenta = $datosRegistro['cuenta'];
            $this->mes = $datosRegistro['mes'];
            $this->importe = $datosRegistro['importe'];
            $this->selectCodigoAreaResponsable = $datosRegistro['area'];
            $this->agregarIVA = $datosRegistro['agregarIVA'];
            $this->solvenciaPresupuestal = $datosRegistro['solvenciaPresupuestal'];
            $this->documentoFuente = $datosRegistro['documentoFuente'];
            $this->verificarCausaIVA();
            $this->dispatch('llenarFormulario', cuenta: $datosRegistro['cuenta'], mes: $datosRegistro['mes'], importe: $datosRegistro['importe'], area: $datosRegistro['area'], agregarIVA: $datosRegistro['agregarIVA'], solvenciaPresupuestal: $datosRegistro['solvenciaPresupuestal']);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al llenar formulario en Devengado previamente recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al llenar formulario, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
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
