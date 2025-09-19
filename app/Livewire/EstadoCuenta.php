<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use Log;

class EstadoCuenta extends Component
{
    #[Validate('required', message: 'Cuenta requerida')]
    public ?string $cuenta = null; 
    
    #[Validate('required', message: 'Fecha de inicio requerida')]
    public $fechaInicio = '';

    #[Validate('required', message: 'Fecha de fin requerida')]
    public $fechaFin = '';

    public $filtroDescripcion = '';

    public $meses = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

    public function render()
    {
        try {
            $cuentas = Cuenta::orderBy('Codigo_cuenta')
            ->whereIn('Nivel', [6, 9])
            ->when($this->filtroDescripcion, function ($query) {
                $query->where(function($q) {
                    $q->where('Descripcion_cuenta', 'like', '%' . $this->filtroDescripcion . '%')
                    ->orWhere('Codigo_cuenta', 'like', '%' . $this->filtroDescripcion . '%');
                });
            })
            ->get();
            return view('livewire.estado-cuenta', ['cuentas' => $cuentas]);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al cargar cuentas en reportes Estado de cuenta: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function generarEstadoCuenta()
    {
        try {
            $this->validate();
            $cuenta = Cuenta::find($this->cuenta);
            $this->dispatch(
                'generarReporteJasper',
                cuenta: $cuenta->Codigo_cuenta,
                descripcionCuenta: $cuenta->Descripcion_cuenta,
                fechaInicio: $this->fechaInicio,
                fechaFin: $this->fechaFin,
            );        
        } catch (\Illuminate\Validation\ValidationException $e) {
             $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'warning', tiempo: 3000);
        }
    }
}
