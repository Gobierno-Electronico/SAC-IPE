<?php

namespace App\Livewire;

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

class PrestamosOtorgamientoCompromisoDevengadoPrestamosInicialesForm extends Component
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

    #[Validate('required', message: 'Documento fuente requerido')]
    public $documentoFuente = "";

    public $consultarRegistro = false;
    public $cambiarCuentaContableSeleccionada = true;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $cuentasAbono = [];
    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }   
    
    public function render()
    {
        try{
            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [94])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Cargo')
            ->orderBy('cuentas.Descripcion_cuenta')->get();

            $this->cambiarCuentaContableSeleccionada = false;
            $this->cargarCuentaContableAbono(false);
            
            return view('livewire.prestamos-otorgamiento-compromiso-devengado-prestamosIniciales-form', ['cuentas' => $cuentas/* , 'cuentasAbono' => $cuentasAbono */]);

        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar cuentas en compromiso-devengado préstamos inicales del capítulo 7000 ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }

    }

    public function cargarPresupuesto()
    {
        try{
            if (!$this->cuenta || !$this->mes || !$this->selectCodigoAreaResponsable) return;


            $cuentaSeleccionada = Cuenta::find($this->cuenta);
            $cuentaContableAbono = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuentaAbono)->whereIn('interaccion_cuenta_conceptos.concepto_id', [94])->where('tipo_interaccion', '=', 'Contable - Abono')->first();
            

            $cuentaContableAbono = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [94])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Abono')
            ->where('cuentas.Descripcion_cuenta', 'LIKE', '%' . $cuentaSeleccionada->descripcionCuenta . '%')
            ->get(); 

            
            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $cuentaContableAbono[0]->cuenta_id)->whereIn('interaccion_cuenta_conceptos.concepto_id', [94])->where('tipo_interaccion', '=', 'Contable - Abono')->first();
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
            ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Por ejercer)%')->first();

            $solvencia = DB::select('EXEC SolvenciaCuentasPorEjercer @area = ?, @cuenta = ?, @anio = ?, @mes = ?', array($departamento->Codigo_completo, $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $this->mes))[0]->Solvencia;
            $this->PTTOEjecutar = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputPTTOEjecutar', amount: "{$this->PTTOEjecutar}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar cargado', tipo: 'success', tiempo: 1500); 
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en compromiso-devengado préstamos inicales del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }


    public function cargarCuentaContableAbono($bandera)
    {
        if($bandera)
        {
            $this->cargarPresupuesto();
        }
        if(!$this->cuenta) return;

        if ($this->cambiarCuentaContableSeleccionada) {
            $this->cuentaAbono = "";
            return;
        }
        $this->cambiarCuentaContableSeleccionada = true;
        try{

            $cuentaSeleccionada = Cuenta::find($this->cuenta);
            $plazo = explode(')', explode('(', $cuentaSeleccionada->Descripcion_cuenta)[1])[0];

            $this->cuentasAbono = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [94])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Abono')
            ->where('cuentas.Descripcion_cuenta', 'LIKE', '%' . $plazo . '%')
            ->get(); 
        
            $this->cuentaAbono = $this->cuentasAbono[0]->cuenta_id;

        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuenta contable abono en compromiso-devengado préstamos inicales del capítulo 7000: ' . $th->getMessage());
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
                'documentoFuente' => $this->documentoFuente
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
        $this->cuentaAbono = "";
        $this->mes = "";
        $this->PTTOEjecutar = 0;
        $this->importe = "";
        $this->importeAbono = "";
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
        $this->documentoFuente = $datosRegistro['documentoFuente'];
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