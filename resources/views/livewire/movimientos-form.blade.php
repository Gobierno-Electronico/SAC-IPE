<div>
    <h2 class="mb-5">Movimientos</h2>

    <div class="col text-end mb-2">
        <a href="#" class="btn btn-outline-success">Abrir evento</a>
        <a href="#" class="btn btn-outline-primary">Importar evento</a>
    </div>

    <div class="container border rounded p-3 mb-4">
        <div class="row">
            <div class="col-3">
                <label for="inputEvento" class="form-label">Evento</label>
                <input type="text" name="inputEvento" id="inputEvento" class="form-control">
            </div>
            <div class="col-3">
                <label for="inputFecha" class="form-label">Fecha</label>
                <input type="date" name="inputFecha" id="inputFecha" class="form-control">
            </div>
        </div>
    </div>
    <h5>Presupuestos</h5>
    <div class="container border rounded p-3 mb-4">
        <label for="selectArea" class="form-label">Área</label>
        <select name="selectArea" id="selectArea" class="form-select mb-3">
            <option value="0" @if ($this->area == '') selected @endif>
                Seleccionar Área</option>
            @foreach (\App\Models\CodigoDepartamento::all() as $departamento)
                @if (strlen($departamento->Codigo_completo) >= 5)
                    <option value="{{ $departamento->Codigo_completo }}">
                        {{ $departamento->Codigo_completo . ' - ' . $departamento->Nombre }}
                    </option>
                @endif
            @endforeach
        </select>

        <label for="selectCOG" class="form-label">COG</label>
        <select name="selectCOG" wire:model.live="cog" id="selectCOG" class="form-select mb-3">
            <option value="0" @if ($this->cog == '') selected @endif>
                Seleccionar Partida</option>
            @foreach (\App\Models\Cuenta::join('CuentasCOG', 'CuentasCOG.codigoCuenta', '=', 'cuentas.Codigo_cuenta')->select('cuentas.*', 'CuentasCOG.*')->where('Descripcion_cuenta', 'like', '%(Aprobado)%')->orderBy('COG')->get() as $cuenta)
                <option value="{{ $cuenta->COG }}">
                    {{ $cuenta->COG . ' - ' . $cuenta->nombre }}</option>
            @endforeach
        </select>

        <div class="row">
            <div class="col-4 me-3">
                <label for="selectMes" class="form-label">Mes de afectación</label>
                <select name="selectMes" id="selectMes" class="form-select mb-3">
                    <option value="0">Seleccionar mes...</option>
                    @foreach (range(1, 12) as $mes)
                        @php
                            $carbonMes = \Carbon\Carbon::createFromFormat('!m', $mes);
                        @endphp
                        <option value="{{ $mes }}">{{ $carbonMes->monthName }}</option>
                    @endforeach
                </select>

                <label for="inputImporte" class="form-label">Importe</label>
                <input type="number" name="inputImporte" id="inputImporte" class="form-control mb-3" step="0.01">
                {{-- step permite el uso de valores flotantes en el input --}}

                <label for="inputTransferencia">Apartado para transferencias</label>
                <input type="text" name="inputTransferencia" id="inputTransferencia" class="form-control mb-3">
            </div>
            <div class="col">
                <label for="inputObservaciones" class="form-label">Observaciones</label>
                <textarea name="inputObservaciones" id="inputObservaciones" class="form-control mb-3" rows="8"></textarea>
            </div>
        </div>

    </div>

    <h5>Detalle de registro</h5>
    <div class="container border rounded p-4">
        <table class="table border">
            <thead>
                <tr class="table-secondary">
                    <th>Área</th>
                    <th>COG</th>
                    <th>Denominación</th>
                    <th>Solvencia</th>
                    <th>Importe</th>
                    <th>Nva Solvencia</th>
                    <th>Consec</th>
                    <th>Mes</th>
                    <th>Concepto</th>
                    <th>Descripción del gasto</th>
                </tr>
            </thead>
            <tbody>
             
            </tbody>
        </table>

    </div>
    <div class="row mt-3">
        <div class="col">
            <a href="/movimientos" class="btn btn-outline-secondary">Limpiar campos</a>
        </div>
        <div class="col text-end">
            <a href="#" class="btn btn_primario">Guardar</a>
            <a href="#" class="btn btn_primario">Aplicar</a>
        </div>
    </div>
</div>
