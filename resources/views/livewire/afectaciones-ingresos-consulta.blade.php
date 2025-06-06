<div>
    @if ($estado == 'INGRESOS')
        <h4>Consulta de ingresos registrados</h4>
    @else
        <h4>Consulta de egresos registrados</h4>
    @endif
    <div class="row mt-4">
        <div class="row mb-3">
            <div class="d-flex flex-row gap-3 mb-3">
                <div class="w-100">
                    <label for="inputObservaciones" class="col-md-12 col-form-label">{{ __('Observación') }}</label>
                    <input value="{{ $observaciones }}" id="inputObservacionesConsulta" type="text"
                        class="form-control w-100" name="inputObservaciones" disabled>
                </div>
                <div>
                    <label for="inputObservaciones" class="col-md-12 col-form-label">{{ __('Total') }}</label>
                    <input value="{{ $total }}" type="text" class="form-control" name="inputObservaciones"
                        disabled>
                </div>
            </div>
            <div class="mt-3">
                <livewire:afectaciones-ingresos-consulta-table :$numeroEvento :$numeroPoliza :$estadoOriginal :$estado
                    :$tipo :$totalPrevio :$total/>
            </div>

        </div>

    </div>
</div>
