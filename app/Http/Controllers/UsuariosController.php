<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Bitacora;
use App\Http\Controllers\BitacoraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use \Illuminate\Validation\Rule;
class UsuariosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function listaDeUsuarios() {

        return view('auth.lista');

    }

    public function editarUsuario($id) {
        $usuario =  User::find($id);
        if($usuario){
            session()->flash('usuario', $usuario);
        }
        else{
            session()->flash('message', 'Usuario no encontrado');
            session()->flash('message_type','error');
            return redirect('/usuarios');
        }
        return view('auth.editar');
    }

    public function cambiarPasswordVista(){
        return view('auth.resetPassword');
    }

    public function cambiosUsuario(Request $request){
        $requestData = request()->all();
        $validator =  Validator::make($requestData, [
            'usuario' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($requestData['id'])],
            'id' => ['required', 'numeric'],
            'numero_de_personal' => ['required', 'numeric', Rule::unique('users')->ignore($requestData['id'])],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['required', 'string', 'max:255'],
            'rol' => ['required', 'string', 'max:255']
        ]);
        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()->messages()));
            session()->flash('message', implode(" ", $errors));
            session()->flash('message_type','error');
            return back();
        }
        $formFields = $validator->getData();
        $id = $formFields['id'];
        unset($formFields['id']);
        User::find($id)->update($formFields);
        $usuariosController = new BitacoraController();
        $usuariosController->bitacora('cambiosUsuario', 'actualizó o intentó actualizar la información del usuario ' .  User::find($id)->usuario, $request);
        session()->flash('message', 'Usuario editado');
        session()->flash('message_type','success');
        return redirect('/usuarios');
    }

    public function cambiarPassword(Request $request){
        $requestData = request()->all();
        $validator =  Validator::make($requestData, [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $validator->validate();
        $id = auth()->user()->id;
        $user = User::find($id);
        $user->password = bcrypt($validator->getData()['password']);
        $user->password_restaurada = false;
        $user->save();
        $usuariosController = new BitacoraController();
        $usuariosController->bitacora('cambiarPassword', 'actualizó o intentó actualizar su contraseña', $request);
        return redirect('/');
    }

}
