<?php

namespace App\Livewire\egresos;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\CodigoDepartamento;
use App\Models\Poliza;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use Carbon\Carbon;
use Log;
use DB;

class EgresosCapitulo2y3ComprometidoForm extends Component
{
    public $consultarRegistro = false;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;

    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Fecha de afectaciónrequerida')]
    public $fechaAfectacion = "";

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    public $PTTOEjecutar = 0;

    public function render()
    {
        try{
            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
            ->orderBy('cuentas.Codigo_cuenta')->get();

            return view('livewire.egresos.egresos-capitulo2y3-comprometido-form', ['cuentas' => $cuentas]);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar cuentas en comprometido del capítulo 2 y 3: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }
    }

    public function agregarRegistro()
    {
        try{
            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();

        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en comprometido del capítulo 2 y 3: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}