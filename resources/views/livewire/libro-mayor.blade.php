<div>
    <div wire:loading.delay.long>
        <div
            style='display: flex; justify-content: center; align-items: center; background-color: black; position: fixed; top: 0px; left: 0px; z-index: 9999; width: 100%; height: 100%; opacity: .75'>
            <div class="la-timer la-2x">
                <div></div>

            </div>
        </div>
    </div>
    <div class="pb-4 pt-3 h-auto">
        <div class="pb-4 pt-3 h-auto">
            <div class="row">
                <div class="col-4">
                    <label for="tipoCuenta" class="form-label">Tipo de cuenta</label>
                    <select name="tipoCuenta" id="tipoCuenta" class="form-select mb-3" wire:model.live="tipoCuenta">
                        <option value="" selected disabled>Seleccionar...</option>
                        <option value="Presupuestal">Presupuestal</option>
                        <option value="Contable">Contable</option>
                    </select>

                    <label for="buscadorCuenta" class="form-label">Buscar por cuenta</label>
                    <input type="text" name="buscadorCuenta" id="buscadorCuenta" class="form-control mb-3" placeholder="Código o Descripción de cuenta" wire:model.live.live="busquedaCuenta">

                    <label for="fechaInicio" class="form-label">Fecha Inicio</label>
                    <input type="date" name="fechaInicio" id="fechaInicio" class="form-control" wire:model.live="fechaInicio">
                </div>
                <div class="col-4">
                    <label for="nivel" class="form-label">Nivel</label>
                    <select name="nivel" id="nivel" class="form-select mb-3" wire:model.live="nivel">
                        <option value="" selected disabled>Seleccionar nivel...</option>
                        @for ($i = 1; $i < 6; $i++)
                            <option value="{{$i}}">Nivel {{$i}}</option>
                        @endfor
                    </select>

                    <label for="cuenta" class="form-label">Cuentas</label>
                    <select name="cuenta" id="cuenta" class="form-select mb-3" wire:model.live="cuenta">
                        <option value="" selected disabled>Seleccionar cuenta...</option>
                        @foreach ($cuentas as $cuenta)
                            <option value="{{$cuenta->id}}">{{$cuenta->Codigo_cuenta}} - {{$cuenta->Descripcion_cuenta}}</option>
                        @endforeach
                    </select>

                    <label for="fechaFin" class="form-label">Fecha Fin</label>
                    <input type="date" name="fechaFin" id="fechaFin" class="form-control" wire:model.live="fechaFin">
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex flex-row-reverse">
        <button id="botonGenerarLibro" wire:click="generar('PDF')" type="button"
            class="btn btn-success shadow border-1 mt-3 mt-md-0" @if ($tipoCuenta == '' || $fechaInicio == '' || $fechaFin == '' || $nivel == '' || $cuenta == '') disabled @endif>
            Generar libro mayor PDF
        </button>

        <button id="botonGenerarLibro" wire:click="generar('X')" type="button"
            class="btn btn-success shadow border-1 mt-3 mt-md-0 me-3" @if ($tipoCuenta == '' || $fechaInicio == '' || $fechaFin == '' || $nivel == '' || $cuenta == '') disabled @endif>
            Generar libro mayor EXCEL
        </button>
    </div>
</div>
<script>
    window.addEventListener('descargar', event => {
        let btnId = "botonGenerarLibro"
        let btnHtml = $("#botonGenerarLibro").html(); //obtenemos el contenido html de mi boton
        let spinner =
            '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
        $("#botonGenerarLibro").html(spinner);
        $("#botonGenerarLibro").prop("disabled", true);
        $('#loadingScreen').prop('hidden', false);
        let url = "http://" + window.IP_PORT + "/" + window.window.NOMBRE_REPORTEADOR +
            "/webresources/service/report?name=ReporteLibroMayor&params="
        url += event.__livewire.params.Params;
        let mensajeEdoSolicitud = toastr.info("Procesando solicitud, espere un momento por favor . . .", "", {
            timeOut: "0"
        });
        console.log($("#botonGenerarLibro").html());
        fetch(url, {
                method: "GET",
            })
            .then((response) => {
                if (!response.ok) {
                    toastr.error(
                        "Problemas al procesar la solicutd, por favor inténtelo más tarde"
                    );
                    mensajeEdoSolicitud.remove();
                } else {
                    response.text().then((PolizaPresupuestal) => {
                        window.open(PolizaPresupuestal);
                        mensajeEdoSolicitud.remove();
                    });
                }
                $("#botonGenerarLibro").prop("disabled", false); //desbloqueamos el botón
                $("#botonGenerarLibro").html(btnHtml); // regresamos el contenido html original al botón
                $('#loadingScreen').prop('hidden', true);

            })
            .catch((error) => {
                mensajeEdoSolicitud.remove();
                $("#botonGenerarLibro").prop("disabled", false); //desbloqueamos el botón
                $("#botonGenerarLibro").html(btnHtml); // regresamos el contenido html original al botón
                $('#loadingScreen').prop('hidden', true);

                toastr.error(
                    "Problemas al procesar la soliciutd, por favor inténtelo más tarde"
                );
            });

    });
</script>
