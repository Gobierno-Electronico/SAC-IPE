@props(['value', 'state'])

<div>
    <x-acciones.editar :value="$value" mensaje="Editar registro"></x-acciones.editar>
    <x-acciones.eliminar :value="$value" mensaje="Eliminar registro"></x-acciones.eliminar>

    <x-modal :value="$value . 'eliminar'" mensajeBoton="Eliminar" accion="eliminarRegistro({{ $value }})" titulo="Confirmar acción">¿Deseas eliminar este registro de la tabla?</x-modal>

</div>
<script>
    // inicializa los tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
