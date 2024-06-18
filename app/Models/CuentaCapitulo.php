<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaCapitulo extends Model
{
    use HasFactory;

    protected $fillable = [
        'cuenta_id',
        'cuenta',
        'capitulo'
    ];
}
