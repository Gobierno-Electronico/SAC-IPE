<?php

namespace App\Livewire;

use App\Clases\Column;
use App\Models\ClasificadorDeConcepto;
use App\Models\Concepto;
use App\Models\Cuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\InteraccionCuentaCuenta;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class GuiaContabilizadoraCuentasTable extends Tabla
{
    public $perPage = 10;

    public $sortBy = '';

    public $cuentaSeleccionada = '';

    public $clasificadorSeleccionado = '';

    public $conceptoSeleccionado = '';

    public $searchBy = ['Codigo_cuenta', 'Descripcion_cuenta'];

    
    public function query(): Builder
    {
        return Cuenta::query();
    }

    public function columns(): array
    {
        if ($this->clasificadorSeleccionado != '' && $this->conceptoSeleccionado != '') {
            // dd($this->data());
            return [
                Column::make('Codigo_cuenta', 'Código'),
                Column::make('Descripcion_cuenta', 'Descripción'),
                Column::make('tipo_interaccion', 'Tipo'),
                Column::make('id', 'Consultar cuenta relacionada')->component('acciones.seleccionCuenta'),
            ];
        } else {
            return [
                Column::make('Codigo_cuenta', 'Código'),
                Column::make('Descripcion_cuenta', 'Descripción'),
            ];
        }
    }


    public function data()
    {

            return $this
                ->query()
                ->when($this->sortBy !== '', function ($query) {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                })->search($this->searchBy, $this->searchTerm)
                ->interaccionCuenta('interaccion_cuenta_conceptos', 'cuentas.id', 'cuenta_id', 'clasificador_de_concepto_id', 'concepto_id', $this->clasificadorSeleccionado, $this->conceptoSeleccionado)
                ->paginate($this->perPage);

    }

    public function edit($value)
    {
    }

    public function changeState($value)
    {

    }

    #[On('clasificadorSeleccionado')]
    public function buscarPorClasificador($value)
    {
        $this->clasificadorSeleccionado = $value;
        // dd($value);
    }

    #[On('conceptoSeleccionado')]
    public function buscarPorConcepto($concepto)
    {
        $this->conceptoSeleccionado = $concepto;
        // dd($concepto);
    }

    #[On('cuentaSeleccionada')]
    public function consultarCuentaRelacionada($cuenta)
    {

        $interaccion = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', $cuenta)->first();
        if (! $interaccion) {
            $interaccion = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_2', $cuenta)->first();
            if(! $interaccion) dd("error");
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::find($interaccion->id_interaccion_concepto_cuenta_1);
        } else {
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::find($interaccion->id_interaccion_concepto_cuenta_2);
        }
        $cuenta = Cuenta::find($interaccionCuentaConcepto->cuenta_id);
        $cuenta->tipo_interaccion = $interaccionCuentaConcepto->tipo_interaccion;
        $this->dispatch('post-created', info: $cuenta);
        // dd(5);
    }
}
