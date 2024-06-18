<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasificacionAdministrativa extends Model
{
    use HasFactory;

    protected $table = 'clasificacion_administrativa';

    protected $fillable = [
        'codigo',
        'nombre'
    ];
}
