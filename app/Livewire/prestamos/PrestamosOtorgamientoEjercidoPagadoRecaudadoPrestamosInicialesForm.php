<?php

namespace App\Livewire\prestamos;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;

class PrestamosOtorgamientoEjercidoPagadoRecaudadoPrestamosInicialesForm extends Component
{
    #[Validate('required', message: 'Área solicitante requerida')]
    public $selectCodigoArea = "";

    #[Validate('required', message: 'Observaciones requeridas')]
    public $observaciones = "";

    #[Validate('required', message: 'Fecha de afectaciónrequerida')]
    public $fechaAfectacion = "";

    #[Validate('required', message: 'Área responsable requerida')]
    public $selectCodigoAreaResponsable = "";

    #[Validate('required', message: 'Cuenta requerida')]
    public $cuenta = "";

    #[Validate('required', message: 'Mes requerido')]
    public $mes = "";

    #[Validate('required', message: 'Importe requerido')]
    public $importe = "";

    public $PTTOEjecutar = 0;
    public $consultarRegistro = false;
    public function render()
    {
        return view('livewire.prestamos.prestamos-otorgamiento-ejercido-pagado-recaudado-prestamosIniciales-form');
    }
}