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
            <div>
                <label for="total">Total</label>
                <input type="text" name="total" id="total" class="form-control" disabled wire:model.live="total">
            </div>
        </div>
    </div>
</div>
<script>

    window.addEventListener('cambioTotal', event => {
        let parametros = event.__livewire.params
        $('#total').val(parametros.total);
        setTimeout(() => {
            formatearImporte({id: 'total'})
        }, 100);
    });

    window.addEventListener('llenarFormulario', event => {
        let parametros = event.__livewire.params
        $("#selectCuentaContable option:contains('" + parametros.cuenta + "')").prop("selected", true);
        $("#selectAreaResponsable option:contains('" + parametros.area + "')").prop("selected", true);
        $("#selectMes option:contains('" + parametros.mes + "')").prop("selected", true);
        $('#inputImporte').val(parametros.importe);
        $('#inputSolvenciaPresupuestal').val(parametros.solvenciaPresupuestal);
        $('#agregarIVA').val(parametros.agregarIVA);
        setTimeout(() => {
            formatearImporte({id: 'inputImporte'})
            formatearImporte({id: 'inputSolvenciaPresupuestal'})
        }, 100);
    });

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

</script>
