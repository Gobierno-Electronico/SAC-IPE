<?php

namespace App\Livewire;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\CodigoDepartamento;
use App\Models\Poliza;
use Carbon\Carbon;
use DB;

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


    public $numeroPoliza;

    public function render()
    {
        $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [19,20,21,35,39])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Abono')
            ->orderBy('cuentas.Codigo_cuenta')->get();
        $eventos =  Poliza::select('evento')->whereYear('fecha', '=', Carbon::now()->year)->where('tipo_poliza', '=', 'I')
            ->where('categoria','=','INGRESOS DEVENGADO')->distinct()->pluck('evento');
        return view('livewire.ingresos-recaudado-form', ['eventos' => $eventos, 'cuentas' => $cuentas]);
    }

    public function cambioEvento() {
        // $this->montoDelEvento = DB::select('EXEC ImporteTotalRecaudado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
        // $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
        // $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo : 'success', tiempo: 1500);
    }

}
