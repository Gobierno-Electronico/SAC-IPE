@props([
    'value'
])

<div>
    {{ '$' . number_format($value, 2, '.', ',') }}
</div>