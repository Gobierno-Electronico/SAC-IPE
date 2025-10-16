<?php

namespace App\Livewire;
use App\Models\Cuenta;
use Livewire\Component;
use Carbon\Carbon;
use App\Models\CodigoDepartamento;
use App\Models\Poliza;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Log;
use DB;

class PrestamosRecuperacionEjerciciosAnterioresForm extends Component
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

    #[Validate('required', message: 'Cuenta cargo requerida')]
    public $cuentaCargo = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    public $PTTOEjecutar = 0;
    public $consultarRegistro = false;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;

    public function render()
    {
        try {
            /* $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->whereIn('interaccion_cuenta_conceptos.concepto_id', [10099])
                ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Abono')
                ->orderBy('cuentas.Descripcion_cuenta')->get();

            $cuentasCargo = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->whereIn('interaccion_cuenta_conceptos.concepto_id', [10099])
                ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Cargo')
                ->orderBy('cuentas.Descripcion_cuenta')->get(); */
            $cuentas = [];
            $cuentasCargo = [];
            return view('livewire.prestamos-recuperacion-ejercicios-anteriores-form', ['cuentas' => $cuentas, 'cuentasCargo' => $cuentasCargo]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en recuperación préstamos ejercicios anteriores del capítulo 7000' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cargarPresupuesto() 
    {
        try {
           /*  if (!$this->cuenta || !$this->mes || !$this->selectCodigoAreaResponsable) return;
            $cuentaPresupuesto = Cuenta::where('id', $this->cuenta)->first();
            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('concepto_id', [10099])
                ->where('tipo_interaccion', '=', 'Contable - Abono')->first();

            $cuentaCompromisoDevengado = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', $interaccionCuentaConceptoPrincipal->id)
                ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->where('cuentas.Descripcion_cuenta', 'LIKE', '%'. '(Devengado)' .'%')
                ->get()
                ->toArray();                
            $solvencia = DB::select('EXEC SolvenciaRecuperacionRecaudadoPrestamosIniciales @cuenta = ?, @anio = ?, @mes = ?', array($cuentaPresupuesto['Codigo_cuenta'] , $anioActual, $this->mes))[0]->Total;
            $this->PTTOEjecutar = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputPTTOEjecutar', amount: "{$this->PTTOEjecutar}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar cargado', tipo: 'success', tiempo: 1500); */
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en recuperación préstamos ejercicios anteriores del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function agregarRegistro()
    {
        try {
          /*   $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";

            $this->validate();

            $cuenta = Cuenta::find($this->cuenta);
            $cuentaCargoSeleccionada = Cuenta::find($this->cuentaCargo);
            $areaResponsable = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $departamento = CodigoDepartamento::where('Codigo_completo', '1.5.04')->first();

            $registro = [
                'id' => 0,
                'codigoArea' => $departamento->Codigo_completo,
                'observaciones' => $this->observaciones,
                'fechaAfectacion' => $this->fechaAfectacion,
                'areaResponsableId' => $this->selectCodigoAreaResponsable,
                'codigoAreaResponsable' => $areaResponsable->Codigo_completo,
                'descripcionAreaResponsable' => $areaResponsable->Nombre,
                'cuentaId' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' => $cuenta->Descripcion_cuenta,
                'cuentaCargoId' => $this->cuentaCargo,
                'codigoCuentaCargo' => $cuentaCargoSeleccionada->Codigo_cuenta,
                'descripcionCuentaCargo' => $cuentaCargoSeleccionada->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'pttoEjecutar' => $this->PTTOEjecutar,
            ];

            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar(); */
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en recuperación préstamos ejercicios anteriores del capítulo 7000: ' . $th->getMessage());
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
        $this->cuentaCargo = "";
        $this->mes = "";
        $this->PTTOEjecutar = 0;
        $this->importe = "";
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->cuentaCargo = $datosRegistro['cuentaCargo'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->PTTOEjecutar = $datosRegistro['pttoEjecutar'];
        $this->dispatch('llenarFormulario', presupuesto: $this->PTTOEjecutar, importe: $this->importe);
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