<?php

namespace App\Enums;

enum RolEnum:String
{
    case ADMINISTRADOR = 'Administrador';
    case JEFE_DEPARTAMENTO_CONTABILIDAD = "Jefe_Departamento_Contabilidad_Presupuesto";
    case JEFE_DEPARTAMENTO_RECURSOS_FINANCIEROS = "Jefe_Departamento_Recursos_Financieros";
    case JEFE_OFICINA_CONTABILIDAD = "Jefe_Oficina_Contabilidad_general";
    case JEFE_OFICINA_CONTROL = "Jefe_Oficina_Control_Presupuestal";
    case TECNICO = 'Tecnico';
    case CAPTURISTA = "Capturista";
    case ANALISTA = "Analista";

    public static function valores(): array
    {
        return array_column(self::cases(), 'name', 'value');
    }
}