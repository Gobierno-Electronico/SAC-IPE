<div class="mt-5">
    @if ($consultarRegistro)
        <div>
            <h4>Resumen de movimientos por registrar</h4>

            <div class="row mt-4">
                <div class="row mb-3">
                    <div class="d-flex flex-row gap-3 mb-3">
                        <div class="w-100">
                            <label for="inputObservaciones"
                                class="col-md-12 col-form-label">{{ __('Observación') }}</label>
                            <input value="{{ $observaciones }}" id="inputObservacionesConsulta" type="text"
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
        <livewire:ingresos-form-consulta-table :$numeroPoliza :$numeroEvento :$total
            tipoMovimiento="PolizaIngresosPorClasificar" urlFinalizar="/ingresos-por-clasificar" tipoPoliza="I" />
    @else
        <div>
            <label for="selectArea" class="form-label">Área solicitante</label>
            <select class="form-select" name="selectArea" id="selectArea" wire:model.live="selectCodigoArea">
                <option value="" @if ($this->selectCodigoArea == '') selected @endif>
                    Seleccionar un área
                </option>
                @foreach (\App\Models\CodigoDepartamento::all() as $departamento)
                    @if (strlen($departamento->Codigo_completo) >= 5)
                        <option value="{{ $departamento->id }}" @if ($this->selectCodigoArea == $departamento->id) selected @endif>
                            {{ $departamento->Codigo_completo . ' ' . $departamento->Nombre }}
                        </option>
                    @endif
                @endforeach
            </select>
            <label for="inputObservacion" class="form-label mt-3">Observación</label>
            <input type="text" name="inputObservacion" id="inputObservacion" class="form-control"
                wire:model.live="observaciones">

            <label for="inputFechaRegistro" class="form-label mt-3">Fecha de registro</label>
            <input type="date" name="inputFechaRegistro" id="inputFechaRegistro" class="form-control mb-3"
                wire:model.live="fechaRegistro">

        </div>

        <h2 class="mt-5 mb-3">Selección de movimientos</h2>
        <div class="row">
            <div class="col-3">
                <label for="selectCuentaContable" class="form-label">Cuenta contable</label>
                <select name="selectCuentaContable" id="selectCuentaContable" class="form-select mb-3"
                    wire:model.live="cuenta">
                    <option value="" @if ($this->cuenta == '') selected @endif selected>Seleccionar
                        cuenta...</option>
                    @foreach ($cuentas as $cuenta)
                        <option value="{{ $cuenta->cuenta_id }}">
                            {{ $cuenta->Codigo_cuenta . ' ' . $cuenta->Descripcion_cuenta }}</option>
                    @endforeach
                </select>
                <label for="selectMes" class="form-label">Mes de afectación</label>
                <select name="selectMes" id="selectMes" class="form-select mb-3" wire:model.live="mes">
                    <option value="" @if ($this->mes == '') selected @endif>Seleccionar mes...</option>
                    @foreach (range(1, 12) as $mes)
                        @php
                            $carbonMes = \Carbon\Carbon::createFromFormat('!m', $mes);
                        @endphp
                        <option value="{{ ucfirst($carbonMes->monthName) }}">{{ ucfirst($carbonMes->monthName) }}
                        </option>
                    @endforeach
                </select>

                <label for="inputImporte" class="form-label">Importe</label>
                <input type="text" name="inputImporte" id="inputImporte" class="form-control mb-3"
                    wire:model.live="importe" onkeyup="keyPress(event, this)" onchange="formatearImporte(this)">

            </div>
            <div class="col">
                <livewire:ingresos-por-clasificar-table />
            </div>
            <div class="row pt-4">
                <div class="col">
                    <buton class="btn btn-success" wire:click="agregarRegistro">Agregar registro</buton>
                </div>
                <div class="col text-end">
                    <buton class="btn btn-success" wire:click="finalizarRegistros">Finalizar registro</buton>
                </div>
            </div>
        </div>
    @endif
</div>
<script>
    window.addEventListener('limpiar', event => {
        limpiar()
    })

    function keyPress(e, obj) {
        let isCurrency = $('#' + obj.id).val().search(/[$]/)
        let texto = $('#' + obj.id).val().replace(/[^0-9.]/g, '');
        let isDecimal = texto.search(/[.]/)
        let amount = parseFloat(texto);
        if (!isNaN(amount) && isDecimal < 0 || isCurrency == 0) {
            $('#' + obj.id).val(amount.toLocaleString());
        }
    }

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
            console.log("Ejecuta: " + obj);
        } else {
            toastr.warning('Ingrese valores numéricos en el campo de importe');
            $('#' + obj.id).val('');
        }
    }

    function limpiar() {
        $('#selectCuentaContable').val('');
        $('#inputImporte').val('');
    }
</script>
