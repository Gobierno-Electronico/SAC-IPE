@props(['value'])

<div class="flex w-100 p-1">
    <div @class([
        'text-white rounded-3 px-2 texto_estados text-uppercase fw-bold small text-center',
        'bg-success' => $value == 1,
        'bg-secondary' => $value == 0,
    ])>
        @if ($value == 1)
            Validado
            @else
            No validado
        @endif
    </div>
</div>
