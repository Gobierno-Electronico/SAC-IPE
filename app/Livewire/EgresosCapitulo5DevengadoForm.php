<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\Poliza;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Log;
use DB;
use App\Enums\EstatusEvento;

class EgresosCapitulo5DevengadoForm extends Component
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

    #[Validate('required', message: 'Partida presupuestal requerida')]
    public $partidaPresupuestal = "";

    public $cuentaContableAbono = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Selector de pago de retenciones requerido')]
    public $selectorPagoRetenciones = "";

    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoDelEvento = "";

    #[Validate('required', message: 'Documento fuente requerido')]
    public $documentoFuente = "";

    public $PTTOComprometido = 0;

    public $consultarRegistro = false;
    public $numeroPoliza;
    public $numeroPolizaRemanente;
    public $total;

    public $partidasPresupuestales = [];
    public $cambiarPartidaPresupuestalSeleccionada = true;
    
    public $cuentasContableAbono = [];
    public $cambiarCuentaContableSeleccionada = true;
    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
        $this->fechaAfectacion = "{$this->anio}-01-01";
    }
    
    public function render() 
    {
        try{
            $eventos =  Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', (string) $this->anio)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 5')
                ->where('estatus_evento', '=', EstatusEvento::ACTIVO->value)
                ->distinct()
                ->pluck('descripcion', 'evento');
                
            $this->cambiarPartidaPresupuestalSeleccionada = false;
            $this->llenarPartidasPresupuestales();


            $this->cambiarCuentaContableSeleccionada = false;

            $this->llenarCuentasContableAbono();

            return view('livewire.egresos-capitulo5-devengado-form', ['eventos' => $eventos]);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar eventos en Devengado del capítulo 5: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar los eventos, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }
    }

    public function cambioEvento(){
        $this->limpiar(); 
        try{
            $this->llenarCamposEspecificos();
            $this->montoDelEvento = DB::select('EXEC ImporteTotalCapitulo5Devengado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
            Log::info('evento');
            $this->llenarPartidasPresupuestales();
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en Devengado del capítulo 5: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCamposEspecificos(){
        try{      
            $descripcionEvento = Poliza::select('descripcion')
                ->where('evento', '=', $this->numeroEvento)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 5')
                ->get()[0]->descripcion;
        
            $areaEvento = Poliza::select('area')
                ->where('evento', '=', $this->numeroEvento)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 5')
                ->get()[0]->area;
        
            $idArea = CodigoDepartamento::select('id')
                ->where('Codigo_completo', '=', $areaEvento)
                ->get()[0]->id; 
        
            $this->observaciones = $descripcionEvento;
            $this->selectCodigoAreaResponsable = $idArea;   
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al llenar campos específicos en Devengado del capítulo 5: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarPartidasPresupuestales()
    {
        if(!$this->numeroEvento){
            return;
        }
        if ($this->cambiarPartidaPresupuestalSeleccionada) {
            $this->partidaPresupuestal = "";
        }
        $this->cambiarPartidaPresupuestalSeleccionada = true;

        try{
            $cuentasComprometidas = Poliza::where('evento', '=', $this->numeroEvento)
            ->whereYear('fecha', '=', (string) $this->anio)
            ->where('tipo_poliza', '=', 'E')
            ->where('concepto', 'LIKE', '%Comprometido%')
            ->get();

            $cuentasDevengadas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [69, 70, 71])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
            ->orderBy('cuentas.Codigo_cuenta')->get();


            $cuentasDevengadasAux = new Collection();
            foreach($cuentasDevengadas as $devengada){
                foreach($cuentasComprometidas as $comprometida){
                    $conceptoComprometida = explode('(', $comprometida->concepto);
                    if(str_contains($devengada->Descripcion_cuenta, $conceptoComprometida[0])){
                        $cuentasDevengadasAux->push($devengada);
                    }
                }
            }
            $cuentasDevengadasAux = $cuentasDevengadasAux->unique('Codigo_cuenta');
            $this->partidasPresupuestales = $cuentasDevengadasAux->toArray();
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar partidas presupuestales en Devengado del capítulo 5: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las partidas presupuestales, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cargarPresupuestoComprometido()
    {

        if (!$this->partidaPresupuestal || !$this->mes || !$this->selectCodigoAreaResponsable) return;
        
        try{
            $anioActual = (string) $this->anio;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->partidaPresupuestal)->whereIn('interaccion_cuenta_conceptos.concepto_id', [69, 70, 71])->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
            ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Comprometido)%')->first();

            $solvencia = DB::select('EXEC SolvenciaComprometidosCapitulo5 @area = ?, @cuenta = ?, @anio = ?, @mes = ?, @evento = ?', array($departamento->Codigo_completo, $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $this->mes, $this->numeroEvento))[0]->Total;
            $this->PTTOComprometido = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputPTTOComprometido', amount: "{$this->PTTOComprometido}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto comprometido cargado', tipo: 'success', tiempo: 1500);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en devengado del capítulo 5: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCuentasContableAbono()
    {
        if(!$this->partidaPresupuestal) return;
        if ($this->cambiarCuentaContableSeleccionada) {
            $this->cuentaContableAbono = "";
        }

        try{
            $this->cambiarCuentaContableSeleccionada = true;

            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->partidaPresupuestal)->whereIn('interaccion_cuenta_conceptos.concepto_id', [69, 70, 71])
            ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $this->cuentasContableAbono = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
                ->join('interaccion_cuenta_conceptos', function ($join) {
                    $join->on('interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                        ->where('tipo_interaccion', '=', 'Contable - Abono');
                })
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->get(); 
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar las cuentas contables en devengado capítulo 5000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas contables, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function asignarCuentaContableAbono()
    {
        try{
            $descripcionPartida = Cuenta::select('Descripcion_cuenta')->where('id', '=', $this->partidaPresupuestal)->get();
            $conceptoGeneralPartida = rtrim(explode('(', $descripcionPartida[0]->Descripcion_cuenta)[0]);
                    
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->partidaPresupuestal)->whereIn('interaccion_cuenta_conceptos.concepto_id', [69, 70, 71])
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
            Log::error('Ocurrió un error al asignar cuenta contable en devengado capítulo 5000: ' . $th->getMessage());
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

            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();

            $partida = Cuenta::find($this->partidaPresupuestal);
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
                'partidaId' => $this->partidaPresupuestal,
                'codigoPartida' => $partida->Codigo_cuenta,
                'descripcionPartida' => $partida->Descripcion_cuenta,
                'cuentaContableId' => $this->cuentaContableAbono,
                'codigoCuentaContable' => $cuentaContableAbonoSeleccionada->Codigo_cuenta,
                'descripcionCuentaContable' => $cuentaContableAbonoSeleccionada->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'montoEvento' => $this->montoDelEvento,
                'pttoComprometido' => $this->PTTOComprometido,
                'selectorPagoRetenciones' => $this->selectorPagoRetenciones,
                'documentoFuente' => $this->documentoFuente
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al registrar en devengado del capítulo 5: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function finalizarRegistro()
    {
        $this->dispatch('finalizar-registros');
    }

    public function limpiar()
    {
        $this->PTTOComprometido = "";
        $this->importe = "";
        $this->mes = "";
        $this->selectorPagoRetenciones = "";
        $this->partidasPresupuestales = [];
        $this->cuentaContableAbono = "";
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $this->partidaPresupuestal = $datosRegistro['partida']; 
        $this->cuentaContableAbono = $datosRegistro['cuentaContable'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->PTTOComprometido = $datosRegistro['pttoComprometido'];
        $this->selectorPagoRetenciones = $datosRegistro['selectorPagoRetenciones'];
        $this->documentoFuente = $datosRegistro['documentoFuente'];
        $this->dispatch('llenarFormulario', presupuesto: $this->PTTOComprometido, importe: $this->importe);
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