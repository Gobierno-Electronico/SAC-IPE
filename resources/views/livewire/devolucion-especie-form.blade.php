<div class="mt-5">

    <label for="selectAreaSolicitante" class="form-label">Área solicitante</label>
    <select name="selectAreaSolicitante" id="selectAreaSolicitante" class="form-select">
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
    <input type="text" name="inputObservacion" id="inputObservacion" class="form-control">

    <h2 class="mt-5 mb-3">Selección de movimientos</h2>
    <div class="row">
        <div class="col-3">
            <label for="selectAreaResponsable" class="form-label mt-3">Área responsable</label>
            <select name="selectAreaResponsable" id="selectAreaResponsable" class="form-select">
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

            <label for="selectCuentaContable" class="form-label mt-3">Cuenta contable</label>
            <select name="selectCuentaContable" id="selectCuentaContable" class="form-select">
                <option value="0">
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
                    <option value="{{ ucfirst($carbonMes->monthName) }}">{{ ucfirst($carbonMes->monthName) }}</option>
                @endforeach
            </select>

            <label for="inputPPTOEjecutar" class="form-label mt-3">PPTO por ejecutar</label>
            <input type="number" name="inputPPTOEjecutar" id="inputPPTOEjecutar" class="form-control">

            <label for="inputImporte" class="form-label mt-3">Importe</label>
            <input type="number" name="inputImporte" id="inputImporte" class="form-control">

        </div>
        <div class="col">
            <livewire:devolucion-especie-table/>
        </div>

        <div class="row mt-4">
            <div class="col">
                <button class="btn btn_primario">Agregar registro</button>
            </div>
            <div class="col text-end">
                <button class="btn btn_primario">Finalizar registros</button>
            </div>
        </div>
    </div>
</div>
