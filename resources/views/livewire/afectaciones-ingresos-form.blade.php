<div>
    <div class="d-flex justify-content-between mb-3">
        <h2>{{ $tipo }} presupuestal</h2>
        <div class="">
            <button onclick="location.reload()" class="btn btn-success ms-auto">
                {{ __('Limpiar') }}
            </button>
        </div>
    </div>
    @if ($consulta)
        <livewire:afectaciones-ingresos-consulta :$observaciones :$numeroPoliza :$numeroEvento :$total :$estado :$estadoOriginal :$tipo :$totalPrevio/>
        {{-- <livewire:afectaciones-ingresos-consulta observaciones="OFICIO DG/504/2024 AMPLIACION PRESUPUESTAL DE SUBSIDIOS" numeroPoliza="1" :numeroEvento="9" total=90000 :$estado/> --}}
    @else
        @if ($estado == 'INGRESOS')
            <h4>Datos de ingresos</h4>
            <div class="row mt-4">
                <div class="row mb-3">
                    <div class="d-flex flex-column gap-3 mb-3">
                        <div>
                            <label for="inputObservaciones"
                                class="col-md-12 col-form-label">{{ __('Observaciones') }}</label>
                            <input placeholder="Ingrese las observaciones del movimiento" id="inputObservaciones"
                                type="text" class="form-control" name="inputObservaciones"
                                wire:model.change="observaciones">
                        </div>
                        <div>
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
                    </div>
                    <div class="col-md-6 w-25">
                        <label for="selectCodigoDepartamento"
                            class="col-md-12 col-form-label">{{ __('Código de departamento') }}</label>
                        <select id="selectCodigoDepartamento" type="text"
                            class="select_presupuesto rounded-1 shadow-sm border w-100" name="selectCodigoDepartamento"
                            wire:model.live="selectCodigoDepartamento" wire:change="change('codigo')">
                            <option value="0" selected>Seleccione un código de departamento...</option>

                            @foreach (\App\Models\CodigoDepartamento::all() as $codigo)
                                @if (strlen($codigo->Codigo_completo) >= 5)
                                    <option value="{{ $codigo->id }}">{{ $codigo->Codigo_completo }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="col">
                        <label for="selectDescripcionDepartamento"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <select id="selectDescripcionDepartamento" type="text"
                            class="select_presupuesto rounded-1 shadow-sm border w-100"
                            name="selectDescripcionDepartamento" wire:model.live="selectCodigoDepartamento"
                            wire:change="change('descripcion')">
                            <option value="0" selected>Seleccione un departamento...</option>

                            @foreach (\App\Models\CodigoDepartamento::all() as $codigo)
                                @if (strlen($codigo->Codigo_completo) >= 5)
                                    <option value="{{ $codigo->id }}">{{ $codigo->Nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                </div>
                <div class="row mb-3">
                    <div class="col-md-6 w-25">
                        <label for="selectCodigoRI"
                            class="col-md-12 col-form-label">{{ __('Código clasificador por rubro de ingreso') }}</label>
                        <select id="selectCodigoRI" type="text"
                            class="select_presupuesto rounded-1 shadow-sm border w-100" name="selectCodigoRI"
                            wire:model.live="selectCodigoRI" wire:change="change('codigo_RI')">
                            <option value="0" selected>Seleccione un código de clasificador por rubro de ingreso...
                            </option>

                            @foreach (\App\Models\ClasificadorRubroIngreso::where('Cuenta_contable', '<', '5')->where('Cuenta_registro', '=', true)->orderBy('Codificacion_rubro_ingreso')->get() as $codigo)
                                <option value="{{ $codigo->id }}">{{ $codigo->Codificacion_rubro_ingreso }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col">
                        <label for="selectDescripcionRI"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <select id="selectDescripcionRI" type="text"
                            class="select_presupuesto rounded-1 shadow-sm border w-100" name="selectDescripcionRI"
                            wire:model.live="selectCodigoRI" wire:change="change('descripcion_RI')">
                            <option value="0" selected>Seleccione un clasificador por rubro de ingreso...</option>

                            @foreach (\App\Models\ClasificadorRubroIngreso::where('Cuenta_contable', '<', '5')->where('Cuenta_registro', '=', true)->orderBy('Nombre')->get() as $codigo)
                                <option value="{{ $codigo->id }}">{{ $codigo->Nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
                <div class="row mb-3">
                    <div class="col-md-6 w-25">
                        <label for="selectCodigoFF"
                            class="col-md-12 col-form-label">{{ __('Código de clasificador por fuente de financiamiento') }}</label>
                        <select id="selectCodigoFF" type="text"
                            class="select_presupuesto rounded-1 shadow-sm border w-100" name="selectCodigoFF"
                            wire:model.live="selectCodigoFF" wire:change="change('codigo_FF')">
                            <option value="0" selected>Seleccione un código de clasificador por fuente de
                                financiamiento...
                            </option>

                            @foreach (\App\Models\ClasificadorFuenteFinanciamiento::where('Cuenta_contable', '<', '5')->where('Cuenta_registro', '=', true)->orderBy('Codificacion_fuente_financiamiento')->get() as $codigo)
                                <option value="{{ $codigo->id }}">{{ $codigo->Codificacion_fuente_financiamiento }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col">
                        <label for="selectDescripcionFF"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <select id="selectDescripcionFF" type="text"
                            class="select_presupuesto rounded-1 shadow-sm border w-100 mt-4" name="selectDescripcionFF"
                            wire:model.live="selectCodigoFF" wire:change="change('descripcion_FF')">
                            <option value="0" selected>Seleccione un clasificador por fuente de financiamiento...
                            </option>

                            @foreach (\App\Models\ClasificadorFuenteFinanciamiento::where('Cuenta_contable', '<', '5')->where('Cuenta_registro', '=', true)->orderBy('Nombre')->get() as $codigo)
                                <option value="{{ $codigo->id }}">{{ $codigo->Nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
                <div class="row mb-3">
                    <div class="col-md-6 w-25">
                        <label for="codigoCuentaCargo"
                            class="col-md-12 col-form-label">{{ __('Código cuenta contable de cargo') }}</label>
                        <input id="codigoCuentaCargo" type="text" class="form-control" name="codigoCuentaCargo"
                            wire:model="codigoCuentaCargo" disabled>
                    </div>

                    <div class="col">
                        <label for="descripcionCuentaCargo"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <input id="descripcionCuentaCargo" type="text" class="form-control"
                            name="descripcionCuentaCargo" wire:model="descripcionCuentaCargo" disabled>
                    </div>

                </div>
                <div class="row mb-3">
                    <div class="col-md-6 w-25">
                        <label for="codigoCuentaAbono"
                            class="col-md-12 col-form-label">{{ __('Código cuenta contable de abono') }}</label>
                        <input id="codigoCuentaAbono" type="text" class="form-control" name="codigoCuentaAbono"
                            wire:model="codigoCuentaAbono" disabled>
                    </div>

                    <div class="col">
                        <label for="descripcionCuentaAbono"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <input id="descripcionCuentaAbono" type="text" class="form-control"
                            name="descripcionCuentaAbono" wire:model="descripcionCuentaAbono" disabled>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <livewire:afectaciones-ingresos-table :$selectCodigoDepartamento :$codigoCuentaCargo :$tipo
                    :$codigoCuentaAbono :$observaciones :$estado :$totalPrevio :$numeroEvento />
            </div>
        @else
            <h4>Datos de egresos</h4>
            <div class="row mt-4">
                <div class="row mb-3">
                    <div class="d-flex flex-column gap-3 mb-3">
                        <div>
                            <label for="inputObservaciones"
                                class="col-md-12 col-form-label">{{ __('Observaciones') }}</label>
                            <input placeholder="Ingrese las observaciones del movimiento" id="inputObservaciones"
                                type="text" class="form-control" name="inputObservaciones"
                                wire:model.change="observaciones">
                        </div>
                        <div>
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
                    </div>
                    <div class="col-md-6 w-25">
                        <label for="selectCodigoDepartamento"
                            class="col-md-12 col-form-label">{{ __('Código de departamento') }}</label>
                        <select id="selectCodigoDepartamento" type="text"
                            class="select_presupuesto rounded-1 shadow-sm border w-100"
                            name="selectCodigoDepartamento" wire:model.live="selectCodigoDepartamento"
                            wire:change="change('codigo')">
                            <option value="0" selected>Seleccione un código de departamento...</option>

                            @foreach (\App\Models\CodigoDepartamento::all() as $codigo)
                                @if (strlen($codigo->Codigo_completo) >= 5)
                                    <option value="{{ $codigo->id }}">{{ $codigo->Codigo_completo }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="col">
                        <label for="selectDescripcionDepartamento"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <select id="selectDescripcionDepartamento" type="text"
                            class="select_presupuesto rounded-1 shadow-sm border w-100"
                            name="selectDescripcionDepartamento" wire:model.live="selectCodigoDepartamento"
                            wire:change="change('descripcion')">
                            <option value="0" selected>Seleccione un departamento...</option>

                            @foreach (\App\Models\CodigoDepartamento::all() as $codigo)
                                @if (strlen($codigo->Codigo_completo) >= 5)
                                    <option value="{{ $codigo->id }}">{{ $codigo->Nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                </div>
                <div class="row mb-3">
                    <div class="col-md-6 w-25">
                        <label for="selectCodigoRI"
                            class="col-md-12 col-form-label">{{ __('Clasificación por objeto del gasto') }}</label>
                        <select id="selectCodigoOG" type="text"
                            class="select_presupuesto rounded-1 shadow-sm border w-100" name="selectCodigoOG"
                            wire:model.live="selectCodigoOG" wire:change="change('codigo_OG')">
                            <option value="0" selected>Seleccione un clasificador...
                            </option>

                            @foreach (\App\Models\ClasificadorObjetoGasto::orderBy('Codigo')->get() as $codigo)
                                @if (strlen($codigo->codigo) >= 5)
                                    <option value="{{ $codigo->codigo }}">{{ $codigo->codigo }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="col">
                        <label for="selectDescripcionRI"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <select id="selectDescripcionOG" type="text"
                            class="select_presupuesto rounded-1 shadow-sm border w-100" name="selectDescripcionOG"
                            wire:model.live="selectCodigoOG" wire:change="change('descripcion_OG')">
                            <option value="0" selected>Seleccione un clasificador...</option>

                            @foreach (\App\Models\ClasificadorObjetoGasto::orderBy('Codigo')->get() as $codigo)
                                @if (strlen($codigo->codigo) >= 5)
                                    <option value="{{ $codigo->codigo }}">{{ $codigo->nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="row mb-3">
                    <div class="col-md-6 w-25">
                        <label for="codigoClasificadorAdministrativo"
                            class="col-md-12 col-form-label">{{ __('Clasificador administrativo') }}</label>
                        <input id="codigoClasificadorAdministrativo" type="text" class="form-control"
                            name="codigoClasificadorAdministrativo" wire:model="codigoClasificadorAdministrativo"
                            disabled>
                    </div>

                    <div class="col">
                        <label for="descripcionClasificadorAdministrativo"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <input id="descripcionClasificadorAdministrativo" type="text" class="form-control"
                            name="descripcionClasificadorAdministrativo"
                            wire:model="descripcionClasificadorAdministrativo" disabled>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 w-25">
                        <label for="codigoClasificadorFuncional"
                            class="col-md-12 col-form-label">{{ __('Clasificación funcional') }}</label>
                        <input id="codigoClasificadorFuncional" type="text" class="form-control"
                            name="codigoClasificadorFuncional" wire:model="codigoClasificadorFuncional" disabled>
                    </div>

                    <div class="col">
                        <label for="descripcionClasificadorFuncional"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <input id="descripcionClasificadorFuncional" type="text" class="form-control"
                            name="descripcionClasificadorFuncional" wire:model="descripcionClasificadorFuncional"
                            disabled>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 w-25">
                        <label for="codigoClasificadorProgramatica"
                            class="col-md-12 col-form-label">{{ __('Clasificación programática') }}</label>
                        <input id="codigoClasificadorProgramatica" type="text" class="form-control"
                            name="codigoClasificadorProgramatica" wire:model="codigoClasificadorProgramatica"
                            disabled>
                    </div>

                    <div class="col">
                        <label for="descripcionClasificadorProgramatica"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <input id="descripcionClasificadorProgramatica" type="text" class="form-control"
                            name="descripcionClasificadorProgramatica"
                            wire:model="descripcionClasificadorProgramatica" disabled>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 w-25">
                        <label for="codigoClasificadorTipoGasto"
                            class="col-md-12 col-form-label">{{ __('Clasificación por tipo de gasto') }}</label>
                        <input id="codigoClasificadorTipoGasto" type="text" class="form-control"
                            name="codigoClasificadorTipoGasto" wire:model="codigoClasificadorTipoGasto" disabled>
                    </div>

                    <div class="col">
                        <label for="descripcionClasificadorTipoGasto"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <input id="descripcionClasificadorTipoGasto" type="text" class="form-control"
                            name="descripcionClasificadorTipoGasto" wire:model="descripcionClasificadorTipoGasto"
                            disabled>
                    </div>
                </div>


                <div class="row mb-3">
                    <div class="col-md-6 w-25">
                        <label for="codigoCuentaCargoEgreso"
                            class="col-md-12 col-form-label">{{ __('Código cuenta contable de cargo') }}</label>
                        <input id="codigoCuentaCargoEgreso" type="text" class="form-control"
                            name="codigoCuentaCargoEgreso" wire:model="codigoCuentaCargoEgreso" disabled>
                    </div>

                    <div class="col">
                        <label for="descripcionCuentaCargoEgreso"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <input id="descripcionCuentaCargoEgreso" type="text" class="form-control"
                            name="descripcionCuentaCargoEgreso" wire:model="descripcionCuentaCargoEgreso" disabled>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 w-25">
                        <label for="codigoCuentaAbonoEgreso"
                            class="col-md-12 col-form-label">{{ __('Código cuenta contable de abono') }}</label>
                        <input id="codigoCuentaAbonoEgreso" type="text" class="form-control"
                            name="codigoCuentaAbonoEgreso" wire:model="codigoCuentaAbonoEgreso" disabled>
                    </div>

                    <div class="col">
                        <label for="descripcionCuentaAbonoEgreso"
                            class="col-md-12 col-form-label">{{ __('Descripción') }}</label>
                        <input id="descripcionCuentaAbonoEgreso" type="text" class="form-control"
                            name="descripcionCuentaAbonoEgreso" wire:model="descripcionCuentaAbonoEgreso" disabled>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <livewire:afectaciones-ingresos-table :$selectCodigoDepartamento :$codigoCuentaCargoEgreso :$tipo
                    :$codigoCuentaAbonoEgreso :$observaciones :$estado :$totalPrevio :$numeroEvento />
            </div>
        @endif
    @endif
</div>

<script>
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

    function changeSelectsRI(id) {
        $('#selectCodigoRI').val(id).change();
        $('#selectDescripcionRI').val(id).change();
    }

    function changeSelectsFF(id) {
        $('#selectCodigoFF').val(id).change();
        $('#selectDescripcionFF').val(id).change();
    }

    function actualizarSelect(tipo, id = 0) {
        console.log(tipo)
        switch (tipo) {
            case "codigo":
                $('#selectDescripcionDepartamento').val(parseInt($('#selectCodigoDepartamento').val()))
                    .change();
                break;
            case "descripcion":
                $('#selectCodigoDepartamento').val(parseInt($('#selectDescripcionDepartamento').val()))
                    .change();
                break;
            case "codigo_RI":
                $('#selectDescripcionRI').val(parseInt($('#selectCodigoRI').val()))
                    .change();
            case "descripcion_RI":
                $('#selectCodigoRI').val(parseInt($('#selectDescripcionRI').val()))
                    .change();
                changeSelectsFF(id)
                break;
            case "codigo_FF":
                $('#selectDescripcionFF').val(parseInt($('#selectCodigoFF').val()))
                    .change();
            case "descripcion_FF":
                $('#selectCodigoFF').val(parseInt($('#selectDescripcionFF').val()))
                    .change();
                changeSelectsRI(id)
                break;
            case "codigo_OG":
                $('#selectDescripcionOG').val(parseInt($('#selectCodigoOG').val()))
                    .change();
            case "descripcion_OG":
                $('#selectCodigoOG').val(parseInt($('#selectDescripcionOG').val()))
                    .change();
                break;
        }
    }

    window.addEventListener('seleccionar-mes', event => {
        $('#inputMesSeleccionado').val(event.__livewire.params.mes);
        $("#tabla-importes tr").removeClass("table-active");
        $(`#${event.__livewire.params.mes}`).addClass("table-active");
        console.log(`#${event.__livewire.params.mes}`)
    });

    window.addEventListener('actualizar-total', event => {
        $('#inputTotal').val(event.__livewire.params.total);
    });

    window.addEventListener('clean', event => {
        $('#inputMesSeleccionado').val("");
        $('#inputImporte').val("");
        $('#inputTotal').val("");
        // $('#inputObservaciones').val("");

    });

    window.addEventListener('actualizar-select', event => {
        let params = event.__livewire.params
        actualizarSelect(params.tipo, params.id)
    });

    window.addEventListener('actualizar-cuentas', event => {
        let params = event.__livewire.params
        console.log(params)
        $("#codigoCuentaCargo").val(params.codigoCargo);
        $("#codigoCuentaAbono").val(params.codigoAbono);
        $("#descripcionCuentaCargo").val(params.descripcionCargo);
        $("#descripcionCuentaAbono").val(params.descripcionAbono);
    });

    window.addEventListener('actualizar-clasificadores-egreso', event => {
        let params = event.__livewire.params
        console.log(params)
        $("#descripcionClasificadorAdministrativo").val(params.descripcionCA);
        $("#codigoClasificadorAdministrativo").val(params.codigoCA);
        $("#descripcionClasificadorFuncional").val(params.descripcionF);
        $("#codigoClasificadorFuncional").val(params.codigoF);

        $("#descripcionClasificadorProgramatica").val(params.descripcionP);
        $("#codigoClasificadorProgramatica").val(params.codigoP);

        $("#descripcionClasificadorTipoGasto").val(params.descripcionTG);
        $("#codigoClasificadorTipoGasto").val(params.codigoTG);

        $("#codigoCuentaCargoEgreso").val(params.codigoCargoEgreso);
        $("#codigoCuentaAbonoEgreso").val(params.codigoAbonoEgreso);
        $("#descripcionCuentaCargoEgreso").val(params.descripcionCargoEgreso);
        $("#descripcionCuentaAbonoEgreso").val(params.descripcionAbonoEgreso);
    });

    window.addEventListener('reset-data', event => {
        $('#selectDescripcionDepartamento').val(0).change();
        $('#selectCodigoDepartamento').val(0).change();
        $('#selectCodigoRI').val(0).change();
        $('#selectDescripcionRI').val(0).change();
        $('#selectCodigoFF').val(0).change();
        $('#selectDescripcionFF').val(0).change();
        $('#selectCodigoOG').val(0).change();
        $('#selectDescripcionOG').val(0).change();

        $('#codigoCuentaAbono').val("");
        $('#descripcionCuentaAbono').val("");
        $('#codigoCuentaCargo').val("");
        $('#descripcionCuentaCargo').val("");
        $("#descripcionClasificadorAdministrativo").val("");
        $("#codigoClasificadorAdministrativo").val("");
        $("#descripcionClasificadorFuncional").val("");
        $("#codigoClasificadorFuncional").val("");
        $("#descripcionClasificadorProgramatica").val("");
        $("#codigoClasificadorProgramatica").val("");
        $("#descripcionClasificadorTipoGasto").val("");
        $("#codigoClasificadorTipoGasto").val("");
        $("#codigoCuentaCargoEgreso").val("");
        $("#codigoCuentaAbonoEgreso").val("");
        $("#descripcionCuentaCargoEgreso").val("");
        $("#descripcionCuentaAbonoEgreso").val("");
    });

    window.addEventListener('mensaje', event => {
        switch (event.__livewire.params.tipo) {
            case 'info':
                toastr.info(event.__livewire.params.mensaje);
                break;
            case 'error':
                toastr.error(event.__livewire.params.mensaje);
                break;
            case 'success':
                toastr.success(event.__livewire.params.mensaje);
                break;
            case 'warning':
                toastr.warning(event.__livewire.params.mensaje);
                break;
            default:
                break;
        }
    });
</script>
