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
use Log;
use DB;
use Carbon\Carbon;


class EgresosCapitulo2y3PagadoForm extends Component
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

    public $partidasPresupuestales = [];
    public $cuentasBanco = [];
    public $cuentasRetenciones = [];

    public function render()
    {
        try {
            $eventos = Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS EJERCIDO CAPITULO 2y3')
                ->where('estatus_evento', '=', true)
                ->distinct()
                ->pluck('descripcion', 'evento');

            return view('livewire.egresos.egresos-capitulo2y3-pagado-form', ['eventos' => $eventos]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar eventos en Pagado del capítulo 2 y 3: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}