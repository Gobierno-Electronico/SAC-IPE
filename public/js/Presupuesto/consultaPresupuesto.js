//const IP_PORT = '10.0.2.62:8080'
const IP_PORT = '10.0.0.12:8080'
//ReporteadorSAC-IPE
function generarPolizaPresupuestalIngresos(btn) {
    let url;
    let btnId = btn.id; //obtenemos el id del boton
    let excel = "";
    let btnHtml = $("#" + btnId + "").html(); //obtenemos el contenido html de mi boton
    let spinner =
        '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
    $("#" + btnId + "").html(spinner);
    $("#" + btnId + "").prop("disabled", true);

    nombrereporte = "PolizaPresupuestal";
    $('#loadingScreen').prop('hidden', false);
    const wsUrl = "http://"+IP_PORT+"/ReporteadorSAC-IPE/webresources/service/report?name=PolizaPresupuestalIngresos&params="
    url = `${wsUrl}Anio;${$('#Anio').val()},Tipo;${'P'},Fecha;${$('#botonFecha').val()},Hora;${$('#botonHora').val()},Numero;${$('#botonNumeroPoliza').val()},Concepto;${'PRESUPUESTO INICIAL ' + $('#Anio').val()}`;
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
            $('#loadingScreen').prop('hidden', true);

            $("#" + btnId + "").prop("disabled", false); //desbloqueamos el botón
            $("#" + btnId + "").html(btnHtml); // regresamos el contenido html original al botón
        })
        .catch((error) => {
            $("#" + btnId + "").prop("disabled", false); //desbloqueamos el botón
            $("#" + btnId + "").html(btnHtml); // regresamos el contenido html original al botón
            $('#loadingScreen').prop('hidden', true);

            toastr.error(
                "Problemas al procesar la solicutd, por favor inténtelo más tarde"
            );
        });
}


function generarPolizaPresupuestalEgresos(btn) {
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
    const wsUrl = "http://"+IP_PORT+"/ReporteadorSAC-IPE/webresources/service/report?name=PolizaPresupuestalEgresos&params="
    url = `${wsUrl}Anio;${$('#Anio').val()},Tipo;${'P'},Fecha;${$('#botonFecha').val()},Hora;${$('#botonHora').val()},Numero;${$('#botonNumeroPoliza').val()},Concepto;${'PRESUPUESTO INICIAL ' + $('#Anio').val()},Orden;${orden}`;
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
            $("#" + btnId + "").prop("disabled", false); //desbloqueamos el botón
            $("#" + btnId + "").html(btnHtml); // regresamos el contenido html original al botón
            $('#loadingScreen').prop('hidden', true);
            mensajeEdoSolicitud.remove();

            toastr.error(
                "Problemas al procesar la soliciutd, por favor inténtelo más tarde"
            );
        });
}