<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;


class EgresosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function capitulo4Comprometido()
    {
        return view('egresos.egresos-capitulo4-comprometido');
    }
}
