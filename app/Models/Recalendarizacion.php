<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recalendarizacion extends Model
{
    use HasFactory;
    protected $table = 'recalendarizacion';

    protected $fillable = [
        'id',
        'area',
        'cog',
        'mes',
        'afectacion',
        'inicial',
        'aumentado',
        'disminuido',
        'final',
        'evento',
        'tipo_poliza',
        'numero_poliza'
    ];
}
