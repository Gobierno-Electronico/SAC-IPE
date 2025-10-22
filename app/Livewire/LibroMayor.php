<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Cuenta;
use Log;
class LibroMayor extends Component
{

    public $tipoCuenta = '';
    public $nivel = '';
    public $cuenta = '';
    public $fechaInicio = '';
    public $fechaFin = '';
    public $busquedaCuenta = '';



    public function render()
    {
        $cuentas = collect();
        try {
            $cuentas = Cuenta::orderBy('Codigo_cuenta')
            ->where('Nivel', '>=' , ($this->nivel != '' ? $this->nivel : 1 ))
            ->when($this->busquedaCuenta, function ($query) {
                $query->where(function($q) {
                    $q->where('Descripcion_cuenta', 'like', '%' . $this->busquedaCuenta . '%')
                    ->orWhere('Codigo_cuenta', 'like', '%' . $this->busquedaCuenta . '%');
                });
            })
            ->get();
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en reportes Libro mayor: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
        
        return view('livewire.libro-mayor', compact('cuentas'));
    }

    public function save() {

    }


    public function change(){
    }

    public function generar($formato){
        $codigoCuentaSeleccionada = Cuenta::where('id', $this->cuenta)->value('Codigo_cuenta');
        $subtituloFechas = "Mayor del " . 
            Carbon::parse($this->fechaInicio)
            ->locale('es')
            ->translatedFormat('d/F/Y') . " al " . 
            Carbon::parse($this->fechaFin)
            ->locale('es')
            ->translatedFormat('d/F/Y');
        $fecha = Carbon::now()->format('d/m/Y');
        $hora = Carbon::now()->format('h:i A');
        $params = "FechaInicio;{$this->fechaInicio},FechaFin;{$this->fechaFin},Cuenta;{$codigoCuentaSeleccionada},Nivel;{$this->nivel},Fecha;{$fecha},Hora;{$hora}, SubtituloFechas;{$subtituloFechas}&formato={$formato}";
        $this->dispatch('descargar', Params: $params);
    
    }
}
