<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresupuestoInicial extends Model
{
    use HasFactory;

    protected $fillable = [
        'area',
        'tipo',
        'anio',
        'fecha',
        'cuenta',
        'descripcion',
        'concepto',
        'total',
        'monto_enero',
        'monto_febrero',
        'monto_marzo',
        'monto_abril',
        'monto_mayo',
        'monto_junio',
        'monto_julio',
        'monto_agosto',
        'monto_septiembre',
        'monto_octubre',
        'monto_noviembre',
        'monto_diciembre',
        'validado',
        'categoria'   
    ];

}
