<div class="container mt-5">

    <div class="mt-5">
        <input class="form-control" type="file" accept=".xlsx" id="archivo" wire:model="archivo">
        <div wire:loading wire:target="archivo" class="mt-2">
            <p class="fw-bold h5">
                Subiendo archivo, por favor espera...
            </p>
        </div>
    </div>
    <div class="mt-5 d-flex justify-content-end">
        <button wire:click="actualizarDocumentosFuente" class="btn btn_primario shadow border-0" id="importarBoton"
            wire:loading.attr="disabled" wire:target="archivo" onclick="mostrarCarga()">
            Cargar fuentes
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
