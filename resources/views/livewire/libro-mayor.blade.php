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
            <div class="d-flex flex-row">
                <select class="rounded-1 shadow-sm border-0 w-25 me-3 select_presupuesto rounded-1 shadow-sm border-0"
                    name="Anio" id="Anio" wire:model.live="selectedYear">
                    @php
                        $currentYear = \Carbon\Carbon::now()->year;
                    @endphp
                    @for ($i = 2020; $i <= $currentYear; $i++)
                        <option @if ($currentYear === $i) selected @endif value="{{ $i }}">Año
                            {{ $i }}</option>
                    @endfor
                </select>
                <input type="date" id="fecha1" name="fecha1" class=" rounded-1 shadow-sm border-0 w-25 me-3 p-1"
                    wire:model.live="fecha1" value="">
                <input type="date" id="fecha2" name="fecha2" class=" rounded-1 shadow-sm border-0 w-25 me-3 p-1"
                    wire:model.live="fecha2" value="">
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex flex-row-reverse">
        <button id="botonGenerarLibro" wire:click="generar" type="button"
            class="btn btn-success shadow border-1 mt-3 mt-md-0" @if ($selectedYear == '' || $fecha1 == '' || $fecha2 == '') disabled @endif>
            Generar libro mayor
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
        let url = "http://10.0.2.59:8080/Reporteador/webresources/service/report?name=LibroMayor&params="
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
