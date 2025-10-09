<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatrizConversion extends Model
{
    use HasFactory;
    
    protected $table = 'matrices_de_conversion';
    protected $primaryKey = 'id';

    protected $fillable = [
        'codigo_clasificador',
        'concepto',
        'tipo_gasto',
        'medio_recaudacion',
        'caracteristicas',
        'medio_pago',
        'codigo_cargo',
        'cuenta_cargo',
        'codigo_abono',
        'cuenta_abono',
        'categoria_matriz',
    ];

    public $timestamps = false;
}
