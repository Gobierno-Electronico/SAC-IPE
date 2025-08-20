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
        <div class="pb-4 pt-3 h-auto">
            <div class="d-flex flex-row gap-3">

                <div class="d-flex flex-column" style="flex: 1 1 30%;">
                    <label for="filtroDescripcion" class="fw-bold mb-1">Buscar</label>
                    <input type="text" id="filtroDescripcion" class="form-control rounded-1 shadow-sm border-0 p-2"
                        placeholder="Escriba..." wire:model="filtroDescripcion">
                </div>

                <div class="d-flex flex-column" style="flex: 1 1 40%;">
                    <label for="cuenta" class="fw-bold mb-1">Cuenta</label>
                    <select class="form-select rounded-1 shadow-sm border-0 p-2" name="cuenta" id="cuenta"
                        wire:model="cuenta">
                        <option value="" >Seleccionar cuenta</option>
                        @foreach ($cuentas->sortBy('Codigo_cuenta') as $cuentaItem)
                            <option value="{{ $cuentaItem->cuenta_id }}">
                                {{ $cuentaItem->Codigo_cuenta . '  ' . $cuentaItem->Descripcion_cuenta }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex flex-column" style="flex: 0 0 15%;">
                    <label for="fechaInicio" class="fw-bold mb-1">Fecha inicial</label>
                    <input type="date" id="fechaInicio" name="fechaInicio"
                        class="form-control rounded-1 shadow-sm border-0 p-2" wire:model="fechaInicio">
                </div>

                <div class="d-flex flex-column" style="flex: 0 0 15%;">
                    <label for="fechaFin" class="fw-bold mb-1">Fecha final</label>
                    <input type="date" id="fechaFin" name="fechaFin"
                        class="form-control rounded-1 shadow-sm border-0 p-2" wire:model="fechaFin">
                </div>

            </div>
        </div>
    </div>



    <div class="mt-4 d-flex flex-row-reverse">
        <button id="botonGenerarEstadoCuenta" wire:click="generarEstadoCuenta" type="button"
            class="btn btn-success shadow border-1 mt-3 mt-md-0">
            Generar estado de cuenta
        </button>
    </div>
</div>
