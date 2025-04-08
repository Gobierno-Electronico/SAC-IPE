<div>

    <div wire:loading>
        <div
            style='display: flex; justify-content: center; align-items: center; background-color: black; position: fixed; top: 0px; left: 0px; z-index: 9999; width: 100%; height: 100%; opacity: .75'>
            <div class="la-timer la-2x">
                <div></div>

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
                                        <x-dynamic-component :component="$column->component" :value="$row[$column->key]" :itemId="$row[$column->key]">
                                        </x-dynamic-component>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $this->data()->links() }}
        </div>
        <div class="d-flex justify-content-end">
            <div class="me-3">
                <label for="totalCargo">Total Cargo</label>
                <input type="text" name="totalCargo" id="totalCargo" class="form-control" disabled wire:model.live="totalCargo">
            </div>
            <div>
                <label for="totalAbono">Total Abono</label>
                <input type="text" name="totalAbono" id="totalAbono" class="form-control" disabled wire:model.live="totalAbono">
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('cambioTotal', event => {
        let parametros = event.__livewire.params

        $('#totalCargo').val(parametros.totalCargo);
        $('#totalAbono').val(parametros.totalAbono);
        setTimeout(() => {
            formatearImporte({id: 'totalCargo'})
            formatearImporte({id: 'totalAbono'})
        }, 100);
    });
    window.addEventListener('llenarFormulario', event => {
        let parametros = event.__livewire.params
        $("#selectCuenta option:contains('" + parametros.cuenta + "')").prop("selected", true);
        $("#selectMes option:contains('" + parametros.mes + "')").prop("selected", true);
        $("#selectTipoInteraccion option:contains('" + parametros.tipoInteraccion + "')").prop("selected", true);
        $('#inputImporte').val(parametros.importe);
        setTimeout(() => {
            formatearImporte({id: 'inputImporte'})
        }, 100);
    });
</script>
