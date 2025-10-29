<div>
    <div wire:loading.delay.long>
        <div
            style='display: flex; justify-content: center; align-items: center; background-color: black; position: fixed; top: 0px; left: 0px; z-index: 9999; width: 100%; height: 100%; opacity: .75'>
            <div class="la-timer la-2x">
                <div></div>

            </div>
        </div>
    </div>
    <div class="pb-4 pt-3 h-auto">
        <div class="d-flex flex-row">
            {{-- <input type="text" class="input_busqueda rounded-1 shadow-sm border-0 w-25" placeholder='Buscar...'
                wire:model.live="searchTerm"> --}}
             <div class="d-flex flex-column me-3" style="flex: 0 0 15%;">
                <label for="fechaInicio" class="fw-bold mb-1">Fecha inicial</label>
                <input type="date" id="fechaInicio" name="fechaInicio"
                    class="form-control rounded-1 shadow-sm p-2" wire:model.live="fechaInicio">
            </div>

            <div class="d-flex flex-column" style="flex: 0 0 15%;">
                <label for="fechaFin" class="fw-bold mb-1">Fecha final</label>
                <input type="date" id="fechaFin" name="fechaFin"
                    class="form-control rounded-1 shadow-sm p-2" wire:model.live="fechaFin">
            </div>
        </div>

    </div>


    <div class="mt-4 d-flex flex-row-reverse">
            <button id="botonGenerarPoliza" type="button"
                class="btn btn-success shadow border-1 mt-3 mt-md-0" @if ($fechaInicio == '' || $fechaFin == '') disabled @endif wire:click="generar('PDF')" >
                Generar balanza armonizada PDF
            </button>

             <button id="botonGenerarPoliza" type="button"
                class="btn btn-success shadow border-1 mt-3 me-3 mt-md-0" @if ($fechaInicio == '' || $fechaFin == '') disabled @endif wire:click="generar('X')" >
                Generar balanza armonizada EXCEL
            </button>
    </div>
</div>
