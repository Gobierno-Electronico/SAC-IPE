<?php

namespace App\Livewire;

use App\Models\ClasificacionAdministrativa;
use App\Models\ClasificacionProgramatica;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\ClasificadorRubroIngreso;
use App\Models\ClasificadorFuenteFinanciamiento;
use App\Models\ClasificadorFuncionalGasto;
use App\Http\Controllers\BitacoraController;
use App\Models\ClasificadorObjetoGasto;
use App\Models\ClasificadorTipoGasto;
use App\Models\Cuenta;
use App\Models\CuentaClasificadorEgreso;
use App\Models\InteraccionCuentaConcepto;
use App\Models\InteraccionCuentaCuenta;
use App\Models\Poliza;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PhpParser\Node\Stmt\Break_;

class AfectacionesIngresosForm extends Component
{
    public $tipo;
    public $selectDescripcionDepartamento = '';
    public $selectCodigoDepartamento = '';
    public $selectDescripcionRI = '';
    public $selectCodigoRI = '';
    public $selectDescripcionFF = '';
    public $selectCodigoFF = '';
    public $descripcionCuentaCargo = '';
    public $codigoCuentaCargo = '';
    public $descripcionCuentaAbono = '';
    public $codigoCuentaAbono = '';

    public $descripcionCuentaCargoEgreso = '';
    public $codigoCuentaCargoEgreso = '';
    public $descripcionCuentaAbonoEgreso = '';
    public $codigoCuentaAbonoEgreso = '';

    public $observaciones = "";
    public $documentoFuente = "";
    public $consulta = false;
    public $registros = [];
    public $selectCodigoOG = '';
    public $selectDescripcionOG = '';
    public $codigoClasificadorAdministrativo = '';
    public $descripcionClasificadorAdministrativo = '';
    public $codigoClasificadorFuncional = '';
    public $descripcionClasificadorFuncional = '';

    public $codigoClasificadorProgramatica = '';
    public $descripcionClasificadorProgramatica = '';

    public $codigoClasificadorTipoGasto = '';
    public $descripcionClasificadorTipoGasto = '';

    public $numeroEvento = 0;
    public $numeroPoliza;
    public $total = 0;
    public $totalPrevio = 0;
    public $estado;
    public $estadoOriginal;

    public function render()
    {
        return view('livewire.afectaciones-ingresos-form');
    }

    public function save()
    {

        dd($this->only(['title', 'content', 'tipo']));
    }


