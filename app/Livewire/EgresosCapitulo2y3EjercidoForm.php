<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\Poliza;
use App\Models\CodigoDepartamento;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use DB;
use Log;

class EgresosCapitulo2y3EjercidoForm extends Component
{
    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Fecha de afectación requerida')]
    public $fechaAfectacion = "";

    #[Validate('required', message: 'Evento requerido')]
    public $numeroEvento = "";

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoDelEvento = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe;

    public $consultarRegistro = false;
    public $numeroPoliza;
    public $numeroPolizaRemanente;
    public $total;

    public $cuentas = [];
    public $cambiarCuentaSeleccionada = true;
    public $PTTODevengado = 0;

    public function render() 
    {
        try{
            $eventos =  Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 2y3')
                ->where('estatus_evento', '=', true)
                ->distinct()
                ->pluck('descripcion', 'evento');

            $this->cambiarCuentaSeleccionada = false;
            $this->llenarCuentas();
                
            return view('livewire.egresos-capitulo2y3-ejercido-form', ['eventos' => $eventos]);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar cuentas en ejercido del capítulo 2 y 3: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }
    }

    public function cambioEvento(){
        try{
            $this->limpiar();
            $this->montoDelEvento = DB::select('EXEC ImporteTotalCapitulo2y3Ejercido @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
            $this->llenarCuentas();
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en ejercido del capítulo 2 y 3: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCuentas()
    {
        try{

            if(!$this->numeroEvento){
                return;
            }

            if ($this->cambiarCuentaSeleccionada) {
                $this->cuenta = "";
                //$this->cargarPresupuestoDevengado();
            }

            $this->cambiarCuentaSeleccionada = true;

            $cuentasDevengadas = Poliza::join('cuentas', 'cuentas.Codigo_cuenta', '=', 'polizas.cuenta')
            ->where('polizas.evento', '=', $this->numeroEvento)
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->where('polizas.tipo_poliza', '=', 'E')
            ->where('polizas.concepto', 'LIKE', '%Devengado%')
            ->get();

            foreach ($cuentasDevengadas as $devengada) {
                $interaccionCuentaConceptoDevengado = InteraccionCuentaConcepto::where('cuenta_id', '=', $devengada->id)->whereIn('concepto_id', [90, 91])
                ->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();

                $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_2', '=', $interaccionCuentaConceptoDevengado->id)
                ->first();
                
                $interaccionCuentaEjercida = InteraccionCuentaConcepto::where('id', '=', $interaccionCuentaCuenta->id_interaccion_concepto_cuenta_1)
                ->whereIn('concepto_id', [90, 91])->where('tipo_interaccion', '=', 'Presupuestal - Cargo')
                ->first();

                $cuentaEjercida = Cuenta::where('id', '=', $interaccionCuentaEjercida->cuenta_id)->first();
                array_push($this->cuentas, $cuentaEjercida);
            }

            $this->cuentas = array_unique($this->cuentas);

        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al llenar las cuentas en Ejercido del capítulo 2 y 3: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cargarPresupuestoDevengado()
    {
        try{
/*             $this->cambiarCuentaSeleccionada = false;
            $this->llenarCuentas(); */
            if (!$this->cuenta || !$this->mes || !$this->selectCodigoAreaResponsable) return;
            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('interaccion_cuenta_conceptos.concepto_id', [90, 91])->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
            ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Devengado)%')->first();
            
            $solvencia = DB::select('EXEC SolvenciaDevengadosCapitulo2y3 @area = ?, @cuenta = ?, @anio = ?, @mes = ?, @evento = ?', array($departamento->Codigo_completo, $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $this->mes, $this->numeroEvento))[0]->Total;
            $this->PTTODevengado = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputPTTODevengado', amount: "{$this->PTTODevengado}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto devengado cargado', tipo: 'success', tiempo: 1500);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en ejercido del capítulo 2 y 3: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function agregarRegistro()
    {
        try{
            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();

            $cuenta = Cuenta::find($this->cuenta);
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
                'importe' => $this->importe,
                'montoEvento' => $this->montoDelEvento,
                'pttoDevengado' => $this->PTTODevengado
            ];

            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en ejercido del capítulo 2 y 3: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function finalizarRegistros()
    {
        $this->dispatch('finalizar-registros');
    }

    public function limpiar()
    {
        $this->cuenta = "";
        $this->selectCodigoAreaResponsable = "";
        $this->PTTODevengado = "";
        $this->cuentas = "";
        $this->importe = "";
        $this->mes = "";
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->PTTODevengado = $datosRegistro['pttoDevengado'];
        $this->dispatch('llenarFormulario', presupuesto: $this->PTTODevengado, importe: $this->importe);
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total, $numeroPolizaRemanente)
    {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->numeroPolizaRemanente = $numeroPolizaRemanente;
        $this->total = $total;
    }
}