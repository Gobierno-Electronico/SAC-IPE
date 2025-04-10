let PORT = window.IP_PORT
let REPORTEADOR = window.NOMBRE_REPORTEADOR

console.log(PORT)
console.log(REPORTEADOR)

function generarPolizaReclasificacion(btn){
    let url;
    let btnId = btn.id; //obtenemos el id del boton
    let excel = "";
    let btnHtml = $("#" + btnId + "").html(); //obtenemos el contenido html de mi boton
    let spinner =
        '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
    $("#" + btnId + "").html(spinner);
    $("#" + btnId + "").prop("disabled", true);
    $('#loadingScreen').prop('hidden', false);

    nombrereporte = $('#botonMovimiento').val();
    console.log(nombrereporte)
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
    const wsUrl = "http://"+PORT+"/"+REPORTEADOR+"/webresources/service/report?name="+nombrereporte+"&params="
    url = `${wsUrl}Fecha;${$('#botonFecha').val()},Hora;${$('#botonHora').val()},Numero;${$('#botonNumeroPoliza').val()},Evento;${$('#botonEvento').val()}`;
    let mensajeEdoSolicitud = toastr.info("Procesando solicitud, espere un momento por favor . . .", "", { timeOut: "0" });
    console.log("url ", url);
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