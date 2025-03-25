<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigoDepartamento extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'Codigo_completo',
        'Nombre',
        'Titular'
    ];
}
