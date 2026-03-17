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

    <h2 class="mt-5 mb-3">Selección de bancos</h2>
    <div class="row">
        <div class="col-3">
            <label for="selectBanco" class="form-label">Rubro de bancos</label>
            <select name="selectBanco" id="selectBanco" class="form-select">
                <option value="0">
                    Seleccionar un banco
                </option>
                @foreach (\App\Models\Cuenta::select('cuentas.*')->where('cuentas.Cuenta_padre_ID', 'LIKE', '1.1.1.2%')->where('cuentas.Nivel', '=', '6')->orderBy('cuentas.Codigo_cuenta')->get() as $cuenta)
                    <option value="{{ $cuenta->Codigo_cuenta }}">
                        {{ $cuenta->Codigo_cuenta . '  ' . $cuenta->Descripcion_cuenta }}</option>
                @endforeach
            </select>

            <label for="inputTotalRecaudado" class="form-label mt-3">Total recaudado</label>
            <input type="number" name="inputTotalRecaudado" id="inputTotalRecaudado" class="form-control">
            
            
            <label for="inputImporte" class="form-label mt-3">Importe</label>
            <input type="number" name="inputImporte" id="inputImporte" class="form-control">
        </div>
        <div class="col">
            <livewire:seleccion-bancos-table/>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col">
            <button class="btn btn_primario">Agregar registro</button>
        </div>
        <div class="col text-end">
            <button class="btn btn_primario">Finalizar registros</button>
        </div>
    </div>

</div>
