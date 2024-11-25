<?php

namespace App\Livewire\prestamos;

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

class PrestamosOtorgamientoCompromisoDevengadoPrestamosRenovacionForm extends Component
{
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
    public $consultarRegistro = false;
    public $numeroPoliza;
    public $total;
    
    public function render()
    {
        try{
            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [94])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Cargo')
            ->orderBy('cuentas.Descripcion_cuenta')->get();

            $cuentasAbono = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [94])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Abono')
            ->where('cuentas.Descripcion_cuenta', 'LIKE', '%Concesión%')
            ->orderBy('cuentas.Descripcion_cuenta')->get();

            $cuentas = $cuentas->merge($cuentasAbono);

            return view('livewire.prestamos.prestamos-otorgamiento-compromiso-devengado-prestamosRenovacion-form', ['cuentas' => $cuentas]);

        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar cuentas en compromiso-devengado préstamos inicales del capítulo 7000 ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }

    }

    public function agregarRegistro()
    {
        try{
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
                'areaResponsableId' => $this->selectCodigoAreaResponsable,
                'codigoAreaResponsable' => $departamento->Codigo_completo,
                'descripcionAreaResponsable' => $departamento->Nombre,
                'cuentaId' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' => $cuenta->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'pttoEjecutar' => $this->PTTOEjecutar
            ];

            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar(); 
        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en Otorgamiento (Comprometido-devengado) Prestamos Iniciales: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function finalizarRegistros()
    {
        $this->dispatch('finalizar-registros');
    }

    public function limpiar()
    {
        $this->cuenta = "";
        $this->selectCodigoAreaResponsable = "";
        $this->mes = "";
        $this->PTTOEjecutar = 0;
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
        $this->PTTOEjecutar = $datosRegistro['pttoEjecutar'];
        $this->dispatch('llenarFormulario', presupuesto: $this->PTTOEjecutar, importe: $this->importe);
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroPoliza, $total)
    {
        $this->consultarRegistro = true;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
    }
}