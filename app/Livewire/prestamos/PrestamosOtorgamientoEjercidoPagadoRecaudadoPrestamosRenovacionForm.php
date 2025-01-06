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

class PrestamosOtorgamientoEjercidoPagadoRecaudadoPrestamosRenovacionForm extends Component
{
    public $selectCodigoArea;

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Fecha de afectaciónrequerida')]
    public $fechaAfectacion = "";

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Cuenta Abono requerida')]
    public $cuentaAbono = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Importe abono requerido')]
    public $importeAbono = "";

    #[Validate('required', message: 'Presupuesto por ejecutar requerido')]
    public $PTTOEjecutar = 0;

    #[Validate('required', message:'Destino del recurso requerido')]
    public $destinoRecurso="";

    public $consultarRegistro = false;
    public $cambiarCuentaContableSeleccionada = true;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $cuentasAbono = [];

    public function render()
    {
        try{
            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                        ->whereIn('interaccion_cuenta_conceptos.concepto_id', [10097]) //..................CAMBIAR CONCEPTO
                        ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Cargo')
                        ->where('cuentas.Descripcion_cuenta', 'LIKE', '%'. 'Concesión' .'%')
                        ->orderBy('cuentas.Descripcion_cuenta')->get();

            $this->cuentasAbono = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [10097]) //..................CAMBIAR CONCEPTO
                            ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Abono')
                            ->where('cuentas.Descripcion_cuenta', 'NOT LIKE', '%'.'Recuperación'.'%')
                            ->orderBy('cuentas.Descripcion_cuenta')->get(); 
            $this->cambiarCuentaContableSeleccionada = false;

            return view('livewire.prestamos.prestamos-otorgamiento-ejercido-pagado-recaudado-prestamosRenovacion-form', ['cuentas' => $cuentas, 'cuentasAbono' => $this->cuentasAbono]);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar cuentas en ejercido-pagado-recaudado préstamos renovación del capítulo 7000 ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }

    }

    public function cargarPresupuesto()
    {
        try{
            if (!$this->cuenta || !$this->mes || !$this->selectCodigoAreaResponsable) return;
            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $cuentaSeleccionada = Cuenta::find($this->cuenta);

            $solvencia = DB::select('EXEC SolvenciaOtorgamientoEjercidoPagadoRecaudadoPrestamosRenovacion @area = ?, @cuenta = ?, @anio = ?, @mes = ?', array($departamento->Codigo_completo, $cuentaSeleccionada->Codigo_cuenta, $anioActual, $this->mes))[0]->Total;            
            $this->PTTOEjecutar = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputPTTOEjecutar', amount: "{$this->PTTOEjecutar}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar cargado', tipo: 'success', tiempo: 1500); 
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en ejercido-pagado-recaudado préstamos renovación del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function agregarRegistro()
    {
        try{
            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";

            $this->importeAbono = floatval(str_replace(['$', ','], "", $this->importeAbono));
            $this->importeAbono = ($this->importeAbono > 0)  ? $this->importeAbono : "";
            $this->validate();

            if($this->importeAbono >= $this->importe)
            {
                $this->dispatch('mostrarMensaje', mensaje: 'El importe abono no puede ser mayor o igual al importe cargo', tipo: 'warning', tiempo: 3000);
                return;
            }  
            
            $cuenta = Cuenta::find($this->cuenta);
            $cuentaAbono = Cuenta::find($this->cuentaAbono);
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
                'cuentaAbonoId' => $this->cuentaAbono,
                'codigoCuentaAbono' => $cuentaAbono->Codigo_cuenta,
                'descripcionCuentaAbono' => $cuentaAbono->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'importeAbono' => $this->importeAbono,
                'pttoEjecutar' => $this->PTTOEjecutar,
                'destinoRecurso' => $this->destinoRecurso
            ];

            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar(); 

        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en Otorgamiento ejercido-pagado-recaudado Prestamos Renovación del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function finalizarRegistros()
    {
        $this->dispatch('finalizar-registros');
    }

    public function limpiar()
    {
        $this->cuentaAbono = "";
        $this->mes = "";
        $this->PTTOEjecutar = 0;
        $this->importeAbono = "";
        $this->destinoRecurso = "";
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->cuentaAbono = $datosRegistro['cuentaAbono'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->importeAbono = $datosRegistro['importeAbono'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->PTTOEjecutar = $datosRegistro['pttoEjecutar'];
        $this->dispatch('llenarFormulario', presupuesto: $this->PTTOEjecutar, importe: $this->importe, importeAbono: $this->importeAbono);
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