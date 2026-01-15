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

class PrestamosOtorgamientoEjercidoPagadoRecaudadoPrestamosInicialesForm extends Component
{
    public $selectCodigoArea;
    public $observaciones = "";
    public $fechaAfectacion = "";
    public $selectCodigoAreaResponsable = "";
    public $cuenta = "";
    public $cuentaAbono = "";
    public $mes = "";
    public $importe = "";
    public $importeAbono = "";
    public $PTTOEjecutar = 0;
    public $documentoFuente = "";

    public $consultarRegistro = false;
    public $cambiarCuentaContableSeleccionada = true;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $cuentasAbono = [];
    public $mostrarAbono = false;

    protected function rules()
    {
        $cuentaSeleccionada = Cuenta::find($this->cuenta);

        $rules = [];

        if (!str_contains($cuentaSeleccionada, 'Concesión')) {
            $rules = [
                'observaciones' => 'required',
                'fechaAfectacion' => 'required|date',
                'selectCodigoAreaResponsable' => 'required',
                'cuenta' => 'required',
                'mes' => 'required',
                'importe' => 'required|numeric|min:0.01',
                'PTTOEjecutar' => 'required|numeric',
                'documentoFuente' => 'required',
            ];
        } else {
            $rules['cuentaAbono']  = 'required';
            $rules['importeAbono'] = 'required|numeric|min:0.01';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'observaciones.required' => 'Observaciones requeridas',
            'fechaAfectacion.required' => 'Fecha de afectación requerida',
            'selectCodigoAreaResponsable.required' => 'Área responsable requerida',
            'cuenta.required' => 'Cuenta requerida',
            'cuentaAbono.required' => 'Cuenta Abono requerida',
            'mes.required' => 'Mes requerido',
            'importe.required' => 'Importe requerido',
            'importeAbono.required' => 'Importe abono requerido',
            'PTTOEjecutar.required' => 'Presupuesto por ejecutar requerido',
            'documentoFuente.required' => 'Documento fuente requerido',
        ];
    }



    public function render()
    {
        try {
            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->whereIn('interaccion_cuenta_conceptos.concepto_id', [95])
                ->where('interaccion_cuenta_conceptos.tipo_interaccion', 'LIKE', '%Presupuestal%')
                ->where(function ($query) {
                    $query->where('cuentas.Descripcion_cuenta', 'LIKE', '%(Pagado)%')
                        ->orWhere('cuentas.Descripcion_cuenta', 'LIKE', '%(Recaudado)%');
                })
                ->orderBy('cuentas.Descripcion_cuenta')
                ->get();

            $this->cuentasAbono = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->whereIn('interaccion_cuenta_conceptos.concepto_id', [95])
                ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Abono')
                ->where('cuentas.Descripcion_cuenta', 'NOT LIKE', '%' . 'Recuperación' . '%')
                ->orderBy('cuentas.Codigo_cuenta')->get();
            $this->cambiarCuentaContableSeleccionada = false;

            return view(
                'livewire.prestamos-otorgamiento-ejercido-pagado-recaudado-prestamosIniciales-form',
                ['cuentas' => $cuentas, 'cuentasAbono' => $this->cuentasAbono]
            );
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en ejercido-pagado-recaudado préstamos inicales del capítulo 7000 ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function updatedCuenta($value)
    {
        $cuentaSeleccionada = Cuenta::find($value);

        if ($cuentaSeleccionada && str_contains($cuentaSeleccionada->Descripcion_cuenta, 'Concesión')) {
            $this->mostrarAbono = true;
        } else {
            $this->mostrarAbono = false;
            $this->cuentaAbono = "";
            $this->importeAbono = "";
        }
    }

    public function cargarPresupuesto()
    {
        try {
            if (!$this->cuenta || !$this->mes || !$this->selectCodigoAreaResponsable) return;
            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $cuentaSeleccionada = Cuenta::find($this->cuenta);
            $tipoPlazo = ((str_contains($cuentaSeleccionada->Descripcion_cuenta, 'Corto Plazo'))) ? "Corto Plazo" : "Mediano Plazo";
            $cuentaPresupuesto = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->whereIn('interaccion_cuenta_conceptos.concepto_id', [95])
                ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Cargo')
                ->where('cuentas.Descripcion_cuenta', 'LIKE', '%' . $tipoPlazo . '%')
                ->orderBy('cuentas.Descripcion_cuenta')
                ->first();

            $solvencia = DB::select('EXEC SolvenciaOtorgamientoEjercidoPagadoRecaudadoPrestamosIniciales @area = ?, @cuenta = ?, @anio = ?, @mes = ?', array($departamento->Codigo_completo, $cuentaPresupuesto->Codigo_cuenta, $anioActual, $this->mes))[0]->Total;


            $this->PTTOEjecutar = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputPTTOEjecutar', amount: "{$this->PTTOEjecutar}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar cargado', tipo: 'success', tiempo: 1500);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en ejercido-pagado-recaudado préstamos inicales del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function agregarRegistro()
    {
        try {
            $cuenta = Cuenta::find($this->cuenta);
            $areaResponsable = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $departamento = CodigoDepartamento::where('Codigo_completo', '1.5.04')->first();
            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();

            if (str_contains($cuenta->Descripcion_cuenta, 'Concesión')) {

                $cuentaAbono = Cuenta::find($this->cuentaAbono);
                $this->importeAbono = floatval(str_replace(['$', ','], "", $this->importeAbono));
                $this->importeAbono = ($this->importeAbono > 0)  ? $this->importeAbono : "";
                $this->validate();

                if ($this->importeAbono >= $this->importe) {
                    $this->dispatch('mostrarMensaje', mensaje: 'El importe abono no puede ser mayor o igual al importe general', tipo: 'warning', tiempo: 3000);
                    return;
                }

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
            } else {
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
                    'cuentaAbonoId' => "",
                    'codigoCuentaAbono' => "",
                    'descripcionCuentaAbono' => "",
                    'mes' => $this->mes,
                    'importe' => $this->importe,
                    'importeAbono' => 0,
                    'pttoEjecutar' => $this->PTTOEjecutar,
                    'documentoFuente' => $this->documentoFuente
                ];
            }

            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        } catch (\Throwable $th) {
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
        $this->cuentaAbono = "";
        $this->PTTOEjecutar = 0;
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
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total)
    {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
    }
}
