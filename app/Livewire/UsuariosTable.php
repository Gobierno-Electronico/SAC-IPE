<?php

namespace App\Livewire;

use App\Clases\Column;
use App\Models\User;
use Livewire\Component;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\BitacoraController;


class UsuariosTable extends Tabla
{
    public $perPage = 10;

    public $sortBy = '';

    public $searchBy = ['nombre', 'numero_de_personal'];

    public function query(): Builder
    {
        return User::query();
    }

    public function columns(): array
    {
        return [
            Column::make('nombre', 'Nombre'),
            Column::make('apellido_paterno', 'Apellido paterno'),
            Column::make('apellido_materno', 'Apellido materno'),
            Column::make('rol', 'Rol'),
            Column::make('usuario', 'Usuario'),
            Column::make('numero_de_personal', 'Número de personal'),
            Column::make('id', 'Acciones')->component('columns.accionesUsuarios'),
        ];
    }

    public function edit($value) {
        return redirect('/usuarios/editar/' . $value);
    }

    public function changeState($value) {
        // $cuenta = Cuenta::find($value);

        // if ($cuenta) {
        //     $cuenta->Estado = !$cuenta->Estado;
        //     $cuenta->save();

        // } else {
        //     session()->flash('message', 'Intente de nuevo');
        //     session()->flash('message_type','error');
        //     return redirect('/cuentas');
        // }
    }

    public function resetPassword($id){
        $usuario =  User::find($id);
        if($usuario){
            $usuario->password = bcrypt('123456789');
            $usuario->password_restaurada = true;
            $usuario->save();
            session()->flash('message', 'Contraseña restablecida');
            session()->flash('message_type','success');
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('resetPassword', 'reinició o intentó reiniciar la contraseña del usuario ' . $usuario->usuario, request());
            return redirect('/usuarios');
        }
        else{
            session()->flash('message', 'Usuario no encontrado');
            session()->flash('message_type','error');
            return redirect('/usuarios');
        }
    }

    public function deleteUser($id) {
        $usuario =  User::find($id);
        if($usuario && $usuario != auth()->user()){
            $usuario->delete();
            session()->flash('message', 'Usuario eliminado');
            session()->flash('message_type','success');
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('deleteUser', 'borró o intentó borrar al usuario ' . $usuario->usuario, request());
            return redirect('/usuarios');
        }
        else{
            if($usuario && $usuario == auth()->user()){
                session()->flash('message', 'No puedes eliminar tu propio usuario');
                session()->flash('message_type','error');
                return redirect('/usuarios');
            } else {
                session()->flash('message', 'Usuario no encontrado');
                session()->flash('message_type','error');
                return redirect('/usuarios');
            }
        }
    }
}
