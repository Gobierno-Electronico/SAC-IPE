<div>

    <x-modal value="borrarPresupuestoInicial" mensajeBoton="Confirmar" accion="borrar()" titulo="Confirmar acción">
        ¿Está seguro(a) que deseas cancelar el movimiento?
        Una vez que se borren deberá realizar el proceso nuevamente.
    </x-modal>

    <div wire:loading.delay.long>
        <div
            style='display: flex; justify-content: center; align-items: center; background-color: black; position: fixed; top: 0px; left: 0px; z-index: 9999; width: 100%; height: 100%; opacity: .75'>
            <div class="la-timer la-2x">
                <div></div>

            </div>
        </div>
    </div>
    @if ($estado == 'INGRESOS')
        @if (
            $selectCodigoDepartamento != '' &&
                $codigoCuentaCargo != '' &&
                ($selectCodigoDepartamento != '0' && $codigoCuentaCargo != '0'))
            <div class="d-flex flex-column gap-3">
                <div class="shadow rounded">
                    <div class="table-responsive">
                        <table class="table small text-gray-500 table-hover" id="tabla-importes">
                            <thead class="text-gray-700 text-uppercase thead-light">
                                <tr>
                                    @foreach ($this->columns() as $column)
                                        <th>
                                            <div class="py-2 px-3 d-flex align-items-center">
                                                <a class=" text-black text-decoration-none"> {{ $column->label }}
                                                </a>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->data() as $row)
                                    <tr class=" hover:bg-light" wire:click="seleccionarMes('{{ $row->mes }}')"
                                        id="{{ $row->mes }}">
                                        @foreach ($this->columns() as $column)
                                            <td class=" px-4 align-middle cursor-pointer" href='#'>
                                                <x-dynamic-component :component="$column->component" :value="$row->{$column->key}">
                                                </x-dynamic-component>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6 w-25">
                    <label for="inputMesSeleccionado"
                        class="col-md-12 col-form-label">{{ __('Mes seleccionado') }}</label>
                    <input id="inputMesSeleccionado" type="text" class="form-control" name="inputMesSeleccionado"
                        wire:model.live="mesSeleccionado" disabled>
                </div>

                <div class="col-md-6 w-25">
                    <label for="inputImporte" class="col-md-12 col-form-label">{{ __('Importe') }}</label>
                    <input placeholder="Ingrese el importe del movimiento" id="inputImporte" type="text"
                        onchange="formatearImporte(this)" onkeyup="keyPress(event, this)" class="form-control" name="inputImporte" wire:model="importe">
                </div>
                <div class="col-md-6 position-relative" style="width: 25%;">
                    <button
                        class="btn btn-success shadow border-1 mt-3 mt-md-0 position-absolute bottom-0 start-50 translate-middle-x"
                        style="width: 60%;" wire:click="agregar" id="agregar">Agregar</button>
                </div>
                <div class="col-md-6 w-25">
                    <label for="inputTotal" class="col-md-12 col-form-label">{{ __('Total') }}</label>
                    <input placeholder="0" id="inputTotal" type="text" disabled onchange="formatearImporte(this)"
                        class="form-control" name="inputTotal" wire:model="total">
                </div>
            </div>
        @endif
    @else
    @endif
    @if (
        $selectCodigoDepartamento != '' &&
            $codigoCuentaCargoEgreso != '' &&
            ($selectCodigoDepartamento != '0' && $codigoCuentaCargoEgreso != '0'))
        <div class="d-flex flex-column gap-3">
            <div class="shadow rounded">
                <div class="table-responsive">
                    <table class="table small text-gray-500 table-hover" id="tabla-importes">
                        <thead class="text-gray-700 text-uppercase thead-light">
                            <tr>
                                @foreach ($this->columns() as $column)
                                    <th>
                                        <div class="py-2 px-3 d-flex align-items-center">
                                            <a class=" text-black text-decoration-none"> {{ $column->label }}
                                            </a>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->data() as $row)
                                <tr class=" hover:bg-light" wire:click="seleccionarMes('{{ $row->mes }}')"
                                    id="{{ $row->mes }}">
                                    @foreach ($this->columns() as $column)
                                        <td class=" px-4 align-middle cursor-pointer" href='#'>
                                            <x-dynamic-component :component="$column->component" :value="$row->{$column->key}">
                                            </x-dynamic-component>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6 w-25">
                <label for="inputMesSeleccionado"
                    class="col-md-12 col-form-label">{{ __('Mes seleccionado') }}</label>
                <input id="inputMesSeleccionado" type="text" class="form-control" name="inputMesSeleccionado"
                    wire:model.live="mesSeleccionado" disabled>
            </div>

            <div class="col-md-6 w-25">
                <label for="inputImporte" class="col-md-12 col-form-label">{{ __('Importe') }}</label>
                <input placeholder="Ingrese el importe del movimiento" id="inputImporte" type="text"
                    onchange="formatearImporte(this)" onkeyup="keyPress(event,this)" class="form-control" name="inputImporte" wire:model="importe">
            </div>
            <div class="col-md-6 position-relative" style="width: 25%;">
                <button
                    class="btn btn-success shadow border-1 mt-3 mt-md-0 position-absolute bottom-0 start-50 translate-middle-x"
                    style="width: 60%;" wire:click="agregar" id="agregar">Agregar</button>
            </div>
            <div class="col-md-6 w-25">
                <label for="inputTotal" class="col-md-12 col-form-label">{{ __('Total') }}</label>
                <input placeholder="0" id="inputTotal" type="text" disabled onchange="formatearImporte(this)"
                    class="form-control" name="inputTotal" wire:model="total">
            </div>
        </div>
    @endif
    <div>
        <div class="d-flex justify-content-end  mt-5">
            <button class="btn btn-success shadow border-1 mt-3 mx-3" wire:click="agregarRegistro" id="registroExtra"
                @if ($total == 0) disabled @endif>Agregar registro</button>
            <button class="btn btn-success shadow border-1 mt-3" wire:click="finalizarRegistros" id="registroExtra"
                @empty($registros) disabled @endempty>Finalizar registros</button>
            @if ($totalPrevio > 0 && $totalProceso !== $totalPrevio)
                <button id="borrarPresupuesto" type="button"
                    class="btn btn-danger shadow border-1 mt-3 ms-3" data-bs-toggle="modal"
                    data-bs-target="#confirmModalborrarPresupuestoInicial">
                    Borrar movimiento
                </button>
            @endif
        </div>
    </div>

</div>
