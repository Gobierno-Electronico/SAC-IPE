<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasificadorFuncionalGasto extends Model
{
    use HasFactory;

    protected $table = 'clasificador_funcional_gasto';
    
    protected $fillable = [
        'codigo',
        'nombre'
    ];
}
