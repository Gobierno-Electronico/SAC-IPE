<?php

namespace App\Livewire\egresos;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\Poliza;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use Illuminate\Support\Collection;
use Log;
use DB;
use Carbon\Carbon;

class EgresosCapitulo4PagadoForm extends Component
{
    public $consultarRegistro = false;
    public $numeroPoliza;
    public $numeroPolizaRemanente;
    public $total;

    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Fecha de afectación requerida')]
    public $fechaAfectacion = "";

    #[Validate('required', message: 'Evento requerido')]
    public $numeroEvento = "";

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Partida presupuestal requerida')]
    public $partidaPresupuestal = "";

    #[Validate('required', message: 'Banco requerido')]
    public $cuentaBanco = "";

    #[Validate('required', message: 'Cuenta de retenciones requerida')]
    public $cuentaDeRetenciones = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    public $montoDelEvento;

    public $PPTOEjercido = 0;


    public $partidasPresupuestales = [];
    public $cambiarPartidaPresupuestalSeleccionada = true;

    public $cuentasBanco = [];
    public $cambiarCuentaBancoSeleccionada = true;

    public $cuentasRetenciones = [];
    public $cambiarCuentaRetencionesSeleccionada = true;


    public function render()
    {
        try {
            $eventos = Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS EJERCIDO CAPITULO 4')
                ->where('estatus_evento', '=', true)
                ->distinct()
                ->pluck('descripcion', 'evento');

            $this->cambiarPartidaPresupuestalSeleccionada = false;
            $this->llenarPartidasPresupuestales();
            $this->cambiarCuentaBancoSeleccionada = false;
            $this->llenarCuentasBanco();
            $this->cambiarCuentaRetencionesSeleccionada = false;

            return view('livewire.egresos.egresos-capitulo4-pagado-form', ['eventos' => $eventos]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en Pagado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cambioEvento()
    {
        try {
            $this->limpiar();
            $this->montoDelEvento = DB::select('EXEC ImporteTotalCapitulo4Pagado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
            $this->llenarPartidasPresupuestales();
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en Devengado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarPartidasPresupuestales()
    {
        try {
            if ($this->cambiarPartidaPresupuestalSeleccionada) {
                $this->partidaPresupuestal = "";
            }

            $this->cambiarPartidaPresupuestalSeleccionada = true;

            $cuentasEjercidas = Poliza::where('evento', '=', $this->numeroEvento)
                ->where('tipo_poliza', '=', 'E')
                ->where('concepto', 'LIKE', '%Ejercido%')
                ->get();

            $cuentasPagadas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->whereIn('interaccion_cuenta_conceptos.concepto_id', [40, 43, 46, 48, 49, 51])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
                ->orderBy('cuentas.Codigo_cuenta')->get();

            $cuentasDevengadasAux = new Collection();
            foreach ($cuentasPagadas as $pagada) {
                foreach ($cuentasEjercidas as $ejercida) {
                    $conceptoComprometida = explode('(', $ejercida->concepto);
                    if (str_contains($pagada->Descripcion_cuenta, $conceptoComprometida[0])) {
                        $cuentasDevengadasAux->push($pagada);
                    }
                }
            }

            $cuentasDevengadasAux = $cuentasDevengadasAux->unique('Codigo_cuenta');
            $this->partidasPresupuestales = $cuentasDevengadasAux;
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en Devengado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCuentasBanco()
    {
        if (!$this->partidaPresupuestal) return;

        if ($this->cambiarCuentaBancoSeleccionada) {
            $this->cuentaBanco = "";
            $this->cargarPresupuestoEjercido();
        }

        try {
            $this->cambiarCuentaBancoSeleccionada = true;
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->partidaPresupuestal)->whereIn('interaccion_cuenta_conceptos.concepto_id', [40, 43, 46, 48, 49, 51])
                ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $this->cuentasBanco = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
                ->join('interaccion_cuenta_conceptos', function ($join) {
                    $join->on('interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                        ->where('tipo_interaccion', '=', 'Contable - Abono');
                })
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get();

            $this->llenarCuentasRetenciones($interaccionCuentaConcepto->id);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar las cuentas de banco en pagado capítulo 4000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas de banco, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCuentasRetenciones($idInteraccionCuentaConcepto)
    {
        try {
            if ($this->cambiarCuentaRetencionesSeleccionada) {
                $this->cuentaDeRetenciones = "";
            }
            $this->cambiarCuentaRetencionesSeleccionada = true;
            $this->cuentasRetenciones = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $idInteraccionCuentaConcepto)
                ->join('interaccion_cuenta_conceptos', function ($join) {
                    $join->on('interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                        ->where('tipo_interaccion', '=', 'Contable - Cargo');
                })
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get();
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar las cuentas de retenciones en pagado capítulo 4000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas de retenciones, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cargarPresupuestoEjercido()
    {
        try {
            if (!$this->partidaPresupuestal || !$this->mes || !$this->selectCodigoAreaResponsable) return;

            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->partidaPresupuestal)->whereIn('interaccion_cuenta_conceptos.concepto_id', [40, 43, 46, 48, 49, 51])->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Ejercido)%')->first();


            $solvencia = DB::select('EXEC SolvenciaEjercidosCapitulo4 @area = ?, @cuenta = ?, @anio = ?, @mes = ?, @evento = ?', array($departamento->Codigo_completo, $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $this->mes, $this->numeroEvento))[0]->Total;
            $this->PPTOEjercido = ($solvencia > 0) ? floatval($solvencia) : 0;
            $this->dispatch('formato_importe', id: 'inputPTTOEjercido', amount: "{$this->PPTOEjercido}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto ejercido cargado', tipo: 'success', tiempo: 1500);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en devengado del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function agregarRegistro()
    {
        Log::info($this->cuentaDeRetenciones);

        try {
            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();
            $partida = Cuenta::find($this->partidaPresupuestal);
            $cuentaBancoSeleccionada = Cuenta::find($this->cuentaBanco);
            $cuentaRetencionesSeleccionada = Cuenta::find($this->cuentaDeRetenciones);
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $registro = [
                'id' => 0,
                'codigoArea' => $this->selectCodigoArea,
                'observaciones' => $this->observaciones,
                'fechaAfectacion' => $this->fechaAfectacion,
                'evento' => $this->numeroEvento,
                'areaResponsableId' => $this->selectCodigoAreaResponsable,
                'codigoAreaResponsable' => $departamento->Codigo_completo,
                'descripcionAreaResponsable' => $departamento->Nombre,
                'partidaId' => $this->partidaPresupuestal,
                'codigoPartida' => $partida->Codigo_cuenta,
                'descripcionPartida' => $partida->Descripcion_cuenta,
                'cuentaBancoId' => $this->cuentaBanco,
                'codigoCuentaBanco' => $cuentaBancoSeleccionada->Codigo_cuenta,
                'descripcionCuentaBanco' => $cuentaBancoSeleccionada->Descripcion_cuenta,
                'cuentaRetencionesId' => $this->cuentaDeRetenciones,
                'codigoCuentaRetenciones' => $cuentaRetencionesSeleccionada->Codigo_cuenta,           
                'descripcionCuentaRetenciones' => $cuentaRetencionesSeleccionada->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'montoEvento' => $this->montoDelEvento,
                'pttoEjercido' => $this->PPTOEjercido
            ];

            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }
    }

    public function finalizarRegistros()
    {
        $this->dispatch('finalizar-registros');
    }

    public function limpiar()
    {
        $this->cuentasBanco = [];
        $this->PPTOEjercido = "";
        $this->partidaPresupuestal = "";
        $this->cuentaBanco = "";
        $this->cuentaDeRetenciones = "";
        $this->importe = "";
        $this->mes = "";
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {

        $this->partidaPresupuestal = $datosRegistro['partida']; 
        $this->cuentaBanco = $datosRegistro['cuentaBanco'];
        $this->cuentaDeRetenciones = $datosRegistro['cuentaRetenciones'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->PPTOEjercido = $datosRegistro['pttoEjercido'];

        $this->dispatch('llenarFormulario', presupuesto: $this->PPTOEjercido, importe: $this->importe, cuentaBanco: $this->cuentaBanco, cuentaRetenciones: $this->cuentaDeRetenciones);
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total, $numeroPolizaRemanente)
    {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->numeroPolizaRemanente = $numeroPolizaRemanente;
        $this->total = $total;
    }
}
