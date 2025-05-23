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
use Illuminate\Support\Collection;
use Log;
use DB;
use Carbon\Carbon;
use App\Enums\EstatusEvento;

class EgresosCapitulo2y3PagadoForm extends Component
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

    #[Validate('required', message: 'Método de pago requerido')]
    public $cuentaBanco = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoDelEvento = "";


    #[Validate('required', message: 'Cuenta de retenciones requerida')]
    public $cuentaDeRetenciones = "";

    public $partidasPresupuestales = [];
    public $cuentasBanco = [];
    public $cuentasRetenciones = [];
    public $PPTOEjercido;
    public $montoContable = 0;
    public $cambiarPartidaPresupuestalSeleccionada = false;
    public $cambiarCuentaBancoSeleccionada = false;
    public $cambiarCuentaRetencionesSeleccionada = false;

    public function render()
    {
        try {
            $eventos = Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS EJERCIDO CAPITULO 2y3')
                ->where('estatus_evento', '=', EstatusEvento::ACTIVO->value)
                ->distinct()
                ->pluck('descripcion', 'evento');

            $this->cambiarPartidaPresupuestalSeleccionada = false;
            $this->llenarPartidasPresupuestales();
            $this->cambiarCuentaBancoSeleccionada = false;
            $this->llenarCuentasBanco();
            $this->cambiarCuentaRetencionesSeleccionada = false;

            return view('livewire.egresos-capitulo2y3-pagado-form', ['eventos' => $eventos]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar eventos en Pagado del capítulo 2000 y 3000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cambioEvento()
    {
        try {
            $this->limpiar();
            $this->llenarCamposEspecificos();
            $this->montoDelEvento = DB::select('EXEC ImporteTotalCapitulo2y3Pagado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
            $this->llenarPartidasPresupuestales();
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en Pagado del capítulo 2000 y 3000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCamposEspecificos(){
        try{      
            $descripcionEvento = Poliza::select('descripcion')
            ->where('evento', '=', $this->numeroEvento)
            ->where('tipo_poliza', '=', 'E')
            ->where('categoria', '=', 'EGRESOS EJERCIDO CAPITULO 2y3')
            ->get()[0]->descripcion;
        
            $areaEvento = Poliza::select('area')
                ->where('evento', '=', $this->numeroEvento)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS EJERCIDO CAPITULO 2y3')
                ->get()[0]->area;
        
            $idArea = CodigoDepartamento::select('id')
                ->where('Codigo_completo', '=', $areaEvento)
                ->get()[0]->id; 
        
            $this->observaciones = $descripcionEvento;
            $this->selectCodigoAreaResponsable = $idArea;   
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al llenar campos específicos en Pagado del capítulo 2 y 3: ' . $th->getMessage());
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

            $cuentasEjercidas = Poliza::join('cuentas', 'cuentas.Codigo_cuenta', '=', 'polizas.cuenta')
                ->where('polizas.evento', '=', $this->numeroEvento)
                ->where('polizas.tipo_poliza', '=', 'E')
                ->where('polizas.concepto', 'LIKE', '%Ejercido%')
                ->get();

            foreach ($cuentasEjercidas as $ejercida) {
                $interaccionCuentaConceptoEjercido = InteraccionCuentaConcepto::where('cuenta_id', '=', $ejercida->id)->whereIn('concepto_id', [92, 93])
                    ->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();

                $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_2', '=', $interaccionCuentaConceptoEjercido->id)
                    ->first();

                $interaccionCuentaPagado = InteraccionCuentaConcepto::where('id', '=', $interaccionCuentaCuenta->id_interaccion_concepto_cuenta_1)
                    ->whereIn('concepto_id', [92, 93])->where('tipo_interaccion', '=', 'Presupuestal - Cargo')
                    ->first();

                $cuentaPagado = Cuenta::where('id', '=', $interaccionCuentaPagado->cuenta_id)->first();
                array_push($this->partidasPresupuestales, $cuentaPagado);
            }

            $this->partidasPresupuestales = array_unique($this->partidasPresupuestales);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al llenar partidas presupuestales en pagado del capítulo 2000 y 3000: ' . $th->getMessage());
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
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->partidaPresupuestal)->whereIn('interaccion_cuenta_conceptos.concepto_id', [92, 93])
                ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $this->cuentasBanco = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
                ->join('interaccion_cuenta_conceptos', function ($join) {
                    $join->on('interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                        ->where('tipo_interaccion', '=', 'Contable - Abono');
                })
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get();
            $this->llenarCuentasRetenciones($interaccionCuentaConcepto->id);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar las cuentas de banco en pagado capítulo 2000 y 3000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas de banco, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cargarPresupuestoEjercido()
    {
        try {
            if (!$this->partidaPresupuestal || !$this->mes || !$this->selectCodigoAreaResponsable) return;

            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->partidaPresupuestal)->whereIn('interaccion_cuenta_conceptos.concepto_id', [92, 93])->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Ejercido)%')->first();


            $solvencia = DB::select('EXEC SolvenciaEjercidosCapitulo2y3 @area = ?, @cuenta = ?, @anio = ?, @mes = ?, @evento = ?', array($departamento->Codigo_completo, $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $this->mes, $this->numeroEvento))[0]->Total;
            if ($this->cuentaDeRetenciones != "") {
                $this->cargarMontoContable();
            } else {
                $this->montoContable = 0;

                return;
            }
            $this->PPTOEjercido = ($solvencia > 0) ? floatval($solvencia) : 0;
            $this->dispatch('formato_importe', id: 'inputPTTOEjercido', amount: "{$this->PPTOEjercido}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto ejercido cargado', tipo: 'success', tiempo: 1500);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto ejercido en pagado del capítulo 2000 y 3000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
    public function llenarCuentasRetenciones($idInteraccionCuentaConcepto)
    {
        try {
            if ($this->cambiarCuentaRetencionesSeleccionada) {
                $this->cuentaDeRetenciones = "";
            }
            $partidaPresupuestalSeleccionada = Cuenta::find($this->partidaPresupuestal);


            $conceptoGeneralPartidaSeleccionada = explode('(Pagado', $partidaPresupuestalSeleccionada->Descripcion_cuenta);
            $partidaDevengado = Cuenta::where('Descripcion_cuenta', 'LIKE', '%' . $conceptoGeneralPartidaSeleccionada[0] . '(Devengado)' . '%')->get();

            if(count($partidaDevengado) > 1){
                // Obtener los últimos dos segmentos de partidaPresupuestalSeleccionada
                $codigoPresupuestal = explode('.', $partidaPresupuestalSeleccionada->Codigo_cuenta);
                $ultimosDosPresupuestal = implode('.', array_slice($codigoPresupuestal, -2, 2));
    
                // Filtrar partidaDevengado dejando solo la cuenta que coincida en los últimos dos segmentos
                $partidaDevengado = $partidaDevengado->filter(function ($cuenta) use ($ultimosDosPresupuestal) {
                    $codigoCuenta = explode('.', $cuenta->Codigo_cuenta);
                    $ultimosDosCuenta = implode('.', array_slice($codigoCuenta, -2, 2));
    
                    return $ultimosDosCuenta == $ultimosDosPresupuestal;
                })->values();
            }
            

            // Si quieres reiniciar los índices del array después del filtro
            $partidaDevengado = $partidaDevengado->values();

            $cuentasDevengadas = Poliza::where('evento', '=', $this->numeroEvento)
                ->where('tipo_poliza', '=', 'E')
                ->where('tipo_interaccion', '=', 'Contable - Abono')
                ->where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 2y3')
                ->where('cuentaRelacionada', '=', $partidaDevengado[0]['Codigo_cuenta'])
                ->get();

            $this->cambiarCuentaRetencionesSeleccionada = true;
            $this->cuentasRetenciones = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $idInteraccionCuentaConcepto)
                ->join('interaccion_cuenta_conceptos', function ($join) {
                    $join->on('interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                        ->where('tipo_interaccion', '=', 'Contable - Cargo');
                })
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get();


            $cuentasDevengadasAux = new Collection();
            foreach ($this->cuentasRetenciones as $pagada) {
                foreach ($cuentasDevengadas as $devengada) {
                    $conceptoComprometida = explode('(', $devengada->concepto);
                    if (str_contains($pagada->Descripcion_cuenta, $conceptoComprometida[0])) {
                        $cuentasDevengadasAux->push($pagada);
                    }
                }
            }

            $cuentasDevengadasAux = $cuentasDevengadasAux->unique('Codigo_cuenta');
            $this->cuentasRetenciones = $cuentasDevengadasAux->toArray();

            // Si solo hay una cuenta, seleccionarla automáticamente
            if (count($this->cuentasRetenciones) === 1) {
                $this->cuentaDeRetenciones = $this->cuentasRetenciones[0]['id'];
            }
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar las cuentas de retenciones en pagado capítulo 2000 y 3000: ' . $th->getMessage() . ' En La línea: ' . $th->getLine()) ;
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas de retenciones, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }


    public function cargarMontoContable()
    {
        if (!$this->partidaPresupuestal || !$this->mes || !$this->selectCodigoAreaResponsable) return;

        $anioActual = Carbon::now()->year;
        $codigoDepartamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
        $codigoCuentaContableSeleccionada = Cuenta::where('id', $this->cuentaDeRetenciones)->value('Codigo_cuenta');

        $partidaPagadoSeleccionada = Cuenta::find($this->partidaPresupuestal);
        $conceptoGeneralPartidaPagado = explode('(Pagado', $partidaPagadoSeleccionada->Descripcion_cuenta);
        $partidaDevengado = Cuenta::where('Descripcion_cuenta', 'LIKE', '%' . $conceptoGeneralPartidaPagado[0] . '(Devengado)' . '%')->get();

        //este bloque filtra codigoCuentaPagada en base a su codigo cuenta debido a que puede haber cuentas que compartan descripcion o sean similares
        if(count($partidaDevengado) > 1){
            // Obtener los últimos dos segmentos de partidaPresupuestalSeleccionada
            $codigoPresupuestal = explode('.', $partidaPagadoSeleccionada->Codigo_cuenta);
            $ultimosDosPresupuestal = implode('.', array_slice($codigoPresupuestal, -2, 2));

            // Filtrar partidaDevengado dejando solo la cuenta que coincida en los últimos dos segmentos
            $partidaDevengado = $partidaDevengado->filter(function ($cuenta) use ($ultimosDosPresupuestal) {
                $codigoCuenta = explode('.', $cuenta->Codigo_cuenta);
                $ultimosDosCuenta = implode('.', array_slice($codigoCuenta, -2, 2));

                return $ultimosDosCuenta == $ultimosDosPresupuestal;
            })->values();
        }

        $solvenciaContable = DB::select('EXEC SolvenciaDevengadoCuentaContableCapitulo2y3 @area = ?, @cuenta = ?, @anio = ?, @mes = ?, @evento = ?, @partidaPagado = ?, @partidaDevengado = ?', array($codigoDepartamento->Codigo_completo, $codigoCuentaContableSeleccionada, $anioActual, $this->mes, $this->numeroEvento, $partidaPagadoSeleccionada->Codigo_cuenta, $partidaDevengado[0]['Codigo_cuenta']))[0]->Total;

        $this->montoContable = ($solvenciaContable > 0) ? floatval($solvenciaContable) : 0;
        $this->dispatch('formato_importe', id: 'inputMontoContable', amount: "{$this->montoContable}");
        $this->dispatch('mostrarMensaje', mensaje: 'Monto contable cargado', tipo: 'success', tiempo: 1500);
    }

    public function agregarRegistro()
    {

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
                'pttoEjercido' => $this->PPTOEjercido,
                'montoContable' => $this->montoContable
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
        $this->partidasPresupuestales = [];
        $this->cuentaBanco = "";
        $this->cuentaDeRetenciones = "";
        $this->importe = "";
        $this->mes = "";
        $this->montoContable = 0;
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
        $this->montoContable = $datosRegistro['montoContable'];

        $this->dispatch('llenarFormulario', presupuesto: $this->PPTOEjercido, importe: $this->importe, cuentaBanco: $this->cuentaBanco, cuentaRetenciones: $this->cuentaDeRetenciones, montoContable: $this->montoContable);
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
