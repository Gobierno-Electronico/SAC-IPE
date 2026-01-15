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
        <livewire:prestamos-form-consulta-table :$numeroPoliza :$numeroEvento :$total
            tipoMovimiento="PolizaOtorgamientoEjercidoPagadoRecaudadoPrestamosIniciales"
            urlFinalizar="/capitulo7-otorgamiento-ejercido-pagado-recaudado-prestamosIniciales" tipoPoliza="D"
            categoriaModulo='OTORGAMIENTO EJERCIDO PAGADO RECAUDADO PRESTAMOS INICIALES' />
    @else
        <label for="selectAreaSolicitante" class="form-label">Área solicitante</label>
        <select name="selectAreaSolicitante" id="selectAreaSolicitante" class="form-select"
            wire:model="selectCodigoArea">
            @foreach (\App\Models\CodigoDepartamento::all() as $departamento)
                @if (strlen($departamento->Codigo_completo) >= 5)
                    @if ($departamento->Codigo_completo == '1.5.04')
                        <option value="{{ $departamento->id }}" selected>
                            {{ $departamento->Codigo_completo . ' ' . $departamento->Nombre }}
                        </option>
                    @endif
                @endif
            @endforeach

        </select>

        <label for="inputObservacion" class="form-label mt-3">Observación</label>
        <input type="text" name="inputObservacion" id="inputObservacion" class="form-control"
            wire:model="observaciones">

        <label for="inputFechaAfectacion" class="form-label mt-3">Fecha de afectación</label>
        <input type="date" name="inputFechaAfectacion" id="inputFechaAfectacion" class="form-control"
            max="{{ now()->toDateString() }}" wire:model="fechaAfectacion">

        <h2 class="mt-5 mb-3">Selección de movimientos</h2>
        <div class="row">
            <div class="col-3">
                <label for="selectAreaResponsable" class="form-label mt-3">Área responsable</label>
                <select name="selectAreaResponsable" id="selectAreaResponsable" class="form-select"
                    wire:model="selectCodigoAreaResponsable" wire:change="cargarPresupuesto">
                    <option value=""selected disabled>Seleccionar área</option>
                    @foreach (\App\Models\CodigoDepartamento::all() as $departamento)
                        @if (strlen($departamento->Codigo_completo) >= 5)
                            <option value="{{ $departamento->id }}">
                                {{ $departamento->Codigo_completo . ' ' . $departamento->Nombre }}
                            </option>
                        @endif
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
                <select name="selectCuenta" id="selectCuenta" class="form-select" wire:model="cuenta"
                    wire:change="cargarPresupuesto">
                    <option value="" disabled>Seleccionar cuenta</option>
                    @foreach ($cuentas as $cuenta)
                        <option value="{{ $cuenta->cuenta_id }}">
                            {{ $cuenta->Codigo_cuenta . '  ' . $cuenta->Descripcion_cuenta }}</option>
                    @endforeach
                </select>

                <label for="selectMes" class="form-label mt-3">Mes de afectación</label>
                <select name="selectMes" id="selectMes" class="form-select" wire:model="mes"
                    wire:change="cargarPresupuesto">
                    <option value="" selected disabled>Seleccionar mes...</option>
                    @foreach (range(1, 12) as $mes)
                        @php
                            $carbonMes = \Carbon\Carbon::createFromFormat('!m', $mes);
                        @endphp
                        <option value="{{ ucfirst($carbonMes->monthName) }}">{{ ucfirst($carbonMes->monthName) }}
                        </option>
                    @endforeach
                </select>



                <label for="inputPTTOEjecutar" class="form-label mt-3">Presupuesto por ejecutar</label>
                <input type="text" name="inputPTTOEjecutar" id="inputPTTOEjecutar" class="form-control" disabled>

                <label for="inputImporte" class="form-label mt-3">Importe</label>
                <input type="text" name="inputImporte" id="inputImporte" class="form-control"
                    onkeyup="validarDecimales(this)" onchange="formatearImporte(this)" wire:model="importe">

                @if ($mostrarAbono)
                    <label for="selectCuentaAbono" class="form-label mt-3">Cuenta de abono</label>
                    <select name="selectCuentaAbono" id="selectCuentaAbono" class="form-select"
                        wire:model="cuentaAbono">
                        <option value="" selected disabled>Seleccionar cuenta de abono</option>
                        @foreach ($cuentasAbono as $item)
                            <option value="{{ $item->cuenta_id }}">
                                {{ $item->Codigo_cuenta . ' ' . $item->Descripcion_cuenta }}
                            </option>
                        @endforeach
                    </select>

                    <label for="inputImporteAbono" class="form-label mt-3">Importe abono</label>
                    <input type="text" name="inputImporteAbono" id="inputImporteAbono" class="form-control"
                        onkeyup="validarDecimales(this)" onchange="formatearImporte(this)" wire:model="importeAbono">
                @endif

            </div>

            <div class="col">
                <livewire:prestamos-otorgamiento-ejercido-pagado-recaudado-prestamosIniciales-table />
            </div>

            <div class="row mt-4">
                <div class="col">
                    <button class="btn btn-success" wire:click="agregarRegistro">Agregar registro</button>
                </div>
                <div class="col text-end">
                    <button class="btn btn-success" wire:click="finalizarRegistros">Finalizar registros</button>
                </div>
            </div>
        </div>

    @endif
</div>

<script>
    window.addEventListener('formato_importe', event => {
        let params = event.__livewire.params
        formatearImporte({
            id: params.id
        }, params.amount)
    })

    window.addEventListener('limpiar', event => {
        limpiar()
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
        $('#inputPTTOEjecutar').val('');
    }
</script>
