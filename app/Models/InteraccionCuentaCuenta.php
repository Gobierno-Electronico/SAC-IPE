<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InteraccionCuentaCuenta extends Model
{
    use HasFactory;


    protected $fillable = [
        'id_interaccion_concepto_cuenta_1',
        'id_interaccion_concepto_cuenta_2'
    ];
}
