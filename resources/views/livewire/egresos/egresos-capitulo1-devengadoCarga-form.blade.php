<div class="mt-5">
    <button id="downloadButton" hidden></button>
    <div class="container mt-5">
        <div class="mt-5">
            <input class="form-control" type="file" accept=".xlsx" name="input-archivo" id="input-archivo"
                onchange="cambioArchivo()">
        </div>
        <div class="mt-5 d-flex justify-content-between">
            <button type="button" onclick="descargarPlantilla()" class="btn btn-success shadow border-0"
                id="botonPlantilla">
                Descargar plantilla
            </button>

            <button wire:click="cargarComprometido" class="btn btn-success shadow border-0" id="importarBoton" disabled>
                Cargar nómina
            </button>
        </div>
    </div>
    <script>

    </script>