    public function change($element = "")
    {
        $this->dispatch('clean');
        if ($element == "") return;
        $tipo = null;
        $clasificadorRI = null;
        $clasificadorFF = null;
        $clasificadorOG = null;
        $clasificadorA = null;
        $clasificadoresEgreso = null;
        $clasificadorF = null;
        $clasificadorP = null;
        $clasificadorTG = null;
        switch ($element) {
            case 'codigo':
                $tipo = 'codigo';
                $this->dispatch('actualizar-select', tipo: $tipo, id: 0);
                return;
            case 'descripcion':
                $tipo = 'descripcion';
                $this->dispatch('actualizar-select', tipo: $tipo, id: 0);
                return;
            case 'descripcion_RI':
                $tipo = "descripcion_RI";
            case 'codigo_RI':
                $tipo = ($tipo) ? $tipo : 'codigo_RI';
                $clasificadorRI = ClasificadorRubroIngreso::find($this->selectCodigoRI);
                $clasificadorFF = ClasificadorFuenteFinanciamiento::where("Cuenta_contable", "=", $clasificadorRI->Cuenta_contable)->first();
                $this->dispatch('actualizar-select', tipo: $tipo, id: $clasificadorFF->id);
                break;
            case 'descripcion_FF':
                $tipo = "descripcion_FF";
            case 'codigo_FF':
                $tipo = ($tipo) ? $tipo : 'codigo_FF';
                $clasificadorFF = ClasificadorFuenteFinanciamiento::find($this->selectCodigoFF);
                $clasificadorRI = ClasificadorRubroIngreso::where("Cuenta_contable", "=", $clasificadorFF->Cuenta_contable)->first();
                $this->dispatch('actualizar-select', tipo: $tipo, id: $clasificadorRI->id);
                break;
            case 'descripcion_OG':
                $tipo = 'descripcion_OG';
            case 'codigo_OG':
                $tipo = ($tipo) ? $tipo : 'codigo_OG';
                $clasificadorOG = ClasificadorObjetoGasto::where('codigo', '=', $this->selectCodigoOG)->first();
                $clasificadoresEgreso = CuentaClasificadorEgreso::where('COG', '=', $this->selectCodigoOG)->first();
                $clasificadorA = ClasificacionAdministrativa::where('codigo', '=', $clasificadoresEgreso->CA)->first();
                $clasificadorF = ClasificadorFuncionalGasto::where('codigo', '=', $clasificadoresEgreso->CFG)->first();
                $clasificadorP = ClasificacionProgramatica::where('codigo', '=', $clasificadoresEgreso->CP)->first();
                $clasificadorTG = ClasificadorTipoGasto::where('codigo', '=', $clasificadoresEgreso->CTG)->first();
                $this->dispatch('actualizar-select', tipo: $tipo, id: $clasificadorOG->codigo);
                break;

            default:
                break;
        }
        if ($this->estado == 'INGRESOS') {
            $cuentas = ClasificadorRubroIngreso::where('Codificacion_rubro_ingreso', '=', $clasificadorRI->Codificacion_rubro_ingreso)->where('Cuenta_contable', '>', '5')
                ->where(function (Builder $query) {
                    $query->where('Nombre', 'like', '%Por ejecutar%')
                        ->orWhere('Nombre', 'like', '%Modificado%');
                })->orderBy('Nombre')->get();
            $this->codigoCuentaAbono = $cuentas[1]->Cuenta_contable;
            $this->codigoCuentaCargo = $cuentas[0]->Cuenta_contable;
            $this->descripcionCuentaAbono = $cuentas[1]->Nombre;
            $this->descripcionCuentaCargo = $cuentas[0]->Nombre;
            // dd($cuentas);
            // dd($this->descripcionCuentaCargo, $this->descripcionCuentaAbono, $this->codigoCuentaCargo, $this->codigoCuentaAbono);
            $this->dispatch(
                'actualizar-cuentas',
                codigoCargo: $this->codigoCuentaCargo,
                codigoAbono: $this->codigoCuentaAbono,
                descripcionCargo: $this->descripcionCuentaCargo,
                descripcionAbono: $this->descripcionCuentaAbono
            );
        } else if ($this->estado == 'EGRESOS') {
            $cuentas = Cuenta::join('cuenta_clasificadores_egreso', 'Codigo_cuenta', 'cuenta_clasificadores_egreso.codigoCuenta')
                ->where('COG', '=', $this->selectCodigoOG)->where(function (Builder $query) {
                    $query->where('Descripcion_cuenta', 'like', '%Por ejercer%')
                        ->orWhere('Descripcion_cuenta', 'like', '%Modificado%');
                })->orderBy('Descripcion_cuenta')->get();
            $this->codigoCuentaAbonoEgreso = $cuentas[0]->Codigo_cuenta;
            $this->codigoCuentaCargoEgreso = $cuentas[1]->Codigo_cuenta;
            $this->descripcionCuentaAbonoEgreso = $cuentas[0]->Descripcion_cuenta;
            $this->descripcionCuentaCargoEgreso = $cuentas[1]->Descripcion_cuenta;

            $this->codigoClasificadorAdministrativo = $clasificadorA->codigo;
            $this->descripcionClasificadorAdministrativo = $clasificadorA->nombre;
            $this->codigoClasificadorFuncional = $clasificadorF->codigo;
            $this->descripcionClasificadorFuncional = $clasificadorF->nombre;
            $this->codigoClasificadorProgramatica = $clasificadorP->codigo;
            $this->descripcionClasificadorProgramatica = $clasificadorP->nombre;
            $this->codigoClasificadorTipoGasto = $clasificadorTG->codigo;
            $this->descripcionClasificadorTipoGasto = $clasificadorTG->nombre;

            $this->dispatch(
                'actualizar-clasificadores-egreso',
                codigoCA: $this->codigoClasificadorAdministrativo,
                descripcionCA: $this->descripcionClasificadorAdministrativo,
                codigoF: $this->codigoClasificadorFuncional,
                descripcionF: $this->descripcionClasificadorFuncional,
                codigoP: $this->codigoClasificadorProgramatica,
                descripcionP: $this->descripcionClasificadorProgramatica,
                codigoTG: $this->codigoClasificadorTipoGasto,
                descripcionTG: $this->descripcionClasificadorTipoGasto,
                codigoAbonoEgreso: $this->codigoCuentaAbonoEgreso,
                descripcionAbonoEgreso: $this->descripcionCuentaAbonoEgreso,
                codigoCargoEgreso: $this->codigoCuentaCargoEgreso,
                descripcionCargoEgreso: $this->descripcionCuentaCargoEgreso,

            );
        }
    }
    #[On('reiniciar-estado')]
    public function reiniciarEstado() {
        $this->estado = $this->estadoOriginal;
        $this->numeroEvento = 0;
    }


    #[On('reset-data')]
    public function resetData()
    {
        $this->selectDescripcionDepartamento = "";
        $this->selectCodigoDepartamento = "";
        $this->selectDescripcionRI = "";
        $this->selectCodigoRI = "";
        $this->selectDescripcionFF = "";
        $this->selectCodigoFF = "";
        $this->descripcionCuentaCargo = "";
        $this->codigoCuentaCargo = "";
        $this->descripcionCuentaAbono = "";
        $this->codigoCuentaAbono = "";
        $this->selectCodigoOG = "";
        $this->selectDescripcionOG;
        $this->codigoCuentaAbonoEgreso = '';
        $this->codigoCuentaCargoEgreso = '';
        $this->descripcionCuentaAbonoEgreso = '';
        $this->descripcionCuentaCargoEgreso = '';
        $this->codigoCuentaAbonoEgreso = '';
        $this->codigoCuentaCargoEgreso  = '';
        $this->descripcionCuentaAbonoEgreso = '';
        $this->descripcionCuentaCargoEgreso = '';

        $this->codigoClasificadorAdministrativo  = '';
        $this->descripcionClasificadorAdministrativo = '';
        $this->codigoClasificadorFuncional = '';
        $this->descripcionClasificadorFuncional  = '';
        $this->codigoClasificadorProgramatica  = '';
        $this->descripcionClasificadorProgramatica  = '';
        $this->codigoClasificadorTipoGasto  = '';
        $this->descripcionClasificadorTipoGasto = '';
    }

    #[On('cancelar-movimiento')]
    public function cancelarMovimiento()
    {
        $this->consulta = false;
        $this->total = 0;
        $this->numeroEvento = 0;
        $this->resetData();
    }

    #[On('continuar-ampliacion')]
    public function continuarAmpliacion($estado, $totalPrevio)
    {
        $this->estado = $estado;
        $this->consulta = false;
        $this->total = 0;
        $this->totalPrevio = $totalPrevio;
    }

    #[On('suma-total')]
    public function sumaTotal($total)
    {
        $total = str_replace(array('$','.', ','), array('','.',''), $total);
        $this->total += $total;
    }

