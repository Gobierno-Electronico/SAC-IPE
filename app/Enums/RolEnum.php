<?php

namespace App\Enums;

enum RolEnum:String
{
    case ADMINISTRADOR = 'Administrador';
    case JEFE_DEPARTAMENTO = "Jefe_Departamento";
    case JEFE_OFICINA = "Jefe_Oficina";
    case CAPTURISTA = "Capturista";
    case ANALISTA = "Analista";
    case TECNICO = "Tecnico";

    public static function valores(): array
    {
        return array_column(self::cases(), 'name', 'value');
    }
}