<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Bitacora;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class BitacoraController extends Controller
{   
    

     // guarda información del proceso que ejecuta cada usuario, para saber quién hace qué
    public function bitacora($nombreMetodo, $descripcionProceso, $request){
        try {
            $bitacora = new Bitacora;
            $bitacora->direccionIp = request()->ip();
            $bitacora->nombreUsuario = Auth::User()->nombre.' '.Auth::User()->apellido_paterno.' '.Auth::User()->apellido_materno;
            $bitacora->descripcionProceso = 'El usuario '. Auth::User()->nombre.' '.Auth::User()->apellido_paterno.' '.$descripcionProceso.' en el método: '.$nombreMetodo;
            $bitacora->updated_at = Carbon::now('America/Mexico_City');
            $bitacora->created_at = Carbon::now('America/Mexico_City');
            $bitacora->save();
        } catch (\Throwable $error) {
            Log::debug($error->getMessage());
        }
    }

    public function listarBitacoras(){
        return view('bitacora.lista');
    }
}
