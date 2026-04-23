<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\CodigoDepartamento;
use Carbon\Carbon;
use Log;
use DB;



class IngresosDevengadoPrevRecaudadoEjerciciosAnterioresForm extends Component
{
    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoPorClasificar = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Fecha de afectación requerida')]
    public $fechaAfectacion = "";

    #[Validate('required', message: 'Cuenta de pago requerida')]
    public $cuentaPago = "";

    #[Validate('required', message: 'Solvencia abono requerido')]
    public $solvenciaAbono = "";

    #[Validate('required', message: 'Documento fuente requerido')]
    public $documentoFuente = "";

    public $subcuentas = [];

    public $cambiarCuentaPagoSeleccionada = true;

    public $causaIva = 0;
    public $agregarIVA = "";

    public $consultarRegistro = false;
    public $numeroPoliza;
    public $numeroEvento;
    public $numeroPolizaRemanente;
    public $total;
    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
        $this->fechaAfectacion = "{$this->anio}-01-01";
    }
    
    public function render()
    {
        try {
            $this->cambiarCuentaPagoSeleccionada = false;
            $this->llenarCuentasPago();
            // $this->verificarCausaIVA();

            $solvenciaPorClasificar = DB::select('EXEC SolvenciaIngresosPorClasificarGeneral')[0]->Total;
            $this->montoPorClasificar = ($solvenciaPorClasificar > 0) ? $solvenciaPorClasificar : 0;

            return view('livewire.ingresos-devengado-prev-recaudado-ejercicios-anteriores-form', ['montoPorClasificar' => $this->montoPorClasificar]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en Devengado previamente recaudado ejercicios anteriores: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function obtenerSolvencia()
    {
        try{
            $anioActual = (string) $this->anio;
            $cuentaAbono = Cuenta::find($this->cuentaPago);
            $solvencia = DB::select('EXEC SolvenciaCuentasContables @cuenta = ?, @anio = ?', array($cuentaAbono->Codigo_cuenta, $anioActual))[0]->Solvencia;
            $this->solvenciaAbono = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputSolvenciaAbono', amount:"{$this->solvenciaAbono}");
            $this->dispatch('mostrarMensaje', mensaje: 'Solvencia cargada', tipo: 'success', tiempo: 1500);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al obtener solvencia en Devengado previamente recaudado ejercicios anteriores: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al obtener solvencia, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCuentasPago()
    {
        try {

            if ($this->cambiarCuentaPagoSeleccionada) {
                $this->cuentaPago = "";
            }
            $this->cambiarCuentaPagoSeleccionada = true;
            $this->subcuentas = Cuenta::where('nivel', 6)
                ->where(function ($query) {
                    $query->where('Codigo_cuenta', 'like', '1.%')
                        ->orWhere('Codigo_cuenta', 'like', '2.%')
                        ->orWhere('Codigo_cuenta', 'like', '3.%');
                })
                ->orderBy('Codigo_cuenta')
                ->get();
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar las cuentas de pago en devengado previamente recaudado ejercicios anteriores: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas de pago, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function agregarRegistro()
    {
        try {
            if ($this->causaIva > 0) {
                if ($this->agregarIVA != "") {
                    if ($this->agregarIVA == 'NO') {
                        $this->causaIva = 0;
                    }
                } else {
                    $this->dispatch('mostrarMensaje', mensaje: 'Selección agregar IVA requerido', tipo: 'warning', tiempo: 3000);
                    return;
                }
            }

            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->causaIva = floatval(str_replace(['$', ','], "", $this->causaIva));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();
            $cuentaPagoSeleccionada = Cuenta::find($this->cuentaPago);
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $registro = [
                'id' => 0,
                'codigoArea' => $this->selectCodigoArea,
                'observaciones' => $this->observaciones,
                'areaResponsableId' => $this->selectCodigoAreaResponsable,
                'codigoAreaResponsable' => $departamento->Codigo_completo,
                'descripcionAreaResponsable' => $departamento->Nombre,
                'cuentaPagoId' => $this->cuentaPago,
                'codigoCuentaPago' => $cuentaPagoSeleccionada->Codigo_cuenta,
                'descripcionCuentaPago' => $cuentaPagoSeleccionada->Descripcion_cuenta,
                'mes' => $this->mes,
                'fechaAfectacion' => $this->fechaAfectacion,
                'importe' => $this->importe,
                'montoPorClasificar' => $this->montoPorClasificar,
                'solvenciaAbono' => $this->solvenciaAbono,
                'documentoFuente' => $this->documentoFuente
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en Devengado previamente recaudado ejercicios anteriores: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    #[On('reiniciar')]
    public function reiniciar()
    {
        $this->limpiar();
        $this->consultarRegistro = false;
        $this->numeroEvento = 0;
        $this->numeroPoliza = 0;
        $this->total = 0;
    }

    public function limpiar()
    {
        $this->mes = "";
        $this->importe = "";
        $this->causaIva = 0;
        $this->cuentaPago = "";
        $this->agregarIVA = "";
        $this->solvenciaAbono = "";
        $this->dispatch('limpiar');
        $this->dispatch('limpiarIVA');
    }


    public function finalizarRegistros()
    {
        $this->dispatch('finalizar-registros');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        try {
            $this->mes = $datosRegistro['mes'];
            $this->importe = $datosRegistro['importe'];
            $this->selectCodigoAreaResponsable = $datosRegistro['area'];
            $this->cuentaPago = $datosRegistro['cuentaPago'];
            $this->solvenciaAbono = $datosRegistro['solvenciaAbono'];
            $this->documentoFuente = $datosRegistro['documentoFuente'];
            $this->dispatch('llenarFormulario', cuentapAGO: $datosRegistro['cuentaPago'], mes: $datosRegistro['mes'], importe: $datosRegistro['importe'], area: $datosRegistro['area'], solvenciaAbono: $datosRegistro['solvenciaAbono']);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al llenar formulario en Devengado previamente recaudado ejercicios anteriores: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al llenar formulario, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total, $numeroPolizaRemanente)
    {
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
        $this->numeroPolizaRemanente = $numeroPolizaRemanente;
        $this->consultarRegistro = true;
    }
}
