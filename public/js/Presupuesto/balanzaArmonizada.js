let IP_PORT = window.IP_PORT
let NOMBRE_REPORTEADOR = window.NOMBRE_REPORTEADOR

function generarBalanzaArmonizada(btn) {
    let url;
    let btnId = btn.id; //obtenemos el id del boton
    let excel = "";
    let btnHtml = $("#" + btnId + "").html(); //obtenemos el contenido html de mi boton
    let spinner =
        '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
    $("#" + btnId + "").html(spinner);
    $("#" + btnId + "").prop("disabled", true);
    $('#loadingScreen').prop('hidden', false);

    nombrereporte = "PolizaPresupuestal";
    var orden = 'IZQUIERDO';
    var capitulo = $('#capitulo').val()
    // switch (capitulo) {
    //     case '2':
    //         orden = 'DERECHO';
    //         break;
    //     case '3':
    //         orden = 'IZQUIERDO';
    //         break;
    //     default:
    //         break;
    // }
    const wsUrl = "http://"+IP_PORT+"/"+NOMBRE_REPORTEADOR+"/webresources/service/report?name=BalanzaArmonizada&params="
    url = `${wsUrl}Anio;${$('#Anio').val()},Grupo;${$('#botonGrupo').val()},Tipo;Reporte,Fecha;${$('#botonFecha').val()},Hora;${$('#botonHora').val()},Numero;${$('#botonNumeroPoliza').val()}"&formato=X`;
    let mensajeEdoSolicitud = toastr.info("Procesando solicitud, espere un momento por favor . . .", "", { timeOut: "0" });
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
            $("#" + btnId + "").prop("disabled", false); //desbloqueamos el botón
            $("#" + btnId + "").html(btnHtml); // regresamos el contenido html original al botón
            $('#loadingScreen').prop('hidden', true);

        })
        .catch((error) => {
            mensajeEdoSolicitud.remove();
            $("#" + btnId + "").prop("disabled", false); //desbloqueamos el botón
            $("#" + btnId + "").html(btnHtml); // regresamos el contenido html original al botón
            $('#loadingScreen').prop('hidden', true);

            toastr.error(
                "Problemas al procesar la soliciutd, por favor inténtelo más tarde"
            );
        });
}