<?php

namespace App\Livewire;

use App\Clases\Column;
use App\Models\Cuenta;
use Livewire\Component;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\BitacoraController;

class CuentasTable extends Tabla
{
    public $perPage = 10;

    public $sortBy = '';


    public $searchBy = ['Codigo_cuenta', 'Descripcion_cuenta', 'Naturaleza'];

    public function query(): Builder
    {
        return Cuenta::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Codigo_cuenta', 'Codigo'),
            Column::make('Descripcion_cuenta', 'Descripción'),
            Column::make('Nivel', 'Nivel'),
            Column::make('Naturaleza', 'Naturaleza'),

            // Column::make('Clasificador_rubro_ingreso', 'CRI'),
            // Column::make('Clasificador_objeto_gasto', 'COG'),
            // Column::make('Clasificador_fuente_financiamiento', 'CFF'),
            Column::make('Cuenta_registro', 'Cuenta de registro')->component('columns.cuentaRegistro'),
            Column::make('Estado', 'Estado')->component('columns.estado'),
            Column::make('id', 'Acciones')->component('columns.acciones'),
        ];
    }

    public function edit($value)
    {
        return redirect('/cuentas/editar/' . $value);
    }

    public function changeState($value)
    {
        $cuenta = Cuenta::find($value);

        if ($cuenta) {
            $cuenta->Estado = !$cuenta->Estado;
            $cuenta->save();
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('changeState', 'modificó o intentó modificar la información de la cuenta '. $cuenta->Codigo_cuenta, request());
        } else {
            session()->flash('message', 'Intente de nuevo');
            session()->flash('message_type', 'error');
            return redirect('/cuentas');
        }
    }
}
