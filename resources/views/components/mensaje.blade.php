@if (session()->has('message') && session()->has('message_type'))
    <script>
        var mensajeNotificacion = "<?php echo session('message'); ?>";
        var tiempo = "<?php echo session('message_time'); ?>";
        var tipoNotificacion = "<?php echo session('message_type'); ?>";
        if (tiempo == '') {
            tiempo = 5000
        }
        toastr.options.onclick = function() {}
        toastr.options.progressBar = true;
        switch (tipoNotificacion) {
            case 'success':
                toastr.success(mensajeNotificacion, '', { timeOut: tiempo });
                break;
            case 'warning':
                toastr.warning(mensajeNotificacion, '', { timeOut: tiempo });
                break;
            case 'error':
                toastr.error(mensajeNotificacion, '', { timeOut: tiempo });
                break;
            default:
                toastr.info(mensajeNotificacion, '', { timeOut: tiempo });
                break;
        }
    </script>
@endif
<script>

window.addEventListener('mostrarMensaje', event => {
       switch (event.detail.tipo) {
            case 'success':
                toastr.success(event.detail.mensaje, '', { timeOut: event.detail.tiempo });
                break;
            case 'warning':
                toastr.warning(event.detail.mensaje, '', { timeOut: event.detail.tiempo });
                break;
            case 'error':
                toastr.error(event.detail.mensaje, '', { timeOut: event.detail.tiempo });
                break;
            default:
                toastr.info(event.detail.mensaje, '', { timeOut: event.detail.tiempo });
                break;
        }
    });;;

</script>