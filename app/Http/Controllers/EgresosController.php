<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;


class EgresosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function capitulo1Comprometido(){
        return view('egresos.egresos-capitulo1-comprometido');
    }

    public function capitulo1Devengado(){
        return view('egresos.egresos-capitulo1-devengado');
    }

    public function capitulo1DevengadoCarga(){
        return view('egresos.egresos-capitulo1-devengadoCarga');
    }

    public function capitulo1Ejercido(){
        return view('egresos.egresos-capitulo1-ejercido');
    }
    public function capitulo1Pagado(){
        return view('egresos.egresos-capitulo1-pagado');
    }

    public function capitulo2y3Comprometido()
    {
        return view('egresos.egresos-capitulo2y3-comprometido');
    }

    public function capitulo2y3Devengado()
    {
        return view('egresos.egresos-capitulo2y3-devengado');
    }

    public function capitulo2y3Ejercido()
    {
        return view('egresos.egresos-capitulo2y3-ejercido');
    }

    public function capitulo2y3Pagado()
    {
        return view('egresos.egresos-capitulo2y3-pagado');
    }

    public function capitulo4Comprometido()
    {
        return view('egresos.egresos-capitulo4-comprometido');
    }

    public function capitulo4Devengado()
    {
        return view('egresos.egresos-capitulo4-devengado');
    }

    public function capitulo4Ejercido()
    {
        return view('egresos.egresos-capitulo4-ejercido');
    }

    public function capitulo4Pagado()
    {
        return view('egresos.egresos-capitulo4-pagado');
    }

    public function capitulo5Comprometido()
    {
        return view('egresos.egresos-capitulo5-comprometido');
    }

    public function capitulo5Devengado()
    {
        return view('egresos.egresos-capitulo5-devengado');
    }


    public function capitulo5Ejercido()
    {
        return view('egresos.egresos-capitulo5-ejercido');
    }

    public function capitulo5Pagado()
    {
        return view('egresos.egresos-capitulo5-pagado');
    }

    public function consultarMovimientos(){
        return view ('egresos.movimientos-egresos');
    }
}