    #[On('finalizarRegistrosIngresos')]
    public function finalizarRegistrosIngresos($registros)
    {
        $idUsuarioRegistrante = Auth::id();
        $this->total = $this->total;
        $this->total = number_format($this->total, 2, '.', ',');
        $this->total = '$' . $this->total;
        $this->consulta = true;
        $this->registros = $registros;
        $numerosPolizas = Poliza::select('numero_poliza')
            ->where('tipo_poliza', '=', 'D')
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->distinct()
            ->orderBy('numero_poliza')
            ->pluck('numero_poliza')
            ->toArray();
        sort($numerosPolizas);
        $this->numeroPoliza = (int)end($numerosPolizas) + 1;
        if ($this->numeroEvento == 0) {
            $numerosEvento = Poliza::select('evento')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('evento')
                ->pluck('evento')
                ->toArray();
            sort($numerosEvento);
            if (!empty($numerosEvento)) {
                // $poliza = Poliza::whereYear('fecha', '=', Carbon::now()->year)->orderBy('evento', 'DESC')->first();
                $this->numeroEvento = (int)end($numerosEvento) + 1;

            } else {
                $this->numeroEvento = 1;
            }
        }

        $anioActual = Carbon::now()->year;
        $fecha = Carbon::now('America/Mexico_City');
        $fecha->year($anioActual);
        foreach ($registros as $registro) {
            $cuenta = Cuenta::where("Codigo_cuenta", "=", $registro[0]['cuenta'])->first();
            $interaccionCuentaConceptoIzquierda = InteraccionCuentaConcepto::where('cuenta_id', '=', $cuenta->id)->where('concepto_id', '=', $this->estado == 'INGRESOS' ? '5' : '8')->first();
            if ($this->estado == 'INGRESOS') {
                $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_2', '=', $interaccionCuentaConceptoIzquierda->id)->first();
                $interaccionCuentaConceptoDerecha = InteraccionCuentaConcepto::where('id', '=', $interaccionCuentaCuenta->id_interaccion_concepto_cuenta_1)->first();
            } else {
                $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoIzquierda->id)->first();
                $interaccionCuentaConceptoDerecha = InteraccionCuentaConcepto::where('id', '=', $interaccionCuentaCuenta->id_interaccion_concepto_cuenta_2)->first();
            }
            $cuentaDerecha = Cuenta::find($interaccionCuentaConceptoDerecha->cuenta_id);
            foreach ($registro as $mes) {
                if ($mes['Importe'] != 0) {
                    $poliza = new Poliza([
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $mes['area'],
                        'tipo_poliza' => 'D',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $fecha,
                        'cuenta' => $mes['cuenta'],
                        'concepto' => $cuenta->Descripcion_cuenta,
                        'total' => $mes['Importe'],
                        'mes' => $mes['mes'],
                        'descripcion' => $this->observaciones,
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $mes['tipo_interaccion'] == 'Presupuestal - Abono' ? 'Abono' : 'Cargo',
                        'validado' => false,
                        'categoria' => ($this->tipo == "Ampliación") ? 'AMPLIACION ' . $this->estado : 'REDUCCION ' . $this->estado,
                        'documento_fuente' => $this->documentoFuente,
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                    $polizaModificado = new Poliza([
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $mes['area'],
                        'tipo_poliza' => 'D',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $fecha,
                        'cuenta' => $cuentaDerecha->Codigo_cuenta,
                        'concepto' => $cuentaDerecha->Descripcion_cuenta,
                        'total' => $mes['Importe'],
                        'mes' => $mes['mes'],
                        'descripcion' => $this->observaciones,
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $mes['tipo_interaccion'] == 'Presupuestal - Abono' ? 'Cargo' : 'Abono',
                        'validado' => false,
                        'categoria' => ($this->tipo == "Ampliación") ? 'AMPLIACION ' . $this->estado : 'REDUCCION ' . $this->estado,
                        'documento_fuente' => $this->documentoFuente,
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                    $poliza->save();
                    $polizaModificado->save();
                }
            }
        }
        $bitacora = new BitacoraController();
        $bitacora->bitacora('finalizarRegistrosIngresos', 'finalizó o intentó finalizar el registro de una ' .$this->tipo. ' de ' .$this->estado. ' con evento : '.$this->numeroEvento, request());
    }
}
