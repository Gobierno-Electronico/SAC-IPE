<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use Log;
use Illuminate\Support\Collection;
use App\Http\Controllers\BitacoraController;
use Illuminate\Support\Facades\Auth;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use App\Models\Poliza;
use Carbon\Carbon;
use DB;

class EgresosCapitulo1EjercidoForm extends Component
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

    #[Validate('required', message: 'Monto del evento requerido')]  
    public $montoDelEvento = "";

    public $PTTODevengado = 0;

    public function render() 
    {
        try{
            $eventos =  Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 1')
                ->where('estatus_evento', '=', true)
                ->distinct()
                ->pluck('descripcion', 'evento');
            return view('livewire.egresos-capitulo1-ejercido-form', ['eventos' => $eventos]);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar cuentas en ejercido: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }
    }

    public function cambioEvento(){
        try {
            $this->limpiar();
            $this->montoDelEvento = DB::select('EXEC ImporteTotalCapitulo1Ejercido @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en ejercido: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }


    public function cargarPresupuestoDevengado(){
        try{

            if (!$this->cuenta || !$this->mes || !$this->selectCodigoAreaResponsable) return;

            $anioActual = Carbon::now()->year;
            $departamento = CodigoDepartamento::find($this->selectCodigoAreaResponsable);
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $this->cuenta)->whereIn('interaccion_cuenta_conceptos.concepto_id', [59, 60, 61, 62])->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
            ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Devengado)%')->first();

            
            $solvencia = DB::select('EXEC SolvenciaDevengadosCapitulo4 @area = ?, @cuenta = ?, @anio = ?, @mes = ?, @evento = ?', array($departamento->Codigo_completo, $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $this->mes, $this->numeroEvento))[0]->Total;
            $this->PTTODevengado = ($solvencia > 0) ? floatval($solvencia) : 0;

            $this->dispatch('formato_importe', id: 'inputPTTODevengado', amount: "{$this->PTTODevengado}");
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto devengado cargado', tipo: 'success', tiempo: 1500);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar presupuesto en ejercido del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar presupuesto, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function limpiar()
    {
        $this->selectCodigoAreaResponsable = "";
        $this->PTTODevengado = "";
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->PTTODevengado = $datosRegistro['pttoDevengado'];
        $this->dispatch('llenarFormulario', presupuesto: $this->PTTODevengado, importe: $this->importe);
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
#[On('finalizar-registros')]
    public function finalizarRegistros()
    {

        try {
            $idUsuarioRegistrante = Auth::id();
            $numerosPolizas = Poliza::select('numero_poliza')
                ->where('tipo_poliza', '=', 'E')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            sort($numerosPolizas);
            $this->numeroPoliza = (int)end($numerosPolizas) + 1;


            $anioActual = Carbon::now()->year;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);

            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar un ejercido del capítulo 1 con evento: ' . $this->numeroEvento, request());
            DB::beginTransaction();

            $polizasDevengadas = Poliza::select()
                ->where('tipo_poliza', '=', 'E')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('evento','=',$this->numeroEvento)
                ->where('categoria','=','EGRESOS DEVENGADO CAPITULO 1')
                ->where('concepto','LIKE','%(Devengado)%')
                ->get();
            foreach ($polizasDevengadas as $movimiento) {

                $cuentaID = Cuenta::where('Codigo_cuenta', '=', $movimiento['cuenta'])
                    ->first();
                $movimiento['total'] = doubleval($movimiento['total']);
                $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $cuentaID->id)->where('concepto_id', [10104])
                    ->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();

                $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_2', '=', $interaccionCuentaConceptoPrincipal->id)
                    ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_1')
                    ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->first();

                $polizas = [
                    [
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $movimiento->area,
                        'tipo_poliza' => 'E',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $this->fechaAfectacion,
                        'cuenta' => $interaccionCuentaCuentas['Codigo_cuenta'],
                        'concepto' => $interaccionCuentaCuentas['Descripcion_cuenta'],
                        'total' => abs($movimiento['total']),
                        'mes' => $movimiento['mes'],
                        'descripcion' => $movimiento['descripcion'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $interaccionCuentaCuentas['tipo_interaccion'],
                        'validado' => false,
                        'estatus_evento' => true,
                        'categoria' => 'EGRESOS EJERCIDO CAPITULO 1',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]
                ];

                Poliza::insert($polizas);

                $polizas = [
                    [
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $movimiento->area,
                        'tipo_poliza' => 'E',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $this->fechaAfectacion,
                        'cuenta' => $movimiento['cuenta'],
                        'concepto' => $movimiento['concepto'],
                        'total' => abs($movimiento['total']),
                        'mes' => $movimiento['mes'],
                        'descripcion' => $movimiento['descripcion'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => 'Presupuestal - Abono',
                        'validado' => false,
                        'estatus_evento' => true,
                        'categoria' => 'EGRESOS EJERCIDO CAPITULO 1',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]
                ];

                Poliza::insert($polizas);


            }
            
            $importeTotalEvento = DB::select('EXEC ImporteTotalCapitulo1Ejercido @evento = ?', [$this->numeroEvento]);
            if ($importeTotalEvento[0]->MontoDelEvento == 0) {
                Poliza::where('evento', '=', $this->numeroEvento)
                    ->whereIn('categoria', ['EGRESOS DEVENGADO CAPITULO 1'])
                    ->whereYear('fecha', '=', Carbon::now()->year)
                    ->update(['estatus_evento' => false]);
            }
            // dd($polizas);

            DB::commit();
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total, $this->numeroPolizaRemanente);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en ejercido del capítulo 1: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}