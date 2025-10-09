<div class="d-flex flex-column gap-3">
    <x-modal value="borrarMatriz" mensajeBoton="Confirmar" accion="borrar()" titulo="Confirmar acción">
        ¿Está seguro(a) que deseas borrar la matriz?
        Una vez que se borre deberá realizar el proceso de carga nuevamente.
    </x-modal>

    <div class="col-5 mb-4">
        <label for="inputCategoriaMatriz" class="form-label">Tipo de matriz</label>
        <select name="inputCategoriaMatriz" id="inputCategoriaMatriz" class="form-select" wire:model.live="tipoMatriz">
            <option value="" selected disabled>Selecciona un tipo...</option>
            <option value="INGRESOS DEVENGADO">Ingresos devengado</option>
            <option value="INGRESOS DEVENGADO CON IMPUESTO AL VALOR AGREGADO">Ingresos devengado con IVA</option>
            <option value="INGRESOS RECAUDADO">Ingresos recaudado</option>
            <option value="INGRESOS RECAUDADO PREVIAMENTE REGISTRADOS POR CLASIFICAR">Ingresos recaudado por clasificar
            </option>
            <option value="INGRESOS DEVENGADO-RECAUDADO SIMULTANEO">Ingresos devengado-recaudado simultáneo</option>
            <option value="GASTOS DEVENGADO">Gastos devengado</option>
            <option value="GASTOS PAGADO">Gastos pagado</option>
        </select>
    </div>

    @if ($tipoMatriz)
        <div class="shadow rounded">
            <div class="table-responsive">
                <table class="table small text-gray-500">
                    <thead class="text-gray-700 text-uppercase bg-light">
                        <tr>
                            @foreach ($this->columns() as $column)
                                <th wire:click="sort('{{ $column->key }}')">
                                    <div class="py-2 px-3 d-flex align-items-center">
                                        <span class="text-black">{{ $column->label }}</span>
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
                        @forelse ($datos as $row)
                            <tr class="hover:bg-light">
                                @foreach ($this->columns() as $column)
                                    <td class="px-4 align-middle">{{ $row[$column->key] ?? '' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($this->columns()) }}" class="text-center py-4 text-muted">
                                    No hay datos disponibles.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $datos->links() }}
        </div>
        <div class="mt-4 d-flex justify-content-between">
            <button @if ($tipoMatriz == '') disabled @endif id="borrarMatriz" type="button"
                class="btn btn-danger shadow border-1 mt-3 mt-md-0" data-bs-toggle="modal"
                data-bs-target="#confirmModalborrarMatriz">
                Borrar matriz
            </button>
            <div>
                <button id="botonGenerarPoliza" onclick="generarReporte(this)" type="button"
                    class="btn btn-success shadow border-1 mt-3 mt-md-0"
                    @if ($tipoMatriz == '') disabled @endif>
                    Generar reporte
                </button>
            </div>
        </div>
    @endif
</div>
