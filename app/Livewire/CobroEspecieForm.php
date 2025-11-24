<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use App\Models\Cuenta;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use App\Models\Poliza;
use Carbon\Carbon;
use Log;
use DB;
use App\Enums\EstatusEvento;


class CobroEspecieForm extends Component
{
    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Fecha de afectación requerida')]
    public $fechaAfectacion = "";

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

    #[Validate('required', message: 'Documento fuente requerido')]
    public $documentoFuente = "";

    public $consultarRegistro = false;
    public $numeroPoliza;
    public $numeroPolizaRemanente;
    public $total;

    public function render()
    {
        try {
            //code...
            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->whereIn('interaccion_cuenta_conceptos.concepto_id', [33])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Abono')
                ->where('cuentas.Descripcion_cuenta', 'LIKE', '%(Recaudado)%')->orderBy('cuentas.Codigo_cuenta')->get();

            $eventos =  Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'I')
                ->where('categoria', '=', 'INGRESOS DEVENGADO')
                ->where('estatus_evento', '=', EstatusEvento::ACTIVO->value)
                ->distinct()
                ->pluck('descripcion', 'evento');

            return view('livewire.cobro-especie-form', ['cuentas' => $cuentas, 'eventos' => $eventos]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en cobro en especie: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cambioEvento()
    {
        try {
            $this->llenarCamposEspecificos();
            $this->montoDelEvento = DB::select('EXEC ImporteTotalCobroEspecie @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en cobro en especie: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
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
            Log::error('Ocurrió un error al llenar campos específicos en cobro especie: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }


    public function agregarRegistro()
    {
        try {
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
                'documentoFuente' => $this->documentoFuente
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en cobro en especie: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function limpiar()
    {
        $this->cuenta = "";
        $this->mes = "";
        $this->importe = "";
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->documentoFuente = $datosRegistro['documentoFuente'];
        $this->dispatch('llenarFormulario', cuenta: $datosRegistro['cuenta'], mes: $datosRegistro['mes'], importe: $datosRegistro['importe'], area: $datosRegistro['area']);
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
