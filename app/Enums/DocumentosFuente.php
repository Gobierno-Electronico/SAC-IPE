<?php

namespace App\Enums;

enum DocumentosFuente: string
{
    case MEMORANDUM = 'Memorandum';
    case ESTADO_CUENTA_BANCARIO = 'Estado de Cuenta Bancario';
    case SOLICITUD_VIATICOS = 'Solicitud de Viáticos';

}