<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class PrestamosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function capitulo7CompromisoDevengadoPrestamosIniciales(){
        return view("prestamos.prestamos-otorgamiento-compromiso-devengado-prestamosIniciales");
    }

    public function capitulo7EjercidoPagadoRecaudadoPrestamosIniciales(){
        return view("");
    }

    public function capitulo7RecaudadoPrestamosIniciales(){
        return view("");
    }
}