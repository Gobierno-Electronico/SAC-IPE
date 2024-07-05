<?php

namespace App\Livewire;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Cuenta;


class CobroEspecieForm extends Component
{
    public $selectCodigoArea;
    public function render(){
        $cuentas = Cuenta::join('interaccion_cuenta_conceptos', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
        ->whereIn('interaccion_cuenta_conceptos.concepto_id', [33])->where('interaccion_cuenta_conceptos.tipo_interaccion', '=', 'Presupuestal - Abono')
        ->where('cuentas.Descripcion_cuenta', 'LIKE', '%(Recaudado)%')->orderBy('cuentas.Codigo_cuenta')->get();
        return view('livewire.cobro-especie-form', ['cuentas' => $cuentas]);
    }
}
