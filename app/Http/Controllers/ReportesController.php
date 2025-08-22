<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function balanza() {
        return view('reportes.balanza');
    }

    public function mayor() {
        return view('reportes.mayor');
    }

    public function diario(){
        return view('reportes.diario');
    }

    public function estadoCuenta(){
        return view('reportes.estadoCuenta');
    }
}
