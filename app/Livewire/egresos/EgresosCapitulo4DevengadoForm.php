<?php

namespace App\Livewire\egresos;

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

class EgresosCapitulo4DevengadoForm extends Component
{
    public $consultarRegistro = false;
    public $numeroPoliza;
    public $total;

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

    #[Validate('required', message: 'Cuenta contable requerida')]
    public $cuentaContable = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";
 
    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoDelEvento = "";
    
    public $PTTOComprometido = 0;

    public $cuentasContables = [];
    public $cambiarCuentaContableSeleccionada = true;

    public $partidasPresupuestales = [];
    public $cambiarPartidaPresupuestalSeleccionada = true;

    public function render()
    {
        try {
            $eventos =  Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 4')
                ->where('estatus_evento', '=', true)
                ->distinct()
                ->pluck('descripcion', 'evento');

            $this->cambiarPartidaPresupuestalSeleccionada = false;
            $this->llenarPartidasPresupuestales();

            $this->cambiarCuentaContableSeleccionada = false;
            $this->llenarCuentasContables();

            return view('livewire.egresos.egresos-capitulo4-devengado-form', ['eventos' => $eventos]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar eventos en Devengado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cambioEvento(){
        try{

            $this->llenarPartidasPresupuestales();

            $this->cambiarCuentaContableSeleccionada = false;
            $this->llenarCuentasContables();
            

        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en Devengado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarPartidasPresupuestales(){
        try{
                    if ($this->cambiarPartidaPresupuestalSeleccionada) {
                        $this->partidaPresupuestal = "";
                    }
                    
                    $this->cambiarPartidaPresupuestalSeleccionada = true;
                    
                    $cuentasComprometidas = Poliza::where('evento', '=', $this->numeroEvento)
                    ->where('tipo_poliza', '=', 'E')
                    ->where('concepto', 'LIKE', '%Comprometido%')
                    ->get();

                    $cuentasDevengadas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                    ->whereIn('interaccion_cuenta_conceptos.concepto_id', [63, 64, 56, 58])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
                    ->orderBy('cuentas.Codigo_cuenta')->get();
           
                    // Log::info($cuentasComprometidas);
                    // Log::info('______________________________________________________________________________');
                    // Log::info($cuentasDevengadas);
                    $cuentasDevengadasAux = new Collection();
                    foreach($cuentasDevengadas as $devengada){
                        foreach($cuentasComprometidas as $comprometida){
                             $conceptoComprometida = explode('(', $comprometida->concepto);
                             if(str_contains($devengada->Descripcion_cuenta, $conceptoComprometida[0])){
                                 $cuentasDevengadasAux->push($devengada);
                             }
                        }
                    }
                    $this->partidasPresupuestales = $cuentasDevengadasAux;
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en Devengado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCuentasContables()
    {
        if (!$this->partidaPresupuestal) return;

        if ($this->cambiarCuentaContableSeleccionada) {
            $this->cuentaContable = "";
            $this->cargarPresupuestoComprometido();
        }

        try {
            $this->cambiarCuentaContableSeleccionada = true;

            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->partidaPresupuestal)->whereIn('interaccion_cuenta_conceptos.concepto_id', [63, 64, 56, 58])

                ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $this->cuentasContables = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
                ->join('interaccion_cuenta_conceptos', function ($join) {
                    $join->on('interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                        ->where('tipo_interaccion', '=', 'Contable - Abono');
                })
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get();
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar las cuentas contables en devengado capítulo 4000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas contables, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cargarPresupuestoComprometido(){
        try{
            if (!$this->partidaPresupuestal || !$this->mes || !$this->selectCodigoAreaResponsable) return;

            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->partidaPresupuestal)->whereIn('interaccion_cuenta_conceptos.concepto_id', [63, 64, 56, 58])->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
            ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Comprometido)%')->first();

            
            $solvencia = DB::select('EXEC SolvenciaComprometidosCapitulo4 @area = ?, @cuenta = ?, @anio = ?, @mes = ?, @evento = ?', array($departamento->Codigo_completo, $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $this->mes, $this->numeroEvento))[0]->Total;
            $this->PTTOComprometido = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputPTTOComprometido', amount: "{$this->PTTOComprometido}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto comprometido cargado', tipo: 'success', tiempo: 1500);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en devengado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function agregarRegistro()
    {
        try{
            $this->validate();
        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }
    }

    public function finalizarRegistro()
    {
        
    }

}