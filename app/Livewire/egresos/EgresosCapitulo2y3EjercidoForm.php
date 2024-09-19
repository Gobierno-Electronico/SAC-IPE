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

class EgresosCapitulo2y3EjercidoForm extends Component
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

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoDelEvento = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe;

    public $cambiarCuentaSeleccionada = true;
    public $partidasPresupuestales = [];
    public $PTTODevengado = 0;

    public function render() 
    {
        try{
            $eventos =  Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 2 y 3')
                ->where('estatus_evento', '=', true)
                ->distinct()
                ->pluck('descripcion', 'evento');
                
            return view('livewire.egresos.egresos-capitulo2y3-ejercido-form', ['eventos' => $eventos]);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar cuentas en ejercido del capítulo 2 y 3: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }
    }
}