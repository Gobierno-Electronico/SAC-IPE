<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cuenta;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;
use \App\Models\CodigoDepartamento;
use App\Models\CuentaCapitulo;
use Livewire\Attributes\On;
use Log;


class RecalendarizacionForm extends Component
{
    #[Validate('required', message: 'Descripción del área solicitante requerida')]
    public $selectDescripcionArea = "";

    #[Validate('required', message: 'Código del área solicitante requerido')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Área responsable requerida')]
    public $areaResponsable = "";

    #[Validate('required', message: 'Partida requerida')]
    public $cog = ""; //partida

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Afectacion requerida')]
    public $afectacion = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    #[Validate('required', message: 'Solvencia requerida')]
    public $solvencia = "";

    #[Validate('required', message: 'Tipo de movimiento requerido')]
    public $movimiento = "";

    public $capitulo = '0';
    public $consulta = false;
    public $numeroEvento;
    public $numeroPoliza;
    public $totalAumentado = 0;
    public $totalDisminuido = 0;

    public function render()
    {
        return view('livewire.recalendarizacion-form');
    }

    public function save()
    {
    }

    public function  change($element =""){
        if ($element != ""){
            switch ($element) {
                case 'codigo':
                    $this->selectDescripcionArea = $this->selectCodigoArea;
                case 'descripcion':
                    $this->selectCodigoArea = $this->selectDescripcionArea;
                    $this->areaResponsable = $this->selectDescripcionArea;
                    break;
                default:
                    break;
            }
        }
    }

    public function agregarRegistro(){
        try {
            $this->importe = floatval(str_replace(['$',','],"",$this->importe));
            $this->importe = ($this->importe > 0)  ? $this->importe : "";
            $this->solvencia = floatval(str_replace(['$',','],"",$this->solvencia));
            if ($this->afectacion != 'aumento') {
                $this->solvencia = ($this->solvencia > 0) ? $this->solvencia : "";
            }
            $this->validate();
            $registro = [
                'id' => 0,
                'descripcionArea' => $this->selectDescripcionArea,
                'codigoArea' => $this->selectCodigoArea,
                'observaciones' => $this->observaciones,
                'areaResponsable' => $this->areaResponsable,
                'cog' => $this->cog,
                'mes' => $this->mes,
                'afectacion' => $this->afectacion,
                'solvencia' => $this->solvencia,
                'importe' => $this->importe,
                'movimiento' => $this->movimiento
            ];
            if($registro['afectacion'] == "disminucion" && $registro['importe'] > $registro['solvencia']){
                $this->dispatch('mostrarMensaje', mensaje: 'Solvencia insuficiente para una disminucion con ese importe', tipo: 'error', tiempo: 3000);
                return;
            }
            $this->dispatch('agregar-registro', registro: $registro);
            $this->limpiar();
        }  catch (\Illuminate\Validation\ValidationException $e) {
            if($e->validator){
                $errors = $e->validator->errors()->all();
                foreach ($errors as $value) {
                    $this->dispatch('mostrarMensaje', mensaje: $value, tipo: 'warning', tiempo: 3000);
                }
            }
            else{
                throw $e;
            }
        }
    }

    public function limpiar(){
        $this->areaResponsable = "";
        $this->cog = "";
        $this->mes = "";
        $this->afectacion = "";
        $this->solvencia = "";
        $this->importe = "";
        $this->movimiento = "";
        $this->dispatch('limpiar');
    }

    public function cambioSolvencia(){
        if($this->cog == "" || $this->mes == "" || $this->areaResponsable == ""){
            return;
        }
        $cuenta = Cuenta::join('CuentasCOG', 'CuentasCOG.codigoCuenta', '=', 'cuentas.Codigo_cuenta')->select('cuentas.*', 'CuentasCOG.*')->where('Descripcion_cuenta', 'like', '%Ejercer%')->where('COG', '=', $this->cog)->orderBy('COG')->first();

        $area = CodigoDepartamento::find($this->areaResponsable);
        // dd($area->Codigo_completo, $cuenta->codigoCuenta, Carbon::now()->year, $this->mes);
        $data = DB::select('EXEC SolvenciaCuentaArea @area = ?, @cuenta = ?, @anio = ?, @mes = ?', array($area->Codigo_completo, $cuenta->codigoCuenta, Carbon::now()->year, $this->mes));
        $this->solvencia = ($data[0]->Solvencia > 0) ? floatval($data[0]->Solvencia) : 0;
        $this->dispatch('actualizar-solvencia', solvencia: $this->solvencia);
    }

    public function finalizarRegistros(){
        $this->dispatch('finalizar-registros');
    }

    #[On('consultar-registro')]
    public function consultarRegistro($numeroEvento, $numeroPoliza, $totalAumentado, $totalDisminuido) {
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->totalAumentado = $totalAumentado;
        $this->totalDisminuido = $totalDisminuido;
        $this->consulta = true;
    }

    #[On('llenar-formulario')]
    public function llenarFormulario ($areaResponsable, $cog, $mes, $movimiento, $solvencia, $afectacion, $importe) {
        $codigoAreaResponsable = explode(" ", $areaResponsable);
        $idAreaResponsable = CodigoDepartamento::where('Codigo_completo', '=', $codigoAreaResponsable[0])->value('id');
        $codigoCog = explode(" ", $cog);
        $afectacionMin = strtolower($afectacion);
        $movimientoMin = strtolower($movimiento);

        $registro = [
            "areaResponsable" => $idAreaResponsable,
            "cog" => $codigoCog[0],
            "mes" => $mes,
            "afectacion" => $afectacionMin,
            "solvencia" => $solvencia,
            "importe" => $importe,
            "movimiento" => $movimientoMin
        ];

        $this->areaResponsable = $idAreaResponsable;
        $this->cog = $codigoCog[0];
        $this->mes = $mes;
        $this->afectacion = $afectacionMin;
        $this->importe = $importe;
        $this->solvencia = $solvencia;
        $this->movimiento = $movimientoMin;

        $this->dispatch('llenarFormulario', areaResponsable: $areaResponsable, cog: $cog, mes: $mes,
                                            afectacion: $afectacion, solvencia: $solvencia, importe: $importe, movimiento: $movimiento);
    }


    #[On('reiniciar')]
    public function reiniciar() {
        $this->limpiar();
        $this->consulta = false;
        $this->numeroEvento = 0;
        $this->numeroPoliza = 0;
        $this->totalAumentado = 0;
        $this->totalDisminuido = 0;
        $this->observaciones = '';
    }

}
