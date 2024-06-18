<?php

namespace App\Models;

use App\Enums\TipoInteraccionEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InteraccionCuentaConcepto extends Model
{
    use HasFactory;

    protected $fillable = [
        'concepto_id',
        'cuenta_id',
        'clasificador_de_concepto_id',
        'tipo_interaccion'
    ];

    protected $casts = [
    ]; 
}
