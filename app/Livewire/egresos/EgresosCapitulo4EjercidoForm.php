<?php

namespace App\Livewire\egresos;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use Log;
use Illuminate\Support\Collection;

use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use App\Models\Poliza;
use Carbon\Carbon;
use DB;

class EgresosCapitulo4EjercidoForm extends Component
{
    public $consultarRegistro = false;
    public $numeroPoliza;
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

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Monto del evento requerido')]
    public $montoDelEvento = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe;

    public $cambiarCuentaSeleccionada = true;
    public $partidasPresupuestales = [];
    public $PTTODevengado = 0;

    public function render() 
    {
        try{
            $eventos =  Poliza::select('evento', 'descripcion')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->where('tipo_poliza', '=', 'E')
                ->where('categoria', '=', 'EGRESOS DEVENGADO CAPITULO 4')
                ->where('estatus_evento', '=', true)
                ->distinct()
                ->pluck('descripcion', 'evento');
            $this->cambiarCuentaSeleccionada = false;
            $this->llenarCuentasPresupuestalCargo();
            return view('livewire.egresos.egresos-capitulo4-ejercido-form', ['eventos' => $eventos]);
        }catch(\Throwable $th){
            Log::error('Ocurrió un error al cargar cuentas en ejercido: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000); 
        }
    }

    public function cambioEvento(){
        try {
            $this->montoDelEvento = DB::select('EXEC ImporteTotalCapitulo4Ejercido @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            $this->dispatch('formato_importe', id: 'inputMontoEvento', amount: ($this->montoDelEvento > 0) ? $this->montoDelEvento : '');
            $this->dispatch('mostrarMensaje', mensaje: 'Monto del evento cargado', tipo: 'success', tiempo: 1500);
            $this->cambiarCuentaSeleccionada = false;
            $this->llenarCuentasPresupuestalCargo();
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en ejercido: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function llenarCuentasPresupuestalCargo()
    {
        try {
            if ($this->cambiarCuentaSeleccionada) {
                $this->cuenta = "";
                $this->cargarPresupuestoDevengado();
            }

            $this->cambiarCuentaSeleccionada = true;

            $cuentasDevengadas = Poliza::where('evento', '=', $this->numeroEvento)
                ->where('tipo_poliza', '=', 'E')
                ->where('concepto', 'LIKE', '%Devengado%')
                ->get();

            $cuentasEjercidas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->whereIn('interaccion_cuenta_conceptos.concepto_id', [59, 60, 61, 62])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
                ->orderBy('cuentas.Codigo_cuenta')->get();

            $cuentasEjercidasAux = new Collection();

            foreach ($cuentasEjercidas as $ejercida) {
                foreach ($cuentasDevengadas as $comprometida) {
                    $conceptoComprometida = explode('(', $comprometida->concepto);
                
                    if (str_contains($ejercida->Descripcion_cuenta, $conceptoComprometida[0])) {
                        // Verificamos si $ejercida ya existe en la colección
                        if (!$cuentasEjercidasAux->contains(function($value) use ($ejercida) {
                            return $value->Descripcion_cuenta === $ejercida->Descripcion_cuenta;
                            })) {
                            // Si no existe, lo agregamos
                            $cuentasEjercidasAux->push($ejercida);
                        }
                    }
                }
            }
            $this->partidasPresupuestales = $cuentasEjercidasAux;     
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar el evento en Ejercido del capítulo 4000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar el evento, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cargarPresupuestoDevengado(){
        try{
            $this->cambiarCuentaSeleccionada = false;
            $this->llenarCuentasPresupuestalCargo();

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

    public function agregarRegistro()
    {
        try{
            $this->importe = floatval(str_replace(['$', ','], "", $this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->validate();

            $cuenta = Cuenta::find($this->cuenta);
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
                'cuentaId' => $this->cuenta,
                'codigoCuenta' => $cuenta->Codigo_cuenta,
                'descripcionCuenta' => $cuenta->Descripcion_cuenta,
                'mes' => $this->mes,
                'importe' => $this->importe,
                'montoEvento' => $this->montoDelEvento,
                'pttoDevengado' => $this->PTTODevengado
            ];

            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en ejercido del capítulo 4: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function limpiar()
    {
        $this->cuenta = "";
        $this->selectCodigoAreaResponsable = "";
        $this->PTTODevengado = "";
        $this->importe = "";
        $this->mes = "";
        $this->dispatch('limpiar');
    }

    #[On('llenar-formulario')]
    public function llenarFormulario($datosRegistro)
    {
        $this->cuenta = $datosRegistro['cuenta'];
        $this->mes = $datosRegistro['mes'];
        $this->importe = $datosRegistro['importe'];
        $this->selectCodigoAreaResponsable = $datosRegistro['area'];
        $this->PTTODevengado = $datosRegistro['pttoDevengado'];
        $this->dispatch('llenarFormulario', presupuesto: $this->PTTODevengado, importe: $this->importe);
    }

    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total)
    {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
    }
    public function finalizarRegistros()
    {
        $this->dispatch('finalizar-registros');
    }
}