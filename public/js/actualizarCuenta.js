function actualizarCuenta(btnSeleccionado) {
    var formData = new FormData($("#formCuenta")[0]);

    var toastEdoSolicitud = toastr.info("Procesando su solicitud...", {
        timeOut: 0,
        extendedTimeOut: 0,
    });

    toastr.options = {
        progressBar: true,
        closeButton: true,
        positionClass: "toast-top-right",
    };

    var btnId = btnSeleccionado.id;
    var btnHtml = $("#" + btnId).html();
    var spinner =
        '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>' +
        '<span role="status">  Cargando...</span></div>';
    $("#" + btnId).html(spinner);
    $("#" + btnId).prop("disabled", true);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });    

    $.ajax({
        type: "POST",
        url: "/cuentas/cambiosCuenta",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            toastr.clear(toastEdoSolicitud);
            if (response.error) {
                toastr.error(response.error);
            } else {
                toastr.success(response.mensaje);
            }
            $("#" + btnId).prop("disabled", false); 
            $("#" + btnId).html(btnHtml);
        },
        error: function (error) {
            console.log(error); 
            toastr.clear(toastEdoSolicitud);
            toastr.error("Ocurrió un error al momento de Actualizar la Cuenta, intente más tarde");
            $("#" + btnId).prop("disabled", false);
            $("#" + btnId).html(btnHtml);
        }
        
    });
}
