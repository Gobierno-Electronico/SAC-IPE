@props(['value'])

@php
    use App\Enums\EstatusEvento;
@endphp

<div class="flex w-100">
    <div @class([
        'text-white rounded-3 px-2 texto_estados text-uppercase fw-bold small text-center',
        'bg-success' => $value == EstatusEvento::ACTIVO->value,
        'bg-secondary' => in_array($value, [EstatusEvento::FINALIZADO->value, EstatusEvento::CONCLUIDO->value]),
    ])>
        @if ($value == EstatusEvento::ACTIVO->value)
            Activo
        @elseif ($value == EstatusEvento::CONCLUIDO->value)
            Concluido
        @else
            Finalizado
        @endif
    </div>
</div>
