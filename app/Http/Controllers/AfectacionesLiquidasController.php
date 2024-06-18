<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AfectacionesLiquidasController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }
    public function afectacionesLiquidas() {
        return view('presupuestos.afectaciones_liquidas.afectaciones-liquidas');
    }
}
