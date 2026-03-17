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
            tipoMovimiento="PolizaEgresosEjercidoCapitulo1" urlFinalizar="/capitulo1-ejercido" tipoPoliza="E"
            categoriaModulo='EGRESOS EJERCIDO CAPITULO 1' />
    @else
        <label for="inputObservacion" class="form-label mt-3">Observación</label>
        <input type="text" name="inputObservacion" id="inputObservacion" class="form-control"
            wire:model="observaciones">

        <label for="inputFechaAfectacion" class="form-label mt-3">Fecha de afectación</label>
        <input type="date" name="inputFechaAfectacion" id="inputFechaAfectacion" class="form-control"
           wire:ignore wire:model="fechaAfectacion">

        <h2 class="mt-5 mb-3">Selección de movimientos</h2>
        <div class="row">
            <div class="col-12">
                <label for="inputSeguimientoEvento" class="form-label mt-3">Número de seguimiento de evento</label>
                <select name="selectSeguimientoEvento" id="selectSeguimientoEvento" class="form-select"
                    wire:model="numeroEvento" wire:change="cambioEvento">
                    <option value="" disabled>
                        Seleccionar un evento
                    </option>
                    @foreach ($eventos as $evento => $descripcion)
                        <option value="{{ $evento }}">
                            {{ $evento }} - {{ $descripcion }}
                        </option>
                    @endforeach
                </select>
                <label for="selectDocumentoFuente" class="form-label mt-3">Documento fuente</label>
                <select name="selectDocumentoFuente" id="selectDocumentoFuente" class="form-select"
                    wire:model="documentoFuente">
                    <option value="">Selecciona una opción...</option>
                    @foreach (\App\Enums\DocumentosFuente::cases() as $documento)
                        <option value="{{ $documento->value }}">
                            {{ $documento->value === 'Memorandum' ? 'Memorándum' : $documento->value }}
                        </option>
                    @endforeach
                </select>
                <label for="inputMontoEvento" class="form-label mt-3">Monto del evento</label>
                <input type="text" name="inputMontoEvento" id="inputMontoEvento" class="form-control" disabled>
            </div>


            <div class="row mt-4">
                <div class="col">
                </div>
                <div class="col text-end">
                    <button class="btn btn_primario" wire:click="finalizarRegistros">Finalizar registros</button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    window.addEventListener('formato_importe', event => {
        let params = event.__livewire.params
        formatearImporte({
            id: params.id
        }, params.amount)
    })

    window.addEventListener('limpiar', event => {
        limpiar()
    })

    window.addEventListener('esconderCargando', event => {
        esconderCargando()
    })

    function esconderCargando() {
        $('#loadingScreen').prop('hidden', true);
        toastr.clear();
    }

    function formatearImporte(obj, amount = '') {
        amount = (amount) ? amount : $('#' + obj.id).val().replace(/[^0-9.]/g, '');
        amount = parseFloat(amount);
        if (!isNaN(amount)) {
            var formattedAmount = amount.toLocaleString('es-MX', {
                style: 'currency',
                currency: 'MXN',
                minimumFractionDigits: 2,
            });
            $('#' + obj.id).val(formattedAmount);
            console.log("Ejecuta: " + obj);
        } else {
            toastr.warning('Ingrese valores numéricos en el campo de importe');
            $('#' + obj.id).val('');
        }
    }

    function limpiar() {
        $('#inputPTTODevengado').val('');
    }
</script>
