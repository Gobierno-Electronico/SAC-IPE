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
            $this->limpiar();   
            $this->montoDelEvento = DB::select('EXEC ImporteTotalCapitulo4Devengado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);

            $this->llenarPartidasPresupuestales();
            $this->cuentaContable = "";

            $this->cambiarCuentaContableSeleccionada = false;
            $this->llenarCuentasContables();


        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en Devengado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
@@ -100,33 +100,34 @@ public function cambioEvento(){

    public function llenarPartidasPresupuestales(){
        try{
            if ($this->cambiarPartidaPresupuestalSeleccionada) {
                $this->partidaPresupuestal = "";
            }
                    if ($this->cambiarPartidaPresupuestalSeleccionada) {
                        $this->partidaPresupuestal = "";
                    }

            $this->cambiarPartidaPresupuestalSeleccionada = true;
                    $this->cambiarPartidaPresupuestalSeleccionada = true;

            $cuentasComprometidas = Poliza::where('evento', '=', $this->numeroEvento)
            ->where('tipo_poliza', '=', 'E')
            ->where('concepto', 'LIKE', '%Comprometido%')
            ->get();

            $cuentasDevengadas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [63, 64, 56, 58])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
            ->orderBy('cuentas.Codigo_cuenta')->get();
                    $cuentasComprometidas = Poliza::where('evento', '=', $this->numeroEvento)
                    ->where('tipo_poliza', '=', 'E')
                    ->where('concepto', 'LIKE', '%Comprometido%')
                    ->get();

                    $cuentasDevengadas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                    ->whereIn('interaccion_cuenta_conceptos.concepto_id', [63, 64, 56, 58])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
                    ->orderBy('cuentas.Codigo_cuenta')->get();

            $cuentasDevengadasAux = new Collection();
            foreach($cuentasDevengadas as $devengada){
                foreach($cuentasComprometidas as $comprometida){
                    $conceptoComprometida = explode('(', $comprometida->concepto);
                    if(str_contains($devengada->Descripcion_cuenta, $conceptoComprometida[0])){
                        $cuentasDevengadasAux->push($devengada);
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
                }
            }

            $cuentasDevengadasAux = $cuentasDevengadasAux->unique('Codigo_cuenta');
            $this->partidasPresupuestales = $cuentasDevengadasAux;
                    $this->partidasPresupuestales = $cuentasDevengadasAux;
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en Devengado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
@@ -185,73 +186,15 @@ public function cargarPresupuestoComprometido(){
    public function agregarRegistro()
    {
        try{
            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();

            $partida = Cuenta::find($this->partidaPresupuestal);
            $cuentaContableSeleccionada = Cuenta::find($this->cuentaContable);
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
                'cuentaContableId' => $this->cuentaContable,
                'codigoCuentaContable' => $cuentaContableSeleccionada->Codigo_cuenta,
                'descripcionCuentaContable' => $cuentaContableSeleccionada->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'montoEvento' => $this->montoDelEvento,
                'pttoComprometido' => $this->PTTOComprometido
            ];

            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }
    }

    public function finalizarRegistros()
    public function finalizarRegistro()
    {
        $this->dispatch('finalizar-registros');

    }

    public function limpiar()
    {
        $this->PTTOComprometido = "";
        $this->importe = "";
        $this->mes = "";
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $this->partidaPresupuestal = $datosRegistro['partida'];
        $this->cuentaContable = $datosRegistro['cuentaContable'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->PTTOComprometido = $datosRegistro['pttoComprometido'];
        $this->dispatch('llenarFormulario', presupuesto: $this->PTTOComprometido, importe: $this->importe);
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total)
    {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
    }
}