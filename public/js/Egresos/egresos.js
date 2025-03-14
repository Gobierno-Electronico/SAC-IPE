const IP_PORT = '10.0.2.62:8080'
function generarPoliza(btn) {
    let url;
    let btnId = btn.id; //obtenemos el id del boton
    let btnHtml = $("#" + btnId + "").html(); //obtenemos el contenido html de mi boton
    let spinner =
        '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
    $("#" + btnId + "").html(spinner);
    $("#" + btnId + "").prop("disabled", true);
    $('#loadingScreen').prop('hidden', false);

    nombrereporte = $('#botonMovimiento').val();
    console.log(nombrereporte)
    const wsUrl = "http://"+IP_PORT+"/Reporteador/webresources/service/report?name="+nombrereporte+"&params="
    url = `${wsUrl}Fecha;${$('#botonFecha').val()},Hora;${$('#botonHora').val()},Numero;${$('#botonNumeroPoliza').val()},Evento;${$('#botonEvento').val()}`;
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

function generarPolizaRemanente(btn, liberado) {
    var estatusRemanente = "REMANENTE NO LIBERADO";
    if(liberado == true){
        estatusRemanente = "REMANENTE LIBERADO"
    }
    let url;
    let btnId = btn.id; //obtenemos el id del boton
    let btnHtml = $("#" + btnId + "").html(); //obtenemos el contenido html de mi boton
    let spinner =
        '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
    $("#" + btnId + "").html(spinner);
    $("#" + btnId + "").prop("disabled", true);
    $('#loadingScreen').prop('hidden', false);

  console.log($('#botonRemanente').val())
    const wsUrl = "http://"+IP_PORT+"/Reporteador/webresources/service/report?name=PolizaEgresosRemanente&params="
    url = `${wsUrl}Fecha;${$('#botonFecha').val()},Hora;${$('#botonHora').val()},Evento;${$('#botonEvento').val()},categoriaRemanente;${$('#botonRemanente').val()}, estatusRemanente;${estatusRemanente}`;
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

function generarPolizaRemanenteLiberado(btn){
    let url;
    let btnId = btn.id; //obtenemos el id del boton
    let btnHtml = $("#" + btnId + "").html(); //obtenemos el contenido html de mi boton
    let spinner =
        '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
    $("#" + btnId + "").html(spinner);
    $("#" + btnId + "").prop("disabled", true);
    $('#loadingScreen').prop('hidden', false);

    var categoriaRemanente = 'LIBERACION ' + $('#botonRemanente').val()
    const wsUrl = "http://"+IP_PORT+"/Reporteador/webresources/service/report?name=PolizaEgresosRemanenteLiberado&params="
    url = `${wsUrl}Fecha;${$('#botonFecha').val()},Hora;${$('#botonHora').val()},Evento;${$('#botonEvento').val()},categoriaRemanente;${categoriaRemanente}`;
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

function cambioArchivo() {
    let archivo = document.getElementById('archivo')
    let nombreArchivo = ""
    let importarBoton = $("#importarBoton")
    if (archivo.files.length > 0) {
        nombreArchivo = archivo.files[0].name
        importarBoton.prop('disabled', false);
    } else {
        importarBoton.prop('disabled', true);
    }
    $("#fieldName").html(nombreArchivo);
}

function descargarPlantilla(btn, tipo) {
    let btnId = btn.id; //obtenemos el id del boton
    let btnHtml = $('#' + btnId + '').html(); //obtenemos el contenido html de mi boton
    let spinner =
        '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
    $('#' + btnId + '').html(spinner);
    $('#' + btnId + '').prop('disabled', true);
    $('#loadingScreen').prop('hidden', false);
    let mensajeEdoSolicitud = toastr.info("Procesando solicitud, espere un momento por favor . . .", "", { timeOut: "0" });

    $.ajaxSetup({
        xhrFields: {
            responseType: 'blob'
        }
    });
    $.ajax({
        type: 'GET',
        url: '/capitulo1/plantillaCompromiso1000',
        data: { "type": tipo },
        success: function (data) {
            var blob = new Blob([data], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download =
                `formatoCargaComprometido1000 ${tipo}.xlsx`; // Reemplaza 'nombre_del_archivo.xlsx' con el nombre de tu archivo
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            $('#' + btnId + '').prop('disabled', false); //desbloqueamos el botón
            $('#' + btnId + '').html(btnHtml); // regresamos el contenido html original al botón
            mensajeEdoSolicitud.remove()
            $('#loadingScreen').prop('hidden', true);

        },
        error: function () {
            toastr.error('Error al descargar la plantilla.');
            $('#loadingScreen').prop('hidden', true);

            $('#' + btnId + '').prop('disabled', false); //desbloqueamos el botón
            $('#' + btnId + '').html(btnHtml); // regresamos el contenido html original al botón
        }
    });
}