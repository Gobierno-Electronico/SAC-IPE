@props(['value', 'state'])

<div>
{{--     <x-acciones.editar :value="$value" mensaje="Editar cuenta"></x-acciones.editar> --}}
    @if ($state == 1)
        <x-acciones.toggleOn :value="$value" mensaje="Desactivar cuenta"></x-acciones.toggleOn>
    @else
        <x-acciones.toggleOff :value="$value" mensaje="Activar cuenta"></x-acciones.toggleOff>
    @endif
    <x-modal :value="$value" mensajeBoton="Desactivar" accion="changeState({{ $value }})" titulo="Confirmar acción">¿Estás seguro de que quieres desactivar este elemento?</x-modal>
</div>
<script>
    // inicializa los tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
