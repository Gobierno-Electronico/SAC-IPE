<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasificacionProgramatica extends Model
{
    use HasFactory;

    protected $table = 'clasificacion_programatica';

    protected $fillable = [
        'codigo',
        'nombre'
    ];
}
