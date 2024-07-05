<?php

namespace App\Livewire;
use Livewire\Component;
use Livewire\Attributes\On;

use App\Models\Cuenta;



class PagoDevolucionForm extends Component
{
    public $selectCodigoArea;
    public function render(){
        $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
        ->whereIn('interaccion_cuenta_conceptos.concepto_id', [26, 27, 28, 29])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Cargo')
        ->where('cuentas.Descripcion_cuenta', 'LIKE', '%(Recaudado)%')->orderBy('cuentas.Codigo_cuenta')->get();
        return view('livewire.pago-devolucion-form', ['cuentas' => $cuentas]);
    }
    
}
