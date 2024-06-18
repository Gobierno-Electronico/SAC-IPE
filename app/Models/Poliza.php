<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poliza extends Model
{
    use HasFactory;

    protected $fillable = [
        'area',
        'tipo_poliza',
        'numero_poliza',
        'fecha',
        'cuenta',
        'concepto',
        'total',
        'descripcion',
        'evento',
        'asiento',
        'mes',
        'tipo_interaccion',
        'validado',
        'categoria'
    ];

    // protected $casts = [
    //     'tipo_interaccion' => TipoInteraccionEnum::class
    // ]; 
}
