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
                            <input value="{{ '$' . number_format($total, 2, '.', ',') }}" type="text"
                                class="form-control" name="inputAumentado" disabled>
                        </div>
                    </div>

                </div>

            </div>
        </div>
        <livewire:recalendarizacion-form-consulta-table :$numeroPoliza :$numeroEvento :$total
            tipoMovimiento="PolizaAnticiposReintegro" urlFinalizar="/deudores-reintegro-anticipo" tipoPoliza="D"
            categoriaModulo='DEUDORES REINTEGRO ANTICIPOS' />
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

            <label for="inputFechaRegistro" class="form-label mt-3">Fecha de afectación</label>
            <input type="date" name="inputFechaRegistro" id="inputFechaRegistro" class="form-control mb-3"
                wire:ignore wire:model.live="fechaAfectacion">

        </div>

        <h2 class="mt-5 mb-3">Selección de movimientos</h2>
        <div class="row">
            <div class="col-3">
                <label for="inputSeguimientoEvento" class="form-label mt-3">Número de seguimiento de evento</label>
                <select name="selectSeguimientoEvento" id="selectSeguimientoEvento" class="form-select"
                    wire:model="numeroEvento" wire:change="cambioEvento">
                    <option value="" disabled>
                        Seleccionar un evento
                    </option>
                    @foreach ($eventos as $evento => $descripcion)
                        <option value="{{ $evento }}">
                            {{ $evento }} - {{ $descripcion }}
                        </option>
                    @endforeach
                </select>

                <label for="selectDocumentoFuente" class="form-label mt-3">Documento fuente</label>
                <select name="selectDocumentoFuente" id="selectDocumentoFuente" class="form-select"
                    wire:model="documentoFuente">
                    <option value="">Selecciona una opción...</option>
                    @foreach (\App\Enums\DocumentosFuente::cases() as $documento)
                        <option value="{{ $documento->value }}">
                            {{ $documento->value === 'Memorandum' ? 'Memorándum' : $documento->value }}
                        </option>
                    @endforeach
                </select>

                <label for="selectCuenta" class="form-label mt-3">Cuenta</label>
                <select name="selectCuenta" id="selectCuenta" class="form-select mb-3" wire:model.live="cuenta">
                    <option value="" @if ($this->cuenta == '') selected @endif selected>Seleccionar
                        cuenta...</option>
                    @foreach ($cuentas as $cuenta)
                        <option value="{{ $cuenta->cuenta_id }}">
                            {{ $cuenta->Codigo_cuenta . ' ' . $cuenta->Descripcion_cuenta }}</option>
                    @endforeach
                </select>

                <label for="selectCuentaCargo" class="form-label mt-2">Cuenta Cargo</label>
                <select name="selectCuentaCargo" id="selectCuentaCargo" class="form-select mb-3"
                    wire:model="cuentaCargo" wire:change="cargarPresupuesto">
                    <option value="" @if ($this->cuenta == '') selected @endif selected>Seleccionar
                        cuenta...</option>
                    @foreach ($cuentasCargo as $cuenta)
                        <option value="{{ $cuenta->cuenta_id }}">
                            {{ $cuenta->Codigo_cuenta . ' ' . $cuenta->Descripcion_cuenta }}</option>
                    @endforeach
                </select>


                <label for="selectMes" class="form-label mt-2">Mes de afectación</label>
                <select name="selectMes" id="selectMes" class="form-select mb-3" wire:model.live="mes"
                    wire:change="cargarPresupuesto">
                    <option value="" @if ($this->mes == '') selected @endif>Seleccionar mes...</option>
                    @foreach (range(1, 12) as $mes)
                        @php
                            $carbonMes = \Carbon\Carbon::createFromFormat('!m', $mes);
                        @endphp
                        <option value="{{ ucfirst($carbonMes->monthName) }}">{{ ucfirst($carbonMes->monthName) }}
                        </option>
                    @endforeach
                </select>

                <label for="inputMontoEvento" class="form-label mt-2">Monto del evento</label>
                <input type="text" name="inputMontoEvento" id="inputMontoEvento" class="form-control" disabled>

                <label for="inputSolvencia" class="form-label mt-2">Solvencia disponible</label>
                <input type="text" class="form-control mb-3" id="inputSolvencia" name="inputSolvencia"
                    wire:model="solvencia" disabled>

                <label for="inputImporte" class="form-label">Importe</label>
                <input type="text" name="inputImporte" id="inputImporte" class="form-control mb-3"
                    wire:model.live="importe" onkeyup="validarDecimales(this)" onchange="formatearImporte(this)">

            </div>
            <div class="col">
                <livewire:deudores-reintegro-anticipo-table />
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

    window.addEventListener('formato_importe', event => {
        let params = event.__livewire.params
        formatearImporte({
            id: params.id
        }, params.amount)
    })

    function validarDecimales(input) {
        // Obtener solo números y un punto decimal permitido
        let valor = input.value.replace(/[^0-9.]/g, '') // Elimina caracteres no numéricos
            .replace(/(\..*)\./g, '$1') // Evita más de un punto decimal
            .replace(/^0+(\d)/, '$1') // Elimina ceros iniciales
            .replace(/^(\d+)(\.\d{0,2})?.*$/, '$1$2'); // Máximo 2 decimales

        // Si el valor es solo un punto, permitirlo sin formatear
        if (valor === ".") {
            input.value = valor;
            return;
        }

        // Convertir a número para formateo
        let partes = valor.split('.');
        let numeroEntero = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ','); // Agrega comas a los miles

        // Reconstruir con decimales si existen
        input.value = partes.length > 1 ? `${numeroEntero}.${partes[1]}` : numeroEntero;
    }

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


    function limpiar() {
        $('#selectCuentaContable').val('');
        $('#inputImporte').val('');
        $('#inputSolvencia').val('');
    }
</script>
