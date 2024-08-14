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



class DevengadoPrevRecaudadoForm extends Component
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

    #[Validate('required', message:'Fecha requerida')]
    public $fechaRegistro = "";

    public $causaIva = 0;
    public $agregarIVA = "";

    public $consultarRegistro = false;
    public $numeroPoliza;
    public $numeroPolizaRemanente;
    public $total;

    public function render()
    {
        try {
            //code...
            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->where('interaccion_cuenta_conceptos.concepto_id', '=', 14)->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Abono')
                ->where('cuentas.Descripcion_cuenta', 'LIKE', '%(Devengado)%')->orderBy('cuentas.Codigo_cuenta')->get();
                $eventos = Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'I')
                ->where('categoria', '=', 'INGRESOS POR CLASIFICAR')
                ->distinct()
                ->pluck('descripcion', 'evento');
    
            $this->verificarCausaIVA();
            return view('livewire.devengado-prev-recaudado-form', ['eventos' => $eventos, 'cuentas' => $cuentas]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en Devengado previamente recaudado: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cambioEvento() {
        try {
            //code...
            $this->montoDelEvento = DB::select('EXEC ImporteTotalDevengadoPrevRecaudado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
    
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo : 'success', tiempo: 1500);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en devengado prv. recaudado: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }

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
            $cuenta = Cuenta::find($this->cuenta);
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $registro = [
                'id' => 0,
                'codigoArea' => $this->selectCodigoArea,
                'observaciones' => $this->observaciones,
                'evento' => $this->numeroEvento,
                'areaResponsableId' => $this->selectCodigoAreaResponsable,
                'codigoAreaResponsable' =>$departamento->Codigo_completo,
                'descripcionAreaResponsable' =>$departamento->Nombre,
                'cuentaId' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' =>$cuenta->Descripcion_cuenta,
                'mes' => $this->mes,
                'fechaRegistro' => $this->fechaRegistro,
                'importe' => $this->importe,
                'montoEvento' => $this->montoDelEvento,
                'iva' => $this->causaIva,
                'agregarIVA' => $this->agregarIVA,
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en Devengado previamente recaudado: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarCausaIVA() {
        try {
            //code...
            if(!$this->cuenta) return;
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('interaccion_cuenta_conceptos.concepto_id', [14])->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();
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
                }else{
                    $this->causaIva = 0;
                    $this->agregarIVA = "";
                    $this->dispatch('limpiarIVA');
                }
            }
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al calcular IVA en Devengado previamente recaudado: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al calcular IVA, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    #[On('reiniciar')]
    public function reiniciar() {
        $this->limpiar();
        $this->consultarRegistro = false;
        $this->numeroEvento = 0;
        $this->numeroPoliza = 0;
        $this->total = 0;
    }

    public function limpiar(){
        $this->cuenta = "";
        $this->mes = "";
        $this->importe = "";
        $this->causaIva = 0;
        $this->agregarIVA = "";
        $this->dispatch('limpiar');
        $this->dispatch('limpiarIVA');
    }


    public function finalizarRegistros(){
        $this->dispatch('finalizar-registros');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario ($datosRegistro) {
        try {
            //code...
            $this->cuenta = $datosRegistro['cuenta'];
            $this->mes = $datosRegistro['mes'];
            $this->importe = $datosRegistro['importe'];
            $this->selectCodigoAreaResponsable = $datosRegistro['area'];
            $this->agregarIVA = $datosRegistro['agregarIVA'];
            $this->verificarCausaIVA();
            $this->dispatch('llenarFormulario', cuenta: $datosRegistro['cuenta'], mes: $datosRegistro['mes'], importe: $datosRegistro['importe'], area: $datosRegistro['area'], agregarIVA: $datosRegistro['agregarIVA']);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al llenar formulario en Devengado previamente recaudado: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al llenar formulario, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total, $numeroPolizaRemanente) {
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
        $this->numeroPolizaRemanente = $numeroPolizaRemanente;
        $this->consultarRegistro = true;

    }

}
