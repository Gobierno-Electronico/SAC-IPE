<?php

namespace App\Enums;

enum TipoInteraccionEnum:String
{
    case CONTABLE_CARGO = 'Contable - Cargo';
    case CONTABLE_ABONO = 'Contable - Abono';
    case PRESUPUESTAL_CARGO = 'Presupuestal - Cargo';
    case PRESUPUESTAL_ABONO = 'Presupuestal - Abono';

    public static function valores(): array
    {
        return array_column(self::cases(), 'name', 'value');
    }
}