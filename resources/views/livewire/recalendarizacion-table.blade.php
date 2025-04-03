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
        <div class="d-flex justify-content-end" style="margin-right: 10rem !important;">
            <div class="me-5">
                <label for="aumentado">Total aumentado</label>
                <input type="text" name="aumentado" id="aumentado" class="form-control" disabled wire:model.live="totalAumentado">
            </div>
            <div class="">
                <label for="disminuido">Total disminuido</label>
                <input type="text" name="disminuido" id="disminuido" class="form-control" disabled wire:model.live="totalDisminuido">
            </div>
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
    window.addEventListener('cambioTotales', event => {
        let parametros = event.__livewire.params
        $('#aumentado').val(parametros.aumento);
        $('#disminuido').val(parametros.disminucion);

        setTimeout(() => {
            formatearImporte({ id: 'disminuido' });
            formatearImporte({ id: 'aumentado' });
        }, 100);
    });

    window.addEventListener('llenarFormulario', event => {
        let parametros = event.__livewire.params
        $("#selectAreaResponsable option:contains('" + parametros.areaResponsable + "')").prop("selected", true);
        $("#selectPartida option:contains('" + parametros.cog + "')").prop("selected", true);
        $("#selectMesAfectacion option:contains('" + parametros.mes + "')").prop("selected", true);
        $("#inputAfectacion option:contains('" + parametros.afectacion + "')").prop("selected", true);
        $('#inputSolvencia').val(parametros.solvencia);
        $('#inputImporte').val(parametros.importe);
        $("#inputTipoMovimiento option:contains('" + parametros.movimiento + "')").prop("selected", true);
        setTimeout(() => {
            formatearImporte({id: 'inputImporte'})
            formatearImporte({id: 'inputSolvencia'})
        }, 100)
    });

    function formatearImporte(obj) {
        var amount = $('#' + obj.id).val().replace(/[^0-9.]/g, '');
        amount = parseFloat(amount);
        if (!isNaN(amount)) {
            var formattedAmount = amount.toLocaleString('es-MX', {
                style: 'currency',
                currency: 'MXN',
                minimumFractionDigits: 2,
            });
            $('#' + obj.id).val(formattedAmount);
            console.log("Ejecuta: "+obj);
        } else {
            toastr.warning('Ingrese valores numéricos en el campo de importe');
            $('#' + obj.id).val('');
        }
    }

</script>
