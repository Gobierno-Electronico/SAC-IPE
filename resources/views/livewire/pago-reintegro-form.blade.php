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
            tipoMovimiento="PolizaIngresosPagoReintegro" urlFinalizar="/pago-reintegro" tipoPoliza="I" categoriaModulo='INGRESOS PAGO REINTEGRO'/>
    @else
        <label for="selectAreaSolicitante" class="form-label">Área solicitante</label>
        <select name="selectAreaSolicitante" id="selectAreaSolicitante" class="form-select" wire:model="selectCodigoArea">
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
        <input type="text" name="inputObservacion" id="inputObservacion" class="form-control" wire:model="observaciones">

        <label for="inputFechaRegistro" class="form-label mt-3">Fecha de registro</label>
        <input type="date" name="inputFechaRegistro" id="inputFechaRegistro" class="form-control mb-3" wire:model="fechaRegistro">

        <h2 class="mt-5 mb-3">Selección de movimientos</h2>
        <div class="row">
            <div class="col-3">
                <div class="auto">
                    <label for="inputSeguimientoEvento" class="form-label">Número de seguimiento de evento</label>
                    <select name="selectSeguimientoEvento" id="selectSeguimientoEvento" class="form-select" wire:model="numeroEvento" wire:change="cambioEvento">
                        <option value="" selected disabled>
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
                <select name="selectAreaResponsable" id="selectAreaResponsable" class="form-select" wire:model="selectCodigoAreaResponsable">
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

                <label for="selectCuentaContable" class="form-label mt-3">Cuenta contable</label>
                <select name="selectCuentaContable" id="selectCuentaContable" class="form-select" wire:model="cuenta">
                    <option value="" disabled>
                        Seleccionar cuenta</option>
                    @foreach ($cuentas as $cuenta)
                        <option value="{{ $cuenta->Codigo_cuenta }}">
                            {{ $cuenta->Codigo_cuenta . '  ' . $cuenta->Descripcion_cuenta }}</option>
                    @endforeach
                </select>

                <label for="selectMes" class="form-label mt-3">Mes de afectación</label>
                <select name="selectMes" id="selectMes" class="form-select">
                    <option value="" selected>Seleccionar mes...</option>
                    @foreach (range(1, 12) as $mes)
                        @php
                            $carbonMes = \Carbon\Carbon::createFromFormat('!m', $mes);
                        @endphp
                        <option value="{{ ucfirst($carbonMes->monthName) }}">{{ ucfirst($carbonMes->monthName) }}
                        </option>
                    @endforeach
                </select>

                <label for="inputPPTODevengado" class="form-label mt-3">PPTO devengado</label>
                <input type="number" name="inputPPTODevengado" id="inputPPTODevengado" class="form-control">

                <label for="inputImporte" class="form-label mt-3">Importe</label>
                <input type="number" name="inputImporte" id="inputImporte" class="form-control">

            </div>
            <div class="col">
                <livewire:pago-devolucion-table />
            </div>

            <div class="row mt-4">
                <div class="col">
                    <button class="btn btn-success">Agregar registro</button>
                </div>
                <div class="col text-end">
                    <button class="btn btn-success">Finalizar registros</button>
                </div>
            </div>
        </div>
    @endif
</div>
