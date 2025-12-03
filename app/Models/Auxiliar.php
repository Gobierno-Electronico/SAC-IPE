<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Auxiliar extends Model
{
    protected $table = 'auxiliares';

    protected $fillable = [
        'codigo_cuenta',
        'descripcion_cuenta',
        'mes',
        'total',
        'anio'
    ];
}
