<?php

namespace App\Livewire\egresos;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\CodigoDepartamento;
use App\Models\Poliza;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use Carbon\Carbon;
use Log;
use DB;

class EgresosCapitulo1DevengadoCargaForm extends Component
{
    

    public function render()
    {
        
        return view('livewire.egresos.egresos-capitulo1-devengadoCarga-form');
    }

    
}