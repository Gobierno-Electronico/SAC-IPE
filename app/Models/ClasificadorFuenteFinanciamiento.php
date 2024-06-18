<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasificadorFuenteFinanciamiento extends Model
{
    use HasFactory;

    protected $table = 'clasificador_fuente_financiamiento';

    protected $fillable = [
        'id',
        'Codificacion_fuente_financiamiento',
        'Nombre',
        'Cuenta_contable',
        'Cuenta_registro'
    ];
}
