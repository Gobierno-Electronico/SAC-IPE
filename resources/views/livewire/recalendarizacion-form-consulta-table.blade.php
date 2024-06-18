<div>
    <x-modal value="validarPresupuestoInicial" mensajeBoton="Confirmar" accion="validar()" titulo="Confirmar acción">
        ¿Está seguro(a) que todos los registros SON CORRECTOS?
        Una vez que se validen no podrá ser revertido.
    </x-modal>

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
    <div class="pb-4 pt-3 h-auto">
        <div class="d-flex flex-row">
            <input type="text" class="input_busqueda rounded-1 shadow-sm border-0 w-25" placeholder='Buscar...'
                wire:model.live="searchTerm">
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
                                        <x-dynamic-component :component="$column->component" :value="$row[$column->key]" :state="$row['Estado']"
                                            :itemId="$row['Estado']">
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
    <button id="botonFecha" value='{{ $fecha }}' hidden></button>
    <button id="botonHora" value='{{ $hora }}' hidden></button>
    <button id="botonNumeroPoliza" value='{{ $numeroPoliza }}' hidden></button>
    <button id="botonEvento" value='{{ $numeroEvento }}' hidden></button>


    <div class="mt-4">

    </div>
    <div class="mt-4 d-flex gap-3 justify-content-end">
        @if (!$validado)
            <button id="botonGenerarPoliza" onclick="generarPolizaReclasificacion(this)" type="button"
                class="btn btn-success shadow border-1 mt-3 mt-md-0">
                Visualizar póliza
            </button>
            <button @if ($validado) disabled @endif
                class="btn btn-success shadow border-1 mt-3 mt-md-0"id="validarPresupuesto" data-bs-toggle="modal"
                data-bs-target="#confirmModalvalidarPresupuestoInicial" wire:init="init()">Validar póliza</button>
            <button @if ($validado) disabled @endif id="borrarPresupuesto" type="button"
                class="btn btn-danger shadow border-1 mt-3 mt-md-0" data-bs-toggle="modal"
                data-bs-target="#confirmModalborrarPresupuestoInicial">
                Borrar movimiento
            </button>
        @else
            <button id="botonGenerarPoliza" onclick="generarPolizaReclasificacion(this)" type="button"
                class="btn btn-success shadow border-1 mt-3 mt-md-0">
                Visualizar póliza
            </button>
       
                <button class="btn btn-success shadow border-1 mt-3 mt-md-0"id="validarPresupuesto"
                    wire:click="finalizar('{{$tipo}}')">Finalizar</button>
        @endif

        <div>

        </div>
    </div>
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
