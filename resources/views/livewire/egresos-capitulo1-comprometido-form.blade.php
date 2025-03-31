<div class="mt-5">
    @if ($consultarRegistro)
        <div>
            <h4>Resumen de movimientos por registrar</h4>

            <div class="row mt-4">
                <div class="row mb-3">
                    <div class="d-flex flex-row gap-3 mb-3">
                        <div class="w-100">
                            <label for="inputObservaciones"
                                class="col-md-12 col-form-label">{{ __('Observación') }}</label>
                            <input value="{{ $observaciones }}" id="inputObservacionesConsulta" type="text"
                                class="form-control w-100" name="inputObservaciones" disabled>
                        </div>
                        <div>
                            <label for="inputObservaciones" class="col-md-12 col-form-label">{{ __('Total') }}</label>
                            <input value="{{ '$' . number_format($total, 2, '.', ',') }}" type="text"
                                class="form-control" name="inputAumentado" disabled>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <livewire:egresos-form-consulta-table :$numeroPoliza :$numeroEvento :$total
            tipoMovimiento="PolizaEgresosComprometidoCapitulo1" urlFinalizar="/capitulo1-comprometido" tipoPoliza="E"
            categoriaModulo='EGRESOS COMPROMETIDO CAPITULO 1' />
    @else
        <button id="downloadButton" hidden></button>
        <div class="container mt-5">

            <div class="col-2">
                <label for="inputFechaAfectacion" class="form-label mt-3">Fecha de afectación</label>
                <input type="date" name="inputFechaAfectacion" id="inputFechaAfectacion" class="form-control"
                    max="{{ now()->toDateString() }}" wire:model="fechaAfectacion">
            </div>

            <div class="mt-5">
                <input class="form-control" type="file" accept=".xlsx" id="archivo" wire:model="archivo">
                <div wire:loading wire:target="archivo" class="mt-2">
                    <p class="fw-bold h5">
                        Subiendo archivo, por favor espera...
                    </p>
                </div>
            </div>
            <div class="mt-5 d-flex justify-content-between">
                <button type="button" onclick="descargarPlantilla(this,'egresos')"
                    class="btn btn-success shadow border-0" id="botonPlantilla">
                    Descargar plantilla
                </button>

                <button wire:click="cargarComprometido" class="btn btn-success shadow border-0" id="importarBoton"
                    wire:loading.attr="disabled" wire:target="archivo" onclick="mostrarCarga()">
                    Cargar comprometido
                </button>

            </div>
        </div>
    @endif
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
