let IP_PORT = window.IP_PORT
let NOMBRE_REPORTEADOR = window.NOMBRE_REPORTEADOR


window.addEventListener('descargar', event => {
    let btnId = "botonGenerarPoliza"
    let btnHtml = $("#botonGenerarPoliza").html(); //obtenemos el contenido html de mi boton
    let spinner =
        '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
    $("#botonGenerarPoliza").html(spinner);
    $("#botonGenerarPoliza").prop("disabled", true);
    $('#loadingScreen').prop('hidden', false);
    let url = "http://" + window.IP_PORT + "/" + window.window.NOMBRE_REPORTEADOR +
        "/webresources/service/report?name=ReporteBalanzaArmonizada&params="
    url += event.__livewire.params.Params;
    let mensajeEdoSolicitud = toastr.info("Procesando solicitud, espere un momento por favor . . .", "", {
        timeOut: "0"
    });
    console.log($("#botonGenerarPoliza").html());
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
            $("#botonGenerarPoliza").prop("disabled", false); //desbloqueamos el botón
            $("#botonGenerarPoliza").html(btnHtml); // regresamos el contenido html original al botón
            $('#loadingScreen').prop('hidden', true);

        })
        .catch((error) => {
            mensajeEdoSolicitud.remove();
            $("#botonGenerarPoliza").prop("disabled", false); //desbloqueamos el botón
            $("#botonGenerarPoliza").html(btnHtml); // regresamos el contenido html original al botón
            $('#loadingScreen').prop('hidden', true);

            toastr.error(
                "Problemas al procesar la soliciutd, por favor inténtelo más tarde"
            );
        });

});
