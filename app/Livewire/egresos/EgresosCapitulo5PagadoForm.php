<?php

namespace App\Livewire\egresos;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Log;

class EgresosCapitulo5PagadoForm extends Component
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

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Partida presupuestal requerida')]
    public $partidaPresupuestal = "";

    #[Validate('required', message: 'Método de pago requerido')]
    public $cuentaBanco = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoDelEvento = "";

    #[Validate('required', message: 'Selector de pago de retenciones requerido')]
    public $selectorPagoRetenciones = "";

    #[Validate('required', message: 'Cuenta de retenciones requerida')]
    public $cuentaDeRetenciones = "";

    public $cuentasRetenciones = [];
    public $partidasPresupuestales = [];
    public $cuentasBanco = [];

    public $cambiarPartidaPresupuestalSeleccionada = false;
    public $cambiarCuentaBancoSeleccionada = false;
    public $cambiarCuentaRetencionesSeleccionada = false;

    public function render()
    {
        try {
            $eventos = Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS EJERCIDO CAPITULO 5')
                ->where('estatus_evento', '=', true)
                ->distinct()
                ->pluck('descripcion', 'evento');

            $this->cambiarPartidaPresupuestalSeleccionada = false;
            $this->llenarPartidasPresupuestales();
            $this->cambiarCuentaBancoSeleccionada = false;
            $this->llenarCuentasBanco();
            $this->cambiarCuentaRetencionesSeleccionada = false;

            return view('livewire.egresos.egresos-capitulo5-pagado-form', ['eventos' => $eventos]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar eventos en Pagado del capítulo 5000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cambioEvento()
    {
        try {
            $this->limpiar();
            $this->montoDelEvento = DB::select('EXEC ImporteTotalCapitulo5Pagado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
            $this->llenarPartidasPresupuestales();
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en Pagado del capítulo 5000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarPartidasPresupuestales()
    {
        try {
            if ($this->cambiarPartidaPresupuestalSeleccionada) {
                $this->partidaPresupuestal = "";
            }

            $this->cambiarPartidaPresupuestalSeleccionada = true;

            $cuentasEjercidas = Poliza::where('evento', '=', $this->numeroEvento)
                ->where('tipo_poliza', '=', 'E')
                ->where('concepto', 'LIKE', '%Ejercido%')
                ->get();

            $cuentasPagadas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->whereIn('interaccion_cuenta_conceptos.concepto_id', [80, 83, 84])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
                ->orderBy('cuentas.Codigo_cuenta')->get();

            $cuentasDevengadasAux = new Collection();
            foreach ($cuentasPagadas as $pagada) {
                foreach ($cuentasEjercidas as $ejercida) {
                    $conceptoComprometida = explode('(', $ejercida->concepto);
                    if (str_contains($pagada->Descripcion_cuenta, $conceptoComprometida[0])) {
                        $cuentasDevengadasAux->push($pagada);
                    }
                }
            }

            $cuentasDevengadasAux = $cuentasDevengadasAux->unique('Codigo_cuenta');
            $this->partidasPresupuestales = $cuentasDevengadasAux;
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al llenar partidas presupuestales en pagado del capítulo 5000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCuentasBanco()
    {
        if (!$this->partidaPresupuestal) return;

        if ($this->cambiarCuentaBancoSeleccionada) {
            $this->cuentaBanco = "";
            $this->cargarPresupuestoEjercido();
        }

        try {
            $this->cambiarCuentaBancoSeleccionada = true;
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->partidaPresupuestal)->whereIn('interaccion_cuenta_conceptos.concepto_id', [80, 83, 84])
                ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $this->cuentasBanco = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
                ->join('interaccion_cuenta_conceptos', function ($join) {
                    $join->on('interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                        ->where('tipo_interaccion', '=', 'Contable - Abono');
                })
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get();

            $this->llenarCuentasRetenciones($interaccionCuentaConcepto->id);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar las cuentas de banco en pagado capítulo 5000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas de banco, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function agregarRegistro()
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }
    }

    public function finalizarRegistro() {}
}
