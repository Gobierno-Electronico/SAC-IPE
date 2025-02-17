@props(['value', 'state'])

<div>
    <x-acciones.visualizarConsultaAmpliacionReduccion :value="$value" mensaje="Visualizar consulta"></x-acciones.visualizarConsultaAmpliacionReduccion>

</div>
<script>
    // inicializa los tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>