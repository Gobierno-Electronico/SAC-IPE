function importarExcel(btnSeleccionado) {

    var formData = new FormData(); // Crear un objeto FormData
    var fileInput = document.getElementById('file'); // Obtener el elemento de entrada de archivo
    var file = fileInput.files[0]; // Obtener el archivo seleccionado
    if(file != null){
        var toastEdoSolicitud = toastr.info('Procesando su solicitud...', {
            timeOut: 0,
            extendedTimeOut: 0
        });
        formData.append('file', file); // Agregar el archivo al objeto FormData

        // Personalizar las opciones de los mensajes al usuario
        toastr.options = {
            "progressBar": false,
            "closeButton": true,
            "positionClass": "toast-top-right",
        }
        $('#loadingScreen').prop('hidden', false);
        var btnId = btnSeleccionado.id; // Obtener el id del botón seleccionado
        var btnHtml = $('#' + btnId).html(); // Obtener el contenido html del botón
        var spinner = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>' +
            '<span role="status">  Cargando...</span></div>'
        $('#' + btnId).html(spinner); // Agregar el spinner al botón para que el usuario vea que se está ejecutando el reporte
        $('#' + btnId).prop('disabled', true); // Bloquear el botón para evitar que el usuario haga varios clics.

        // Configurar el token CSRF
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Realizar la solicitud AJAX con el objeto FormData
        $.ajax({
            type: 'POST',
            url: '/importarExcel',
            data: formData, // Usar el objeto FormData en lugar de 'datos'
            processData: false, // Evitar que jQuery procese los datos
            contentType: false, // Evitar que jQuery configure automáticamente el encabezado Content-Type
            success: function (response) {
                console.log(response)
                $('#loadingScreen').prop('hidden', true);

                if (response.error != '') {
                    toastr.clear(toastEdoSolicitud);
                    respuesta = response.error
                    toastr.error(respuesta)

                } else {
                    toastr.clear(toastEdoSolicitud);
                    respuesta = response.mensaje
                    toastr.success(respuesta)
                }  

                $('#' + btnId).prop('disabled', false); // Desbloquear el botón
                $('#' + btnId).html(btnHtml); // Restaurar el contenido html original del botón
            },
            error: function (error) {
                console.log(error)
                toastr.clear(toastEdoSolicitud);
                console.log(error.error);
                toastr.error(error.error)
                $('#' + btnId).prop('disabled', false); // Desbloquear el botón
                $('#' + btnId).html(btnHtml); // Restaurar el contenido html original del botón
                $('#loadingScreen').prop('hidden', true);

            }
        });
    }else{
        // Personalizar las opciones de los mensajes al usuario
        toastr.options = {
            "progressBar": true,
            "closeButton": true,
            "positionClass": "toast-top-right",
        }
        toastr.warning("Seleccione un archivo excel");
    }
}
