<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasificadorTipoGasto extends Model
{
    use HasFactory;

    protected $table = 'clasificador_tipo_gasto';

    protected $fillable = [
        'codigo',
        'nombre'
    ];
}
