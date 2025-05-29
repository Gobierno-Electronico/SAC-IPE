@props(['value'])

@php
    use App\Enums\EstatusEvento;
@endphp

<div class="flex w-100">
    <div @class([
        'text-white rounded-3 px-2 texto_estados text-uppercase fw-bold small text-center',
        'bg-success' => $value == EstatusEvento::ACTIVO->value,
        'bg-secondary' => $value == EstatusEvento::FINALIZADO->value,
        'bg-danger' => $value == EstatusEvento::CANCELADO->value,
    ])>
        @if ($value == EstatusEvento::ACTIVO->value)
            Activo
        @elseif ($value == EstatusEvento::CANCELADO->value)
            Cancelado
        @else
            Finalizado
        @endif
    </div>
</div>
