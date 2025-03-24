$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})
function cambioArchivo() {
    let archivo = document.getElementById('input-archivo')
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

function descargarPlantilla(btn) {
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
        url: '/contabilidad/plantilla-poliza-inicial',
        success: function (data) {
            var blob = new Blob([data], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download =
                `Formato carga poliza inicial.xlsx`; // Reemplaza 'nombre_del_archivo.xlsx' con el nombre de tu archivo
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

function cargarPolizaInicial(btn) {
    let btnId = btn.id; //obtenemos el id del boton
    let btnHtml = $('#' + btnId + '').html(); //obtenemos el contenido html de mi boton
    let spinner =
        '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
    $('#' + btnId + '').html(spinner);
    $('#' + btnId + '').prop('disabled', true);
    $('#formCargarPolizaInicial').submit();
    $('#loadingScreen').prop('hidden', false);
    let mensajeEdoSolicitud = toastr.info("Procesando solicitud, este proceso puede tomar varios minutos, espere un momento por favor . . .", "", { timeOut: "0" });
}
