<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function balanza()
    {
        return view('reportes.balanza');
    }

    public function mayor()
    {
        return view('reportes.mayor');
    }

    public function diario()
    {
        return view('reportes.diario');
    }

    public function estadoCuenta()
    {
        return view('reportes.estadoCuenta');
    }

    public function estadoActividades()
    {
        return view('reportes.estadoActividades');
    }

    public function estadoSituacionFinanciera()
    {
        return view('reportes.estadoSituacionFinanciera');
    }



    public function mostrarClasificadores($tipo)
    {
        $titulo = '';
        switch ($tipo) {
            case 'CA':
                $titulo = 'Clasificador Administrativo';
                break;
            case 'CP':
                $titulo = 'Clasificador Programático';
                break;

            case 'CFG':
                $titulo = 'Clasificador Funcional Gasto';
                break;

            case 'CTG':
                $titulo = 'Clasificador Tipo Gasto';
                break;

            case 'COG':
                $titulo = 'Clasificador Objeto Gasto';
                break;

            case 'CFF':
                $titulo = 'Clasificador Fuente Financiamiento';
                break;

            case 'CRI':
                $titulo = 'Clasificador Rubro Ingreso';
                break;

            default:
                $titulo = 'Clasificador Administrativo';
                break;

        }
        return view('reportes.clasificadores', compact('tipo', 'titulo'));
    }

    public function mostrarVistaCargaMatriz(){
        return view('matriz_conversion.matriz');
    }

    public function mostrarVistaConsultaMatriz(){
        return view('reportes.consultaMatrices');
    }
    public function mostrarVistaCargaFuente(){
        return view('fuente.fuente');
    }
}
