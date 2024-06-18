<?php

namespace App\Enums;

enum RolEnum:String
{
    case ADMINISTRADOR = 'Administrador';
    case GENERAL = 'General';
    case TECNICO = 'Tecnico';
    case JEFE_OFICINA = "Jefe_Oficina";
    case CAPTURISTA = "Capturista";
    case ANALISTA = "Analista";

    public static function valores(): array
    {
        return array_column(self::cases(), 'name', 'value');
    }
}