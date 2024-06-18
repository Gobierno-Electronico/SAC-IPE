<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasificadorRubroIngreso extends Model
{
    use HasFactory;

    protected $table = 'clasificador_rubro_ingreso';
    
    protected $fillable = [
        'Codificacion_rubro_ingreso',
        'Nombre',
        'Cuenta_contable',
        'Cuenta_registro'
    ];
}
