<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasificadorObjetoGasto extends Model
{
    use HasFactory;

    protected $table = 'clasificador_objeto_gasto';

    protected $fillable = [
        'codigo',
        'nombre',
        'cuenta'
    ];
}
