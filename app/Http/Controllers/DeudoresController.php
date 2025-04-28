<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class DeudoresController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function otorgamientoAnticipo()
    {
        return view('deudores.deudores-ortorgamiento-anticipo');
    }

 /*    public function reintegroAnticipo()
    {
        return view('deudores.deudores-reintegro-anticipo');
    }

    public function comprobacionAnticipo()
    {
        return view('deudores.deudores-comprobacion-anticipo');
    } */
}