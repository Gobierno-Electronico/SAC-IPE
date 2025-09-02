<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class IngresosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function ingresosPorClasificar(){
        return view('ingresos.ingresos-por-clasificar');
    }
    public function depositosBancos(){
        return view('ingresos.depositos-bancos');
    }

    public function devengadoRecaudado(){
        return view('ingresos.devengado-prev-recaudado');
    }

    public function devengadoRecaudadoEjerciciosAnteriores(){
        return view('ingresos.devengado-prev-recaudado-ejercicios-anteriores');
    }

    public function ingresosDevengado(){
        return view('ingresos.ingresos-devengado');
    }

    public function ingresosRecaudado(){
        return view('ingresos.ingresos-recaudado');
    }

    public function bancos(){
        return view('ingresos.seleccion-bancos');
    }

    public function autorizacionDevolucion(){
        return view('ingresos.autorizacion-devolucion');
    }

    public function pagoDevolucion(){
        return view('ingresos.pago-devolucion');
    }

    public function autorizacionReintegro(){
        return view('ingresos.autorizacion-reintegro');
    }

    public function pagoReintegro(){
        return view('ingresos.pago-reintegro');
    }

    public function cobroEspecie(){
        return view('ingresos.cobro-especie');
    }

    public function devolucionEspecie(){
        return view ('ingresos.devolucion-especie');
    }
    public function consultarMovimientos(){
        return view ('ingresos.movimientos-ingresos');
    }
}
