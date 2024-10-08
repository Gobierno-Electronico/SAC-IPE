@props(['value', 'state'])

<div>
    <x-acciones.verMovimiento :value="$value" mensaje="Ver movimiento"></x-acciones.verMovimiento>

</div>
<script>
    // inicializa los tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>