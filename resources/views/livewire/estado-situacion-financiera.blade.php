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
                <input type="date" id="fechaInicio" name="fechaInicio" class=" rounded-1 shadow-sm border-0 w-25 me-3 p-1"  wire:model.live="fechaInicio" value="" >
                <input type="date" id="fechaFin" name="fechaFin" class=" rounded-1 shadow-sm border-0 w-25 me-3 p-1"  wire:model.live="fechaFin" value="" >
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex flex-row-reverse">
            <button id="botonGenerarEstadoSituacionFinanciera" wire:click="generar('PDF')" type="button"
                class="btn btn-success shadow border-1 mt-3 mt-md-0" @if ($fechaInicio == "" || $fechaFin == "") disabled @endif>
                Generar Estado de Situación Financiera PDF
            </button>

             <button id="botonGenerarEstadoSituacionFinanciera" wire:click="generar('X')" type="button"
                class="btn btn-success shadow border-1 mt-3 mt-md-0 me-3" @if ($fechaInicio == "" || $fechaFin == "") disabled @endif>
                Generar Estado de Situación Financiera EXCEL
            </button>
    </div>
</div>
<script>
    window.addEventListener('descargar', event => {
        let btnId = "botonGenerarEstadoSituacionFinanciera"
        let btnHtml = $("#" + btnId + "").html(); 
        let spinner =
            '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
        $("#" + btnId + "").html(spinner);
        $("#" + btnId + "").prop("disabled", true);
        $('#loadingScreen').prop('hidden', false);
        let url = "http://" + window.IP_PORT + "/" + window.window.NOMBRE_REPORTEADOR + "/webresources/service/report?name=ReporteEstadoSituacionFinanciera&params=" 
        url += event.__livewire.params.Params;
        let mensajeEdoSolicitud = toastr.info("Procesando solicitud, espere un momento por favor . . .", "", { timeOut: "0" });
        console.log(url);
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
            $("#" + btnId + "").prop("disabled", false); 
            $("#" + btnId + "").html(btnHtml); 
            $('#loadingScreen').prop('hidden', true);

        })
        .catch((error) => {
            mensajeEdoSolicitud.remove();
            $("#" + btnId + "").prop("disabled", false); 
            $("#" + btnId + "").html(btnHtml); 
            $('#loadingScreen').prop('hidden', true);

            toastr.error(
                "Problemas al procesar la soliciutd, por favor inténtelo más tarde"
            );
        });

    });
</script>
