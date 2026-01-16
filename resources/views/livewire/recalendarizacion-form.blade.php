<div>
    <div class="row mt-5">
        @if ($consulta)

            <div>
                <h4>Consulta de movimientos por registrar</h4>

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
                                <label for="inputObservaciones"
                                    class="col-md-12 col-form-label">{{ __('Total aumentado') }}</label>
                                <input value="{{ $totalAumentado }}" type="text" class="form-control"
                                    name="inputAumentado" disabled>
                            </div>
                            <div>
                                <label for="inputObservaciones"
                                    class="col-md-12 col-form-label">{{ __('Total disminuido') }}</label>
                                <input value="{{ $totalDisminuido }}" type="text" class="form-control"
                                    name="inputDisminuido" disabled>
                            </div>
                        </div>
                        <div class="mt-3">
                            <livewire:recalendarizacion-form-consulta-table :$numeroPoliza :$numeroEvento
                                :$totalAumentado :$totalDisminuido urlFinalizar="/presupuesto/recalendarizacion"
                                tipoPoliza="D" tipoMovimiento="PolizaReclasificacion"
                                categoriaModulo='RECLASIFICACIÓN O RECALENDARIZACIÓN' />

                        </div>

                    </div>

                </div>
            </div>
        @else
            <div class="col-2">
                <label for="" class="form-label fs-5">Área solicitante</label>
            </div>
            <div class="col-3">
                <select name="selectCodigoArea" id="selectCodigoArea" class="form-select"
                    wire:model.live="selectCodigoArea" wire:change="change('codigo')">
                    <option value="" @if ($this->selectDescripcionArea == '') selected @endif>
                        Seleccionar código de área
                    </option>
                    @foreach (\App\Models\CodigoDepartamento::all() as $departamento)
                        @if (strlen($departamento->Codigo_completo) >= 5)
                            <option value="{{ $departamento->id }}" @if ($this->selectDescripcionArea == $departamento->id) selected @endif>
                                {{ $departamento->Codigo_completo }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="col">
                <select name="selectDescripcionArea" id="selectDescripcionArea" class="form-select"
                    wire:model.live="selectDescripcionArea" wire:change="change('descripcion')">
                    <option value="" @if ($this->selectCodigoArea == '') selected @endif>
                        Seleccionar descripción de área
                    </option>
                    @foreach (\App\Models\CodigoDepartamento::all() as $departamento)
                        @if (strlen($departamento->Codigo_completo) >= 5)
                            <option value="{{ $departamento->id }}" @if ($this->selectCodigoArea == $departamento->id) selected @endif>
                                {{ $departamento->Nombre }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
    </div>
    <div class="row mt-2">
        <div class="col-2">
            <label for="observaciones" class="form-label fs-5">Observación</label>
        </div>
        <div class="col">
            <input type="text" class="form-control" wire:model.live="observaciones" id="observaciones">
        </div>

    </div>

    <div class="row mt-2">
        <div class="col-2">
            <label for="inputFechaAfectacion" class="form-label fs-5">Fecha de afectación</label>
        </div>
        <div class="col">
            <input type="date" name="inputFechaAfectacion" id="inputFechaAfectacion" class="form-control" wire:ignore
                wire:model="fechaAfectacion">
        </div>
    </div>

    <h3 class="mt-5 mb-2">Selección de movimientos</h3>
    <div class="d-flex ">
        <div class="col-3">
            <div class="mb-3">
                <label for="selectAreaResponsable" class="form-label">Área responsable</label>
                <select name="selectAreaResponsable" id="selectAreaResponsable" class="form-select mb-3"
                    wire:model.live="areaResponsable" wire:change="cambioSolvencia">
                    <option value="" @if ($this->selectCodigoArea == '') selected @endif>
                        Seleccionar descripción de área
                    </option>
                    @foreach (\App\Models\CodigoDepartamento::all() as $departamento)
                        @if (strlen($departamento->Codigo_completo) >= 5)
                            <option value="{{ $departamento->id }}" @if ($this->selectCodigoArea == $departamento->id) selected @endif>
                                {{ $departamento->Codigo_completo . ' ' . $departamento->Nombre }}
                            </option>
                        @endif
                    @endforeach
                </select>

                <label for="selectDocumentoFuente" class="form-label">Documento fuente</label>
                <select name="selectDocumentoFuente" id="selectDocumentoFuente" class="form-select"
                    wire:model="documentoFuente">
                    <option value="">Selecciona una opción...</option>
                    @foreach (\App\Enums\DocumentosFuente::cases() as $documento)
                        <option value="{{ $documento->value }}">
                            {{ $documento->value === 'Memorandum' ? 'Memorándum' : $documento->value }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="selectPartida">Partida</label>
                <select name="selectPartida" id="selectPartida" class="form-select" wire:model.live="cog"
                    wire:change="cambioSolvencia">
                    <option value="" @if ($this->cog == '') selected @endif>
                        Seleccionar Partida
                    </option>
                    @foreach (\App\Models\Cuenta::join('CuentasCOG', 'CuentasCOG.codigoCuenta', '=', 'cuentas.Codigo_cuenta')->select('cuentas.*', 'CuentasCOG.*')->where('Descripcion_cuenta', 'like', '%ejercer%')->orderBy('COG')->get() as $cuenta)
                        <option value="{{ $cuenta->COG }}">
                            {{ $cuenta->COG . ' ' . $cuenta->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="selectMesAfectacion">Mes de afectación</label>
                <select name="selectMesAfectacion" id="selectMesAfectacion" wire:change="cambioSolvencia"
                    class="form-select" wire:model.live="mes">
                    <option value="" selected>Seleccionar mes...</option>
                    @foreach (range(1, 12) as $mes)
                        @php
                            $carbonMes = \Carbon\Carbon::createFromFormat('!m', $mes);
                        @endphp
                        <option value="{{ ucfirst($carbonMes->monthName) }}">{{ ucfirst($carbonMes->monthName) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="inputAfectacion">Afectación PPTAL</label>
                <select name="inputAfectacion" id="inputAfectacion" class="form-select"
                    wire:model.live="afectacion">
                    <option value="" selected>Seleccionar tipo de afectación...</option>
                    <option value="aumento">Aumento</option>
                    <option value="disminucion">Disminución</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="inputSolvencia">Solvencia</label>
                <input type="text" name="inputSolvencia" id="inputSolvencia" class="form-control"
                    onchange="formatearImporte(this)" onkeyup="keyPress(event, this)" wire:model.live="solvencia"
                    disabled>
            </div>
            <div class="mb-3">
                <label for="inputImporte">Importe</label>
                <input type="text" name="inputImporte" id="inputImporte" class="form-control"
                    onchange="formatearImporte(this)" onkeyup="validarDecimales(this)" wire:model.live="importe">
            </div>
            <div class="mb-4">
                <label for="inputTipoMovimiento">Tipo de movimiento</label>
                <select name="inputTipoMovimiento" id="inputTipoMovimiento" class="form-select"
                    wire:model.live="movimiento">
                    <option value="" selected>Seleccionar tipo de movimiento...</option>
                    <option value="reclasificacion">Reclasificación</option>
                    <option value="recalendarizacion">Recalendarización</option>
                </select>
            </div>
        </div>
        <div class="col ms-4">
            <livewire:recalendarizacion-table />
        </div>
    </div>
    <div class="mb-3 row">
        <div class="col-auto">
            <button class="btn btn-success" wire:click="agregarRegistro">Agregar registro</button>
        </div>
        <div class="col text-end">
            <button class="btn btn-success" wire:click="finalizarRegistros">Finalizar registros</button>
        </div>
    </div>
    @endif
</div>
<script>



    window.addEventListener('limpiar', event => {
        limpiar()
    })

    window.addEventListener('actualizar-solvencia', event => {
        let parametros = event.__livewire.params
        $('#inputSolvencia').val(parametros.solvencia);
        setTimeout(() => {
            formatearImporte({
                id: "inputSolvencia"
            })
        }, 100);
    })

    function keyPress(e, obj) {
        let isCurrency = $('#' + obj.id).val().search(/[$]/)
        let texto = $('#' + obj.id).val().replace(/[^0-9.]/g, '');
        let isDecimal = texto.search(/[.]/)
        let amount = parseFloat(texto);
        if (!isNaN(amount) && isDecimal < 0 || isCurrency == 0) {
            console.log("si")
            $('#' + obj.id).val(amount.toLocaleString());
        }
    }

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
        $('#selectAreaResponsable').val('');
        $('#selectPartida').val('');
        $('#selectMesAfectacion').val('');
        $('#inputAfectacion').val('');
        $('#inputSolvencia').val('');
        $('#inputImporte').val('');
        $('#inputTipoMovimiento').val('');
    }
</script>
