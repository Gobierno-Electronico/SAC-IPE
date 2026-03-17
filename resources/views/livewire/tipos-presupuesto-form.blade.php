<div>
    <div wire:loading.delay.long>
        <div
            style='display: flex; justify-content: center; align-items: center; background-color: black; position: fixed; top: 0px; left: 0px; z-index: 9999; width: 100%; height: 100%; opacity: .75'>
            <div class="la-timer la-2x">
                <div></div>

            </div>
        </div>
    </div>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <div class="row">
        <h2 class="mb-5">Tipos de presupuesto</h2>
        <h5 class="mt-3">Presupuestos</h5>
        <div class="col">
            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                <label class="btn btn-outline-secondary">
                    <input type="radio" wire:click="cambioPresupuestos('aprobado')" value="solicitado" name="presupuestos" autocomplete="off">
                    Solicitado/Aprobado
                </label>

                <label class="btn btn-outline-secondary">
                    <input type="radio"  wire:click="cambioPresupuestos('modificado')" value="modificado" name="presupuestos"
                        autocomplete="off">
                    Modificado
                </label>

                <label class="btn btn-outline-secondary">
                    <input type="radio" wire:click="cambioPresupuestos('comprometido')" value="comprometido" name="presupuestos"
                        autocomplete="off"> Comprometido
                </label>

                <label class="btn btn-outline-secondary">
                    <input type="radio"  wire:click="cambioPresupuestos('devengado')" value="devengado" name="presupuestos"
                        autocomplete="off"> Devengado
                </label>

                <label class="btn btn-outline-secondary">
                    <input type="radio"  wire:click="cambioPresupuestos('ejercido')" value="ejercido" name="presupuestos"
                        autocomplete="off"> Ejercido
                </label>

                <label class="btn btn-outline-secondary">
                    <input type="radio"  wire:click="cambioPresupuestos('pagado')" value="pagado" name="presupuestos" autocomplete="off">
                    Pagado
                </label>
            </div>
        </div>
    </div>

    <div class="row m-3" @if ($this->tipo == '') hidden @endif>
        <h5 class="mt-3">Reportes</h5>
        <div class="col">
            <div class="nav nav-tabs" id="presupuestosTabs" role="tablist">
                <a @if ($this->seccion == 'ubpp') class="active nav-item nav-link text-secondary" @endif
                    wire:click="cambioSeccion('ubpp')" class="nav-item nav-link text-secondary" id="ubpp-tab"
                    data-toggle="tab" role="tab">Área</a>
                <a @if ($this->seccion == 'partida') class="active nav-item nav-link text-secondary" @endif
                    wire:click="cambioSeccion('partida')" class="nav-item nav-link text-secondary" id="partida-tab"
                    data-toggle="tab" role="tab">Partida</a>
                <a @if ($this->seccion == 'capitulo') class="active nav-item nav-link text-secondary" @endif
                    wire:click="cambioSeccion('capitulo')" class="nav-item nav-link text-secondary" id="capitulo-tab"
                    data-toggle="tab" role="tab">Capítulo</a>
                <a @if ($this->seccion == 'ubpp-partida') class="active nav-item nav-link text-secondary" @endif
                    wire:click="cambioSeccion('ubpp-partida')" class="nav-item nav-link text-secondary"
                    id="ubpp-partida-tab" data-toggle="tab" role="tab">Área/Partida</a>
            </div>

            <div class="tab-content">
                @if ($this->seccion == 'ubpp')
                    <div class="border-start border-end border-bottom p-5">
                        <div class="row">
                            <div class="col-4">
                                <label for="filtro" class="form-label">Filtro</label>
                                <select wire:model.live="filtro1" class="form-select" name="filtro" id="filtro"
                                    wire:change="change">
                                    <option value="0">Seleccionar filtro...</option>
                                    <option value="igual">Igual a</option>
                                    <option value="rango">En el rango</option>
                                </select>
                            </div>
                            <div class="col">
                                <div id="divUbpp" @if ($this->filtro1 == '0') class="d-none" @endif>
                                    <label class="form-label" for="ubpp">Área</label>
                                    <select wire:model.live="valor1" class="form-select" name="ubpp" id="ubpp">
                                        <option value="0" @if ($this->valor1 == '') selected @endif>
                                            Seleccionar Área</option>
                                        @foreach (\App\Models\CodigoDepartamento::all() as $departamento)
                                            @if (strlen($departamento->Codigo_completo) >= 5)
                                                <option value="{{ $departamento->Codigo_completo }}">
                                                    {{ $departamento->Codigo_completo . ' - ' . $departamento->Nombre }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div id="divUbppRango" @if ($this->filtro1 == '0' || $this->filtro1 == 'igual') class="d-none mt-3" @endif
                                    class="mt-3">
                                    <label class="form-label" for="ubppRango">Área Rango</label>
                                    <select wire:model.live="valor2" class="form-select" name="ubppRango"
                                        id="ubppRango">
                                        <option value="0" @if ($this->valor2 == '') selected @endif>
                                            Seleccionar Área</option>
                                        @foreach (\App\Models\CodigoDepartamento::all() as $departamento)
                                            @if (strlen($departamento->Codigo_completo) >= 5)
                                                <option value="{{ $departamento->Codigo_completo }}">
                                                    {{ $departamento->Codigo_completo . ' - ' . $departamento->Nombre }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($this->seccion == 'partida')
                    <div class="border-start border-end border-bottom p-5">
                        <div class="row">
                            <div class="col-4">
                                <label for="filtroPartida" class="form-label">Filtro</label>
                                <select wire:model.live="filtro1" class="form-select" name="filtro" id="filtro"
                                    wire:change="change">
                                    <option value="0">Seleccionar filtro...</option>
                                    <option value="igual">Igual a</option>
                                    <option value="rango">En el rango</option>
                                </select>
                            </div>
                            <div class="col">
                                <div id="divPartida" @if ($this->filtro1 == '0') class="d-none" @endif>
                                    <label class="form-label" for="partida">Partida</label>
                                    <select wire:model.live="valor1" class="form-select" name="partida"
                                        id="partida">
                                        <option value="0" @if ($this->valor1 == '') selected @endif>
                                            Seleccionar Partida</option>
                                        @foreach (\App\Models\Cuenta::join('CuentasCOG', 'CuentasCOG.codigoCuenta', '=', 'cuentas.Codigo_cuenta')->select('cuentas.*', 'CuentasCOG.*')->where('Descripcion_cuenta', 'like', '%(Aprobado)%')->orderBy('COG')->get() as $cuenta)
                                            <option value="{{ $cuenta->COG }}">
                                                {{ $cuenta->COG . ' - ' . $cuenta->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="divPartidaRango"
                                    @if ($this->filtro1 == '0' || $this->filtro1 == 'igual') class="d-none mt-3" @endif class="mt-3">
                                    <label class="form-label" for="partidaRango">Partida Rango</label>
                                    <select wire:model.live="valor2" class="form-select" name="partidaRango"
                                        id="partidaRango">
                                        <option value="0" @if ($this->valor2 == '') selected @endif>
                                            Seleccionar Partida</option>
                                        @foreach (\App\Models\Cuenta::join('CuentasCOG', 'CuentasCOG.codigoCuenta', '=', 'cuentas.Codigo_cuenta')->select('cuentas.*', 'CuentasCOG.*')->where('Descripcion_cuenta', 'like', '%(Aprobado)%')->orderBy('COG')->get() as $cuenta)
                                            <option value="{{ $cuenta->COG }}">
                                                {{ $cuenta->COG . ' - ' . $cuenta->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($this->seccion == 'capitulo')
                    <div class="border-start border-end border-bottom p-5">
                        <div class="row">
                            <div class="col-4">
                                <label for="filtroCapitulo" class="form-label">Filtro</label>
                                <select wire:model.live="filtro1" class="form-select" name="filtro" id="filtro"
                                    wire:change="change">
                                    <option value="0">Seleccionar filtro...</option>
                                    <option value="igual">Igual a</option>
                                    <option value="rango">En el rango</option>
                                </select>
                            </div>
                            <div class="col">
                                <div id="divCapitulo" @if ($this->filtro1 == '0') class="d-none" @endif>
                                    <label class="form-label" for="capitulo">Capítulo</label>
                                    <select wire:model.live="valor1" class="form-select" name="capitulo"
                                        id="capitulo">
                                        <option value="0" @if ($this->valor1 == '') selected @endif>
                                            Seleccionar capítulo</option>
                                        @for ($i = 1000; $i < 8000; $i += 1000)
                                            @if ($i != 6000)
                                                <option value="{{ $i }}">{{ $i }}</option>
                                            @endif
                                        @endfor
                                    </select>
                                </div>
                                <div id="divCapituloRango"
                                    @if ($this->filtro1 == '0' || $this->filtro1 == 'igual') class="d-none mt-3" @endif class="mt-3">
                                    <label class="form-label" for="capituloRango">Capítulo Rango</label>
                                    <select wire:model.live="valor2" class="form-select" name="capituloRango"
                                        id="capituloRango">
                                        <option value="0" @if ($this->valor2 == '') selected @endif>
                                            Seleccionar capítulo</option>
                                        @for ($i = 1000; $i < 8000; $i += 1000)
                                            @if ($i != 6000)
                                                <option value="{{ $i }}">{{ $i }}</option>
                                            @endif
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($this->seccion == 'ubpp-partida')
                    <div class="border-start border-end border-bottom p-5">
                        <div class="row border rounded">
                            <div class="col-3 m-3">
                                <label for="filtroUbppPartida" class="form-label">Filtro Área</label>
                                <select wire:model.live="filtro1" class="form-select" name="filtro" id="filtro"
                                    wire:change="change">
                                    <option value="0">Seleccionar filtro...</option>
                                    <option value="igual">Igual a</option>
                                    <option value="rango">En el rango</option>
                                </select>
                            </div>
                            <div class="col m-3">
                                <div class="row">
                                    <div id="divUbpp_partida"
                                        @if ($this->filtro1 == '0') class="d-none" @endif>
                                        <label class="form-label" for="ubpp_partida">Área</label>
                                        <select wire:model.live="valor1" class="form-select" name="ubpp_partida"
                                            id="ubpp_partida">
                                            <option value="0" @if ($this->valor1 == '') selected @endif>
                                                Seleccionar Área</option>
                                            @foreach (\App\Models\CodigoDepartamento::all() as $departamento)
                                                @if (strlen($departamento->Codigo_completo) >= 5)
                                                    <option value="{{ $departamento->Codigo_completo }}">
                                                        {{ $departamento->Codigo_completo . ' - ' . $departamento->Nombre }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div id="divUbpp_partidaRango"
                                        @if ($this->filtro1 == '0' || $this->filtro1 == 'igual') class="d-none mt-3" @endif class="mt-3">
                                        <label class="form-label" for="ubpp_partidaRango">Área Rango</label>
                                        <select wire:model.live="valor2" class="form-select" name="ubpp_partidaRango"
                                            id="ubpp_partidaRango">
                                            <option value="0" @if ($this->valor2 == '') selected @endif>
                                                Seleccionar Área</option>
                                            @foreach (\App\Models\CodigoDepartamento::all() as $departamento)
                                                @if (strlen($departamento->Codigo_completo) >= 5)
                                                    <option value="{{ $departamento->Codigo_completo }}">
                                                        {{ $departamento->Codigo_completo . ' - ' . $departamento->Nombre }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5 border rounded">
                            <div class="col-3 m-3">
                                <label for="filtroPartidaUbpp" class="form-label">Filtro Partida</label>
                                <select wire:model.live="filtro2" class="form-select" name="filtro" id="filtro"
                                    wire:change="change">
                                    <option value="0">Seleccionar filtro...</option>
                                    <option value="igual">Igual a</option>
                                    <option value="rango">En el rango</option>
                                </select>
                            </div>
                            <div class="col m-3">
                                <div class="row">
                                    <div id="divPartida_ubpp"
                                        @if ($this->filtro2 == '0') class="col d-none" @endif class="col">
                                        <label class="form-label" for="partida_ubpp">Partida</label>
                                        <select wire:model.live="valor3" class="form-select" name="partida_ubpp"
                                            id="partida_ubpp">
                                            <option value="0" @if ($this->valor3 == '') selected @endif>
                                                Seleccionar Partida</option>
                                            @foreach (\App\Models\Cuenta::join('CuentasCOG', 'CuentasCOG.codigoCuenta', '=', 'cuentas.Codigo_cuenta')->select('cuentas.*', 'CuentasCOG.*')->where('Descripcion_cuenta', 'like', '%(Aprobado)%')->orderBy('COG')->get() as $cuenta)
                                                <option value="{{ $cuenta->COG }}">
                                                    {{ $cuenta->COG . ' - ' . $cuenta->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div id="divPartida_ubppRango"
                                        @if ($this->filtro2 == '0' || $this->filtro2 == 'igual') class="d-none mt-3" @endif class="mt-3">
                                        <label class="form-label" for="partida_ubppRango">Partida Rango</label>
                                        <select wire:model.live="valor4" class="form-select" name="partida_ubppRango"
                                            id="partida_ubppRango">
                                            <option value="0" @if ($this->valor4 == '') selected @endif>
                                                Seleccionar Partida</option>
                                            @foreach (\App\Models\Cuenta::join('CuentasCOG', 'CuentasCOG.codigoCuenta', '=', 'cuentas.Codigo_cuenta')->select('cuentas.*', 'CuentasCOG.*')->where('Descripcion_cuenta', 'like', '%(Aprobado)%')->orderBy('COG')->get() as $cuenta)
                                                <option value="{{ $cuenta->COG }}">
                                                    {{ $cuenta->COG . ' - ' . $cuenta->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="mt-5 me-3 ms-3 row">
        <div class="col">
            <a href="/tiposPresupuesto" class="btn btn-outline-secondary">Limpiar campos</a>
        </div>
        <div class="col text-end">
            <button type="submit" onclick="descargar()" wire:click="reporte" class="btn btn_primario mb-4"
                id="descargar">Generar reporte</button>
        </div>
    </div>

    
</div>

<script>
    let btnHtml //obtenemos el contenido html de mi boton

    window.addEventListener("load", (event) => {
        btnHtml = $("#descargar").html();
    });
    window.addEventListener('descargar-reporte-tipo-presupuesto', event => {
        let mensajeEdoSolicitud = toastr.info("Procesando solicitud, espere un momento por favor . . .", "", {
            timeOut: "0"
        });

        fetch(event.__livewire.params.url, {
                method: "GET",
            })
            .then((response) => {
                if (!response.ok) {
                    toastr.error(
                        "Problemas al procesar la solicutd, por favor inténtelo más tarde"
                    );
                    mensajeEdoSolicitud.remove();
                } else {
                    response.text().then((reporte) => {
                        window.open(reporte);
                        mensajeEdoSolicitud.remove();
                    });
                }
                $('#loadingScreen').prop('hidden', true);
                $("#descargar").prop("disabled", false); //desbloqueamos el botón
                $("#descargar").html(btnHtml); // regresamos el contenido html original al botón
            })
            .catch((error) => {
                mensajeEdoSolicitud.remove();

                $("#descargar").prop("disabled", false); //desbloqueamos el botón
                $("#descargar").html(btnHtml); // regresamos el contenido html original al botón
                $('#loadingScreen').prop('hidden', true);

                toastr.error(
                    "Problemas al procesar la solicutd, por favor inténtelo más tarde"
                );
            });
    });

    function descargar() {
        $('#loadingScreen').prop('hidden', false);
        let spinner =
            '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
        $("#descargar").html(spinner);
        $("#descargar").prop("disabled", true);
    }
</script>

