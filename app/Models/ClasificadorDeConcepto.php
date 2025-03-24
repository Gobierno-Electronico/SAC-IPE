<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasificadorDeConcepto extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_clasificador',
        'descripcion_clasificador',
        'nivel',
        'clasificador_padre',
        'estado'
    ];
}
