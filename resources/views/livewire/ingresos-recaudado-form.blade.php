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
        <livewire:ingresos-form-consulta-table :$numeroPoliza :$numeroEvento :$total
            tipoMovimiento="PolizaIngresosRecaudado" tipoPoliza="I" urlFinalizar="/ingresos-recaudado"
            :$numeroPolizaRemanente categoriaModulo='INGRESOS RECAUDADO'
            categoriaRemanente='INGRESOS DEVENGADO REMANENTE RECAUDADO' />
    @else
        <label for="selectAreaSolicitante" class="form-label">Área solicitante</label>
        <select name="selectAreaSolicitante" id="selectAreaSolicitante" class="form-select"
            wire:model="selectCodigoArea">
            <option value="" @if ($this->selectCodigoArea == '') selected @endif disabled>
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
            wire:model="observaciones">

        <label for="inputFechaRegistro" class="form-label mt-3">Fecha de afectación</label>
        <input type="date" name="inputFechaRegistro" id="inputFechaRegistro" class="form-control"
            max="{{ now()->toDateString() }}" wire:model="fechaAfectacion">

        <h2 class="mt-5 mb-3">Selección de movimientos</h2>
        <div class="row">
            <div class="col-3">
                <div class="col-auto">
                    <label for="inputSeguimientoEvento" class="form-label">Número de seguimiento de evento</label>
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
                </div>
                <label for="selectAreaResponsable" class="form-label mt-3">Área responsable</label>
                <select name="selectAreaResponsable" id="selectAreaResponsable" class="form-select"
                    wire:model="selectCodigoAreaResponsable" wire:change="obtenerSolvenciaPresupuestal">
                    <option value="" @if ($this->selectCodigoAreaResponsable == '') selected @endif>
                        Seleccionar un área
                    </option>
                    @foreach (\App\Models\CodigoDepartamento::all() as $departamento)
                        @if (strlen($departamento->Codigo_completo) >= 5)
                            <option value="{{ $departamento->id }}" @if ($this->selectCodigoAreaResponsable == $departamento->id) selected @endif>
                                {{ $departamento->Codigo_completo . ' ' . $departamento->Nombre }}
                            </option>
                        @endif
                    @endforeach
                </select>

                <label for="selectCuentaContable" class="form-label mt-3">Cuenta</label>
                <select name="selectCuentaContable" id="selectCuentaContable" class="form-select" wire:model="cuenta"
                    wire:change="obtenerSolvenciaPresupuestal">
                    <option value="" disabled>
                        Seleccionar cuenta</option>
                    @foreach ($cuentas as $cuenta)
                        <option value="{{ $cuenta->cuenta_id }}">
                            {{ $cuenta->Codigo_cuenta . '  ' . $cuenta->Descripcion_cuenta }}</option>
                    @endforeach
                </select>
                
                <label for="selectMes" class="form-label mt-3">Mes de afectación</label>
                <select name="selectMes" id="selectMes" class="form-select" wire:model="mes" wire:change="obtenerSolvenciaPresupuestal">
                    <option value="" selected disabled>Seleccionar mes...</option>
                    @foreach (range(1, 12) as $mes)
                        @php
                            $carbonMes = \Carbon\Carbon::createFromFormat('!m', $mes);
                        @endphp
                        <option value="{{ ucfirst($carbonMes->monthName) }}">{{ ucfirst($carbonMes->monthName) }}
                        </option>
                    @endforeach
                </select>

                <label for="selectCuentaPago" class="form-label mt-3">Cuenta de pago</label>
                <select name="selectCuentaPago" id="selectCuentaPago" class="form-select" wire:model="cuentaPago" wire:change="obtenerSolvenciaContable">
                    <option value="" disabled>Seleccionar cuenta de pago</option>
                    @foreach ($subcuentas as $cuentaPago)
                        <option value="{{ $cuentaPago->cuenta_id }}">
                            {{ $cuentaPago->Codigo_cuenta . '  ' . $cuentaPago->Descripcion_cuenta }}
                        </option>
                    @endforeach
                </select>


                <label for="inputMontoEvento" class="form-label mt-3">Monto del evento</label>
                <input type="text" name="inputMontoEvento" id="inputMontoEvento" class="form-control" disabled>

                <label for="inputSolvenciaPresupuestal" class="form-label mt-3">Solvencia cuenta presupuestal</label>
                <input type="text" name="inputSolvenciaPresupuestal" id="inputSolvenciaPresupuestal"
                    class="form-control" disabled>

                <label for="inputSolvenciaCargo" class="form-label mt-3">Solvencia cuenta de cargo</label>
                <input type="text" name="inputSolvenciaCargo" id="inputSolvenciaCargo" class="form-control"
                    disabled>

                <label for="inputImporte" class="form-label mt-3">Importe</label>
                <input type="text" name="inputImporte" id="inputImporte" class="form-control"
                    onkeyup="validarDecimales(this)" onchange="formatearImporte(this)" wire:model="importe">

            </div>
            <div class="col">
                <livewire:ingresos-recaudado-table />
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
        $('#selectCuentaContable').val('');
        $('#inputImporte').val('');
        $('#inputSolvenciaCargo').val('');
        $('#inputSolvenciaPresupuestal').val('');
    }
</script>
