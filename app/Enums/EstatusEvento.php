<?php

namespace App\Enums;

enum EstatusEvento: int
{
    case FINALIZADO = 0;
    case ACTIVO = 1;
    case CANCELADO = 9;
}