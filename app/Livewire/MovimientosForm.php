<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
class MovimientosForm extends Component
{

    public $area = "";
    public $cog = "";

    public function render()
    {
        return view('livewire.movimientos-form');
    }

    public function save() {
    }
    

    public function change(){
    }

}
