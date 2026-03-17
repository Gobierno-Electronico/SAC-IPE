<div class="container mt-5">

    <div class="col-2">
        <label for="inputCategoriaMatriz" class="form-label mt-3">Tipo de matriz</label>
        <select name="inputCategoriaMatriz" id="inputCategoriaMatriz" class="form-select" wire:model="tipoMatriz">
            <option value="" selected disabled>Selecciona un tipo...</option>
            <option value="INGRESOS DEVENGADO">Ingresos devengado</option>
            <option value="INGRESOS DEVENGADO CON IMPUESTO AL VALOR AGREGADO">Ingresos devengado con impuesto al valor agregado</option>
            <option value="INGRESOS RECAUDADO">Ingresos recaudado</option>
            <option value="INGRESOS RECAUDADO PREVIAMENTE REGISTRADOS POR CLASIFICAR">Ingresos recaudado previamente registrados por clasificar</option>
            <option value="INGRESOS DEVENGADO-RECAUDADO SIMULTANEO">Ingresos devengado-recaudado simultáneo</option>
            <option value="GASTOS DEVENGADO">Gastos devengado</option>
            <option value="GASTOS PAGADO">Gastos pagado</option>
        </select>
    </div>

    <div class="mt-5">
        <input class="form-control" type="file" accept=".xlsx" id="archivo" wire:model="archivo">
        <div wire:loading wire:target="archivo" class="mt-2">
            <p class="fw-bold h5">
                Subiendo archivo, por favor espera...
            </p>
        </div>
    </div>
    <div class="mt-5 d-flex justify-content-end">
        <button wire:click="cargarMatriz" class="btn btn_primario shadow border-0" id="importarBoton"
            wire:loading.attr="disabled" wire:target="archivo" onclick="mostrarCarga()">
            Cargar matriz
        </button>

    </div>
</div>
<script>
    function mostrarCarga() {
        $('#loadingScreen').prop('hidden', false);
    }

    window.addEventListener('esconderCargando', event => {
        esconderCargando()
    })

    function esconderCargando() {
        $('#loadingScreen').prop('hidden', true);
        toastr.clear();
    }
</script>
