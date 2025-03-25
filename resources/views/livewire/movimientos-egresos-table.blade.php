<div>
    @if ($consultarRegistro)
    <div>
        <h4>Resumen de movimientos de egresos</h4>

        <div class="row mt-4">
            <div class="row mb-3">
                <div class="d-flex flex-row gap-3 mb-3">
                    <div class="w-100">
                        <label for="inputObservaciones"
                            class="col-md-12 col-form-label">{{ __('Observación') }}</label>
                        <input value="{{ $descripcion }}" id="inputObservacionesConsulta" type="text"
                            class="form-control w-100" name="inputObservaciones" disabled>
                    </div>
                    <div>
                        <label for="inputObservaciones" class="col-md-12 col-form-label">{{ __('Total') }}</label>
                        <input value="{{ $total }}" type="text" class="form-control" name="inputAumentado"
                            disabled>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <livewire:egresos-form-consulta-table :$numeroPoliza :$numeroEvento :$total
        :$tipoMovimiento urlFinalizar="/movimientos-egresos" :$numeroPolizaRemanente tipoPoliza="E"
        :$categoriaModulo :$categoriaRemanente/>

    @else

        <div wire:loading>
            <div
                style='display: flex; justify-content: center; align-items: center; background-color: black; position: fixed; top: 0px; left: 0px; z-index: 9999; width: 100%; height: 100%; opacity: .75'>
                <div class="la-timer la-2x">
                    <div></div>

                </div>
            </div>
        </div>
        <div class="pb-4 pt-3 h-auto">
            <div class="d-flex flex-row">
                <input type="text" class="input_busqueda rounded-1 shadow-sm border-0 w-25 me-3" placeholder='Buscar...'
                    wire:model.live="searchTerm">
                <div>
                    <select name="selectCapitulos" id="selectCapitulos" class="form-select" wire:model="capituloSeleccionado" wire:change="actualizarFiltros">
                        <option value="" selected>Filtrar capítulo...</option>
                        <option value="1">Capítulo 1000</option>
                        <option value="2y3">Capítulo 2000</option>
                        <option value="2y3">Capítulo 3000</option>
                        <option value="4">Capítulo 4000</option>
                        <option value="5">Capítulo 5000</option>

                    </select>
                </div>
                <div class="ms-3">
                    <select name="selectEventos" id="selectEventos" class="form-select" wire:model="eventoSeleccionado" wire:change="actualizarFiltros">
                        <option value="" selected>Filtrar evento...</option>
                        @foreach ($eventos as $evento => $descripcion)
                            <option value="{{ $evento }}">
                                {{ $evento }} - {{ $descripcion }}
                            </option>
                        @endforeach
                    </select>
                    
                </div>
            </div>
        </div>

        <div class="d-flex flex-column gap-3">
            <div class="shadow rounded">
                <div class="table-responsive">

                    <table class="table small text-gray-500">
                        <thead class="text-gray-700 text-uppercase bg-light">
                            <tr>
                                @foreach ($this->columns() as $column)
                                    <th wire:click="sort('{{ $column->key }}')">
                                        <div class="py-2 px-3 d-flex align-items-center">
                                            <a class=" text-black text-decoration-none" href="#"> {{ $column->label }}
                                            </a>
                                            @if ($sortBy === $column->key)
                                                @if ($sortDirection === 'asc')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="flecha_orden"
                                                        viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="flecha_orden"
                                                        viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            @endif
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->data() as $row)
                                <tr class=" hover:bg-light">
                                    @foreach ($this->columns() as $column)
                                        <td class=" px-4 align-middle cursor-pointer">
                                            <x-dynamic-component :component="$column->component" :value="$row[$column->key]">
                                            </x-dynamic-component>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
            {{ $this->data()->links() }}
        </div>
    @endif
</div>
<script>
    window.addEventListener('update', event => {
        console.log("update-item")

        setTimeout(() => {
            $('[data-toggle="tooltip"]').tooltip('dispose').tooltip();
            console.log($('[data-toggle="tooltip"]'))
        }, 1500);

    });
</script>
