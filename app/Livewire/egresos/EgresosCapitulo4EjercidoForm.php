<?php

namespace App\Livewire\egresos;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use Log;
use Illuminate\Support\Collection;

use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use App\Models\Poliza;
use Carbon\Carbon;
use DB;

class EgresosCapitulo4EjercidoForm extends Component
{
    public $consultarRegistro = false;

    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Fecha de afectación requerida')]
    public $fechaAfectacion = "";

    #[Validate('required', message: 'Evento requerido')]
    public $numeroEvento = "";

    #[Validate('required', message: 'Partida presupuestal requerida')]
    public $partidaPresupuestal = "";

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

    public $cuentasContables = [];
    public $cambiarCuentaContableSeleccionada = true;

    public $partidasPresupuestales = [];
    public $subcuentas = [];
    public $cambiarPartidaPresupuestalSeleccionada = true;

    public function render() 
    {
        try{
            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [59, 60, 61, 62])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
            ->orderBy('cuentas.Codigo_cuenta')->get();

            $eventos =  Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 4')
                ->where('estatus_evento', '=', true)
                ->distinct()
                ->pluck('descripcion', 'evento');

            return view('livewire.egresos.egresos-capitulo4-ejercido-form', ['cuentas' => $cuentas], ['eventos' => $eventos]);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar cuentas en ejercido: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }
    }

    public function cambioEvento(){
        try {
            //code...
            $this->llenarCuentasPago();
            $this->montoDelEvento = DB::select('EXEC ImporteTotalCapitulo4Ejercido @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCuentasPago()
    {
        try {
            if ($this->cambiarPartidaPresupuestalSeleccionada) {
                $this->partidaPresupuestal = "";
            }

            $this->cambiarPartidaPresupuestalSeleccionada = true;

            $cuentasDevengadas = Poliza::where('evento', '=', $this->numeroEvento)
                ->where('tipo_poliza', '=', 'E')
                ->where('concepto', 'LIKE', '%Devengado%')
                ->get();

            $cuentasEjercidas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->whereIn('interaccion_cuenta_conceptos.concepto_id', [59, 60, 61, 62])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
                ->orderBy('cuentas.Codigo_cuenta')->get();

            $cuentasEjercidasAux = new Collection();
            foreach ($cuentasEjercidas as $ejercida) {
                foreach ($cuentasDevengadas as $comprometida) {
                    $conceptoComprometida = explode('(', $comprometida->concepto);
                    

                    if (str_contains($ejercida->Descripcion_cuenta, $conceptoComprometida[0])) {
                        $cuentasEjercidasAux->push($ejercida);
                    }
                }
            }

            $this->partidasPresupuestales = $cuentasEjercidasAux;
            
            
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en Ejercido del capítulo 4000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
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