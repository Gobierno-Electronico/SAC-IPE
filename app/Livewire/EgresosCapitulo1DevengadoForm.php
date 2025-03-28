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
use Livewire\EgresosCapitulo1DevengadoCargaForm;
use Log;
use DB;
class EgresosCapitulo1DevengadoForm extends Component
{
    public $consultarRegistro = false;
    public $numeroPoliza;
    public $numeroPolizaRemanente;
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
    #[Validate('required', message: 'Importe abono requerido')]
    public $importeAbono = "";
 
    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoDelEvento = "";
    
    public $PTTOComprometido = 0;
    public $cuentasContables = [];
    public $cambiarCuentaContableSeleccionada = true;
    public $partidasPresupuestales = [];
    public $cambiarPartidaPresupuestalSeleccionada = true;
    public $cambiarEventoSeleccionado = true;
    public $eventos = [];
    public $eventoAuxiliar = "";

    public function render() 
    {
        try{         
            $this->cambiarEventoSeleccionado = false;
            $this->cargarEventos();

            $this->cambiarPartidaPresupuestalSeleccionada = false;
            $this->llenarPartidasPresupuestales();

            $this->cambiarCuentaContableSeleccionada = false;
            $this->llenarCuentasContables(); 

            return view('livewire.egresos-capitulo1-devengado-form', ['eventos' => $this->eventos]);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar eventos en Devengado del capítulo 1: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }
    }

    public function cargarEventos(){
        if ($this->cambiarEventoSeleccionado) {
            $this->numeroEvento = "";
        }

        try{
            $this->cambiarEventoSeleccionado = true;
            $this->numeroEvento = $this->eventoAuxiliar;
            $this->eventos =  Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS COMPROMETIDO CAPITULO 1')
                ->where('estatus_evento', '=', true)
                ->distinct()
                ->pluck('descripcion', 'evento');


            $this->cambiarCuentaContableSeleccionada = false;
            $this->llenarCuentasContables();

        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar eventos en Devengado del capítulo 1: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }

    }

    public function llenarCuentasContables(){
       // sleep(3);
        if ($this->cambiarCuentaContableSeleccionada) {
            $this->cuentaContable = "";
        }
        
        try{
            $this->cambiarCuentaContableSeleccionada = true;

            $cuentasAbono = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [10102])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Abono')
            ->orderBy('cuentas.Codigo_cuenta')->get();

            $this->cuentasContables = $cuentasAbono;
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar las cuentas contables en devengado capítulo 1: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas contables, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cambioEvento(){
        try{
            sleep(1);
            $this->eventoAuxiliar = $this->numeroEvento;
            $this->limpiar();   
            $this->montoDelEvento = DB::select('EXEC ImporteTotalCapitulo1Devengado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);

            $this->llenarPartidasPresupuestales();
            $this->llenarCuentasContables();
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en Devengado del capítulo 1: ' . $th->getMessage());
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
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [10102])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
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
            Log::error('Ocurrió un error al cargar partidas presupuestales en Devengado del capítulo 1: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las partidas presupuestales, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cargarPresupuestoComprometido(){
        try{
            if (!$this->partidaPresupuestal || !$this->mes || !$this->selectCodigoAreaResponsable) return;
            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->partidaPresupuestal)->whereIn('interaccion_cuenta_conceptos.concepto_id', [10102])->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
            ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Comprometido)%')->first();

            
            $solvencia = DB::select('EXEC SolvenciaDevengadoCapitulo1 @area = ?, @cuenta = ?, @anio = ?, @mes = ?, @evento = ?', array($departamento->Codigo_completo, $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $this->mes, $this->numeroEvento))[0]->Total;
            $this->PTTOComprometido = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputPTTOComprometido', amount: "{$this->PTTOComprometido}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto comprometido cargado', tipo: 'success', tiempo: 1500);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en devengado del capítulo 1: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function agregarRegistro()
    {
        try{
            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";

            $this->importeAbono = floatval(str_replace(['$', ','], "", $this->importeAbono));
            $this->importeAbono = ($this->importeAbono > 0)  ? $this->importeAbono : "";
            $this->validate();

            if($this->importeAbono > $this->importe)
            {
                $this->dispatch('mostrarMensaje', mensaje: 'El importe abono no puede ser mayor al importe general', tipo: 'warning', tiempo: 3000);
                return;
            }   

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
                'cuentaId' => $this->partidaPresupuestal,
                'codigoCuenta' => $partida->Codigo_cuenta,
                'descripcionCuenta' => $partida->Descripcion_cuenta,
                'cuentaAbonoId' => $this->cuentaContable,
                'codigoCuentaAbono' => $cuentaContableSeleccionada->Codigo_cuenta,
                'descripcionCuentaAbono' => $cuentaContableSeleccionada->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'importeAbono' => $this->importeAbono,
                'montoEvento' => $this->montoDelEvento,
                'pttoComprometido' => $this->PTTOComprometido,
                'evento' => $this->numeroEvento
            ];

            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al registrar en devengado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function abrirVentanaCargaNomina()
    {
        $this->dispatch('mostrarCargando');
        return redirect()->route('capitulo1DevengadoCarga');
    }

    public function finalizarRegistros()
    {
        $this->dispatch('finalizar-registros');
    }

    public function limpiar()
    {
        $this->PTTOComprometido = "";
        $this->importeAbono = "";
        $this->mes = "";
        $this->cuentaContable = "";
        $this->cargarEventos();
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $this->partidaPresupuestal = $datosRegistro['partida']; 
        $this->cuentaContable = $datosRegistro['cuentaContable'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->importeAbono = $datosRegistro['importeAbono'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->PTTOComprometido = $datosRegistro['pttoComprometido'];
        $this->dispatch('llenarFormulario', presupuesto: $this->PTTOComprometido, importe: $this->importe, importeAbono: $this->importeAbono);
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