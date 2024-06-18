@props(['value', 'state'])

<div>
    <x-acciones.editar :value="$value" mensaje="Editar usuario"></x-acciones.editar>
    <x-acciones.contrasenia :value="$value" mensaje="Restablecer contraseña"></x-acciones.contrasenia>
    <x-acciones.eliminar :value="$value" mensaje="Eliminar usuario"></x-acciones.eliminar>

    <x-modal :value="$value" mensajeBoton="Restablecer" accion="resetPassword({{ $value }})" titulo="Confirmar acción">¿Deseas restablecer la contraseña de este usuario?</x-modal>
    <x-modal :value="$value . 'eliminar'" mensajeBoton="Eliminar" accion="deleteUser({{ $value }})" titulo="Confirmar acción">¿Deseas eliminar este usuario?</x-modal>

</div>
<script>
    // inicializa los tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
