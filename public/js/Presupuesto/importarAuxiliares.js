$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})

function seleccionAnio() {
    $('#input-archivo').prop('disabled', false);
}

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

function descargarPlantilla(btn, tipo) {
    let btnId = btn.id; 
    let btnHtml = $('#' + btnId + '').html();
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
        url: '/presupuesto/plantilla-presupuesto-inicial',
        data: { "type": tipo },
        success: function (data) {
            var blob = new Blob([data], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download =
                `Formato presupuesto inicial ${tipo}.xlsx`; 
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            $('#' + btnId + '').prop('disabled', false); 
            $('#' + btnId + '').html(btnHtml); 
            mensajeEdoSolicitud.remove()
            $('#loadingScreen').prop('hidden', true);

        },
        error: function () {
            toastr.error('Error al descargar la plantilla.');
            $('#loadingScreen').prop('hidden', true);

            $('#' + btnId + '').prop('disabled', false); 
            $('#' + btnId + '').html(btnHtml); 
        }
    });
}

function importarAuxiliares(btn) {
        let btnId = btn.id; 
        let btnHtml = $('#' + btnId + '').html(); 
        let spinner =
            '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
        $('#' + btnId + '').html(spinner);
        $('#' + btnId + '').prop('disabled', true);
        $('#formImportarAuxiliares').submit();
        $('#loadingScreen').prop('hidden', false);
        let mensajeEdoSolicitud = toastr.info("Procesando solicitud, este proceso puede tomar varios minutos, espere un momento por favor . . .", "", { timeOut: "0" });

    }
