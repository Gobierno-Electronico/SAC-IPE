<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoAnual extends Model
{
    use HasFactory;
    protected $table = 'movimientos_anuales';

    protected $fillable = [
        'id_cuenta',
        'anio',
        'monto_inicial',
        'cargo_Enero', 'abono_Enero',
        'cargo_Febrero', 'abono_Febrero',
        'cargo_Marzo', 'abono_Marzo',
        'cargo_Abril', 'abono_Abril',
        'cargo_Mayo', 'abono_Mayo',
        'cargo_Junio', 'abono_Junio',
        'cargo_Julio', 'abono_Julio',
        'cargo_Agosto', 'abono_Agosto',
        'cargo_Septiembre', 'abono_Septiembre',
        'cargo_Octubre', 'abono_Octubre',
        'cargo_Noviembre', 'abono_Noviembre',
        'cargo_Diciembre', 'abono_Diciembre',
    ];
}
