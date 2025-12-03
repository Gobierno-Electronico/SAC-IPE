<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\Poliza;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use Carbon\Carbon;
use Log;
use DB;
use App\Enums\EstatusEvento;

class DeudoresReintegroAnticipoForm extends Component
{
    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Cuenta cargo requerida')]
    public $cuentaCargo = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Evento requerido')]
    public $numeroEvento = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Fecha de afectación requerida')]
    public $fechaAfectacion = "";

    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoDelEvento = "";

    #[Validate('required', message: 'Documento fuente requerido')]
    public $documentoFuente = "";

    public $consultarRegistro = false;
    public $numeroPoliza;
    public $total;
    public $tipoMovimiento;
    public $ppto;

    public function render()
    {
        try {
            $eventos =  Poliza::select('evento', 'descripcion')
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->where('tipo_poliza', '=', 'D')
            ->where('categoria', '=', 'DEUDORES OTORGAMIENTO ANTICIPOS')
            ->where('estatus_evento', '=', EstatusEvento::ACTIVO->value)
            ->distinct()
            ->pluck('descripcion', 'evento');

            $cuentasCargo = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->where('interaccion_cuenta_conceptos.concepto_id', [10107])
            ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Cargo')
            ->where('cuentas.Descripcion_cuenta', '=', 'Caja General')
            ->orWhere('cuentas.Descripcion_cuenta', '=', 'BBVA Bancomer 4441066229') 
            ->orderBy('cuentas.Codigo_cuenta')->get(); 

            $cuentasCargo = $cuentasCargo->unique('Descripcion_cuenta');

            $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->whereIn('interaccion_cuenta_conceptos.concepto_id', [10107])
            ->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Contable - Abono')
            ->where('cuentas.Descripcion_cuenta', 'LIKE', '%Responsabilidad de Funcionarios%' )
            ->orderBy('cuentas.Descripcion_cuenta')->get();

            $this->cuenta = $cuentas[0]->cuenta_id;

            return view('livewire.deudores-reintegro-anticipo-form', ['cuentas' => $cuentas, 'eventos' => $eventos, 'cuentasCargo' => $cuentasCargo]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en deudores reintegro de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cambioEvento(){
        $this->limpiar(); 
        try{
            $this->montoDelEvento = DB::select('EXEC ImporteTotalOtorgamientoAnticipo @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el monto del evento en deudores reintegro de anticipos: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cargarPresupuesto()
    {
        if (!$this->cuenta || !$this->mes  ) return;

        try{
            $anioActual = Carbon::now()->year;
            $codigoCuenta = Cuenta::where('id', '=', $this->cuenta)->value('Codigo_cuenta');
            $solvencia = DB::select('EXEC SolvenciaReintegrosAnticipos @cuenta = ?, @anio = ?, @mes = ?, @evento = ?', array($codigoCuenta, $anioActual, $this->mes, $this->numeroEvento))[0]->Total;
            $this->ppto = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputSolvencia', amount: "{$this->ppto}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto comprometido cargado', tipo: 'success', tiempo: 1500);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en deudores reintegro de anticipos: ' . $th->getMessage());
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
            $cuentaCargo = Cuenta::find($this->cuentaCargo);
            $departamento = CodigoDepartamento::find($this->selectCodigoArea);
            $registro = [
                'id' => 0,
                'codigoArea' => $this->selectCodigoArea,
                'observaciones' => $this->observaciones,
                'fechaAfectacion' => $this->fechaAfectacion,
                'codigoAreaResponsable' => $departamento->Codigo_completo,
                'descripcionAreaResponsable' => $departamento->Nombre,
                'evento' => $this->numeroEvento,
                'idCuenta' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' => $cuenta->Descripcion_cuenta,
                'idCuentaCargo' => $this->cuentaCargo,
                'codigoCuentaCargo' => $cuentaCargo->Codigo_cuenta,
                'descripcionCuentaCargo' => $cuentaCargo->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'montoEvento' => $this->montoDelEvento,
                'ppto' => $this->ppto,
                'documentoFuente' => $this->documentoFuente
            ];
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al registrar en deudores reintegro de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function limpiar()
    {
        $this->cuentaCargo = "";
        $this->mes = "";
        $this->importe = "";
        $this->ppto = "";
        $this->dispatch('limpiar');
    }

    public function finalizarRegistros()
    {
        $this->dispatch('finalizar-registros');
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total)
    {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->cuentaCargo = $datosRegistro['cuentaCargo'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->ppto = $datosRegistro['solvencia'];
        $this->documentoFuente = $datosRegistro['documentoFuente'];
        $this->dispatch('llenarFormulario', cuenta: $datosRegistro['cuenta'], cuentaCargo: $datosRegistro['cuentaCargo'], mes: $datosRegistro['mes'], importe: $datosRegistro['importe'], ppto: $datosRegistro['solvencia']);
    }
}
