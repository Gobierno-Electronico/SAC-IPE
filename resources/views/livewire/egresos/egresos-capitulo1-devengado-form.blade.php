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
        <livewire:egresos.egresos-form-consulta-table :$numeroPoliza :$numeroEvento :$total
            tipoMovimiento="PolizaEgresosDevengadoCapitulo1" urlFinalizar="/capitulo1-devengado"  tipoPoliza="E"
            categoriaModulo='EGRESOS DEVENGADO CAPITULO 1' />
    @else
        <div class="col text-end">
            <button class="btn btn-secondary" wire:click="">Importar nómina</button>
        </div>
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

        <label for="inputFechaAfectacion" class="form-label mt-3">Fecha de afectación</label>
        <input type="date" name="inputFechaAfectacion" id="inputFechaAfectacion" class="form-control" max="{{ now()->toDateString() }}"
            wire:model="fechaAfectacion">

        <h2 class="mt-5 mb-3">Selección de movimientos</h2>
        <div class="row">
            <div class="col-3">
                <label for="inputSeguimientoEvento" class="form-label mt-3">Número de seguimiento de evento</label>
                <select name="selectSeguimientoEvento" id="selectSeguimientoEvento" class="form-select" wire:model="numeroEvento" wire:change="cambioEvento">
                    <option value="" disabled>
                        Seleccionar un evento
                    </option>
                    @foreach ($eventos as $evento  => $descripcion)
                        <option value="{{ $evento }}">
                           {{ $evento }} - {{$descripcion}}
                        </option>
                    @endforeach
                </select>

                <label for="selectAreaResponsable" class="form-label mt-3">Área responsable</label>
                <select name="selectAreaResponsable" id="selectAreaResponsable" class="form-select"
                    wire:model="selectCodigoAreaResponsable" wire:change="cargarPresupuestoComprometido">
                    <option value="" @if ($this->selectCodigoAreaResponsable == '') selected @endif disabled>
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

                <label for="selectPartidaPresupuestal" class="form-label mt-3">Partida presupuestal</label>
                <select name="selectPartidaPresupuestal" id="selectPartidaPresupuestal" class="form-select" wire:model="partidaPresupuestal" wire:change="llenarCuentasContables">
                    <option value="" selected disabled>Seleccionar partida presupuestal</option>
                    @foreach ($partidasPresupuestales as $partida)
                        <option value="{{ $partida->cuenta_id }}">
                            {{ $partida->Codigo_cuenta . '  ' . $partida->Descripcion_cuenta }}</option>
                    @endforeach
                </select>

                <label for="selectCuenta" class="form-label mt-3">Cuenta contable</label>
                <select name="selectCuenta" id="selectCuenta" class="form-select" wire:model="cuentaContable">
                    <option value="" selected disabled>Seleccionar cuenta</option>
                    @foreach ($cuentasContables as $cuenta)
                        <option value="{{ $cuenta->cuenta_id }}"> 
                            {{ $cuenta->Codigo_cuenta . '  ' . $cuenta->Descripcion_cuenta }}
                        </option>
                    @endforeach
                </select>

                <label for="selectMes" class="form-label mt-3">Mes de afectación</label>
                <select name="selectMes" id="selectMes" class="form-select" wire:model="mes" wire:change="cargarPresupuestoComprometido">
                    <option value="" selected disabled>Seleccionar mes...</option>
                    @foreach (range(1, 12) as $mes)
                        @php
                            $carbonMes = \Carbon\Carbon::createFromFormat('!m', $mes);
                        @endphp
                        <option value="{{ ucfirst($carbonMes->monthName) }}">{{ ucfirst($carbonMes->monthName) }}
                        </option>
                    @endforeach
                </select>

                <label for="inputMontoEvento" class="form-label mt-3">Monto del evento</label>
                <input type="text" name="inputMontoEvento" id="inputMontoEvento" class="form-control" disabled>

                <label for="inputPTTOComprometido" class="form-label mt-3">Presupuesto comprometido</label>
                <input type="text" name="inputPTTOComprometido" id="inputPTTOComprometido" class="form-control" disabled>
                
                <label for="inputImporte" class="form-label mt-3">Importe</label>
                <input type="text" name="inputImporte" id="inputImporte" class="form-control"
                    onkeyup="keyPress(event, this)" onchange="formatearImporte(this)" wire:model="importe">
            </div>

            <div class="col">
                <livewire:egresos.egresos-capitulo1-devengado-table />
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

    function keyPress(e, obj) {
        let isCurrency = $('#' + obj.id).val().search(/[$]/)
        let texto = $('#' + obj.id).val().replace(/[^0-9.]/g, '');
        let isDecimal = texto.search(/[.]/)
        let amount = parseFloat(texto);
        if (!isNaN(amount) && isDecimal < 0 || isCurrency == 0) {
            $('#' + obj.id).val(amount.toLocaleString());
        }
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
        $('#inputPTTOComprometido').val('');
    }
</script>
