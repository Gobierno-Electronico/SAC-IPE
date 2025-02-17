<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaClasificadorEgreso extends Model
{
    use HasFactory;
    protected $table = 'cuenta_clasificadores_egreso';
    public $timestamps = false;

    protected $fillable = [
        'codigoCuenta',
        'CTG',
        'CP',
        'COG',
        'CFG',
        'CA'
    ];
}
