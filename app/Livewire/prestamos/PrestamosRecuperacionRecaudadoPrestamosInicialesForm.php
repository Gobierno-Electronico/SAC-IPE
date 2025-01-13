<?php

namespace App\Livewire\prestamos;
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


class PrestamosRecuperacionRecaudadoPrestamosInicialesForm extends Component
{
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

    #[Validate('required', message: 'Cuenta de banco requerida')]
    public $cuentaBanco = "";

    public $PTTOEjecutar = 0;
    public $consultarRegistro = false;
    public $cambiarCuentaContableSeleccionada = true;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $cuentasAbono = [];
    public function render()
    {
        try{
            /* $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                        ->whereIn('interaccion_cuenta_conceptos.concepto_id', [10096])
                        ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Abono')
                        ->orderBy('cuentas.Descripcion_cuenta')->get();

            $this->cuenta = $cuentas->first()->cuenta_id; */

            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [10096])
            ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Abono')
            ->orderBy('cuentas.Descripcion_cuenta')->get();
  
            $bancos = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                        ->whereIn('interaccion_cuenta_conceptos.concepto_id', [10096])
                        ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Cargo')
                        ->orderBy('cuentas.Descripcion_cuenta')->get();
            
            return view('livewire.prestamos.prestamos-recuperacion-recaudado-prestamosIniciales-form', ['cuentas' => $cuentas, 'bancos' => $bancos]);

        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar cuentas en recaudado préstamos inicales del capítulo 7000 ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }
    }


    public function cargarPresupuesto()
    {
        try{      
            if (!$this->cuenta || !$this->mes || !$this->selectCodigoAreaResponsable) return;
            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $solvencia = DB::select('EXEC SolvenciaRecuperacionRecaudadoPrestamosRenovacion @area = ?, @cuenta = ?, @anio = ?, @mes = ?', array($departamento->Codigo_completo, '8.1.4.4.1.7.1.02.01', $anioActual, $this->mes))[0]->Total;
            $this->PTTOEjecutar = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputPTTOEjecutar', amount: "{$this->PTTOEjecutar}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar cargado', tipo: 'success', tiempo: 1500);  
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en recaudado préstamos inicales del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
    
    public function agregarRegistro()
    {
        try{
            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";

            $this->validate();



            $cuenta = Cuenta::find($this->cuenta);
            $cuentaBanco = Cuenta::find($this->cuentaBanco);
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
                'cuentaBancoId' => $this->cuentaBanco,
                'codigoCuentaBanco' => $cuentaBanco->Codigo_cuenta,
                'descripcionCuentaBanco' => $cuentaBanco->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'pttoEjecutar' => $this->PTTOEjecutar,
            ];

            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar(); 
        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en Otorgamiento ejercido-pagado-recaudado Prestamos Iniciales: ' . $th->getMessage());
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
        $this->cuentaBanco = "";
        $this->mes = "";
        $this->PTTOEjecutar = 0;
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->cuentaBanco = $datosRegistro['cuentaBanco'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->PTTOEjecutar = $datosRegistro['pttoEjecutar'];
        $this->dispatch('llenarFormulario', presupuesto: $this->PTTOEjecutar, importe: $this->importe);
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento,$numeroPoliza, $total)
    {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
         $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
    }



}