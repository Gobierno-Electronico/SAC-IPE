// inicializa los tooltips
$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})

// selecciona el nivel que será la nueva cuenta
function seleccionarNivel() {
    var nivelSeleccionado = document.getElementById("nivel").value;

    ocultarTodosLosSelect();
    desocultarSelectNivel1(nivelSeleccionado);
    mostrarSelectsNiveles(nivelSeleccionado)
}

//muestra el select para seleccionar las cuentas del primer nivel
function desocultarSelectNivel1(cantidad) {
    var selectNivel1 = document.getElementById("selectNivel1")
    if (selectNivel1 != null && cantidad > 1) {
        selectNivel1.classList.remove('d-none');
    }
}

// muestra los demás selects para seleccionar cuentas (dependiendo del número de niveles que seleccione se despliegan cierto numero de selects)
function mostrarSelectsNiveles(cantidad) {
    for (let i = 2; i < cantidad; i++) {
        var elementoSelect = document.getElementById("div" + i)
        if (elementoSelect !== null) {
            elementoSelect.classList.remove('d-none')
        }
    }
}

//oculta todos lo select, esto ayuda a actualizar los selects mostrados si es que hay una re-seleccion en el select de nivel
function ocultarTodosLosSelect() {
    var selectNivel1 = document.getElementById("selectNivel1")
    if (selectNivel1 != null) {
        selectNivel1.classList.add('d-none');
    }

    for (let i = 2; i < 9; i++) {
        var selectNivel = document.getElementById('div' + i)
        if (selectNivel != null) {
            selectNivel.classList.add('d-none');

        }
    }
}

// variable en la cual se guarda lo que se construye en el campo de vista previa del codigo de cuenta
var preview = "";


// metodo general para llenar dinamicamente los selects que se muestran, recibe como parametro el elemento (select) que desencadena este metodo
function llenarSiguienteNivel(elementoSeleccionado) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // se obtiene el valor seleccionado del select que desencadeno este método
    var cuentaPadre = document.getElementById(elementoSeleccionado.id).value
    // se obtiene el id del select que desencadenó este método
    var nivel = elementoSeleccionado.id
    // se aumenta el nivel más uno, de esta forma se sabe cual es el nivel del siguiente select que será llenado dinamicamente
    //a raíz de lo que se seleccione en el select que desencadenó este método
    nivel++;

    console.log("Nivel: " + nivel + " Cuenta: " + cuentaPadre)

    var data = {
        cuentaPadre: cuentaPadre,
        nivel: nivel
    }

    var queryString = $.param(data)

    $.ajax({
        //se manda a llamar la ruta que extrae la información de la base de datos
        url: 'llenarSiguienteNivel?' + queryString,
        method: 'GET',
        // se maneja la respuesta del controlador
        success: function (response) {
            var cuentas = response.mensaje
            console.log(cuentas)

            // se construye lo que se muestra en el campo de vista previa del código de cuenta en base a lo que se vaya seleccionando en cada select
            preview = cuentaPadre + "."
            document.getElementById("txtPreview").value = preview

            // se obtiene el id del select que se va a llenar
            var selectALlenar = $("#" + nivel);
            // se elimina lo que el select a llenar tenga, esto hace que se actualice el select si se cambia la opción del select anterior
            selectALlenar.empty();

            if (cuentas.length > 0) {
                //se agrega la opción de Seleccionar cuenta... para que el usuario pueda darse cuenta que el select siguiente ya se llenó
                var optionInicial = $("<option disabled selected></option>");
                optionInicial.val(0);
                optionInicial.text("Seleccionar cuenta...");
                selectALlenar.append(optionInicial);

                // se agregan las demás opciones del select con la información que se extrajo de la base de datos
                for (var i = 0; i < cuentas.length; i++) {
                    var cuenta = cuentas[i];
                    var option = $("<option></option>");
                    option.val(cuenta.Codigo_cuenta);
                    option.text(cuenta.Codigo_cuenta + " - " + cuenta.Descripcion_cuenta);
                    selectALlenar.append(option);
                }
            } else {
                //Se ocultan los select que no se hayan llenado porque no hay cuentas para ese nivel
                for (let i = nivel; i < 7; i++) {
                    var selectNivel = document.getElementById('div' + i)
                    if (selectNivel != null) {
                        selectNivel.classList.add('d-none');
                    }
                }

                // se modifica el nivel seleccionado en el primer select, debido a que se ocultaron los select que no tenian cuenta
                var opcionNivelSeleccionado = document.getElementById("nivel");
                opcionNivelSeleccionado.value = nivel;

                // se notifica al usuario que para el siguiente nivel no hay cuentas registradas
                toastr.options = {
                    "progressBar": true,
                    "closeButton": true,
                    "positionClass": "toast-top-right",
                }
                toastr.info('Sin cuentas para el siguiente nivel')
            }

        },
        // se notifica al usuario si es que hay un error al obtener la información
        error: function (error) {
            console.log(error.responseText)
            toastr.options = {
                "progressBar": true,
                "closeButton": true,
                "positionClass": "toast-top-right",
            }
            toastr.error('Ocurrió un error al obtener las cuentas del nivel ' + nivel + ', intente más tarde')
        }
    })
}

function cocatenarPreview(event) {

    // Accede al valor del input usando event.target.value
    var textoIngresado = event.target.value;

    // concatena el texto ingresado dentro del preview para visualizar la estructura del código de cuenta
    var nuevoTexto = preview + textoIngresado;
    document.getElementById("txtPreview").value = nuevoTexto;
}

// llama a la ruta para agregar una cuenta
function agregarCuenta(btnSeleccionado) {

    //se personalizan las opciones de los mensajes al usuario
    toastr.options = {
        "progressBar": true,
        "closeButton": true,
        "positionClass": "toast-top-right",
    }

    //obtenemos la información que se va a registrar
    var txtCodigo = document.getElementById('txtCodigo').value
    var codigoCuenta = document.getElementById('txtPreview').value
    var descripcionCuenta = document.getElementById('txtDescripcion').value
    var valorCuentaRegistro = document.querySelector('input[name="btnCuentaRegistro"]:checked');
    if (valorCuentaRegistro) {
        // Verificar si un radio button está seleccionado
        var cuentaRegistro = valorCuentaRegistro.value;
        // Aquí puedes usar valorCuentaRegistro como desees
    } else {
        var cuentaRegistro = null
    }
    var clasificadorIngreso = document.getElementById('txtClasificadorIngreso').value
    var clasificadorGasto = document.getElementById('txtClasificadorGasto').value
    var nivel = document.getElementById('nivel').value

    // asignamos la información a una colección json para mandarla mediante ajax
    var datos = {
        txtCodigo: txtCodigo,
        codigoCuenta: codigoCuenta,
        descripcionCuenta: descripcionCuenta,
        cuentaRegistro: cuentaRegistro,
        clasificadorIngreso: clasificadorIngreso,
        clasificadorGasto: clasificadorGasto,
        nivel: nivel
    }

    var btnId = btnSeleccionado.id //obtenemos el id del botón seleccionado
    var btnHtml = $('#' + btnId).html(); // obtenemos el contenido html del boton
    var spinner = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>' +
                    '<span role="status">Cargando...</span></div>'
    $('#' + btnId + '').html(spinner); // agregamos el spinner a mi botón para el que el usuario vea que se esta ejecutando el reporte
    $('#' + btnId + '').prop('disabled', true);// bloqueamos el botón para evitar que el usuario hagas varios clics.

    if (txtCodigo == null || codigoCuenta == null || descripcionCuenta == null || cuentaRegistro == null || nivel == null) {
        toastr.warning('Los campos marcados con * son requeridos')
        $('#' + btnId + '').prop('disabled', false); //desbloqueamos el botón
        $('#' + btnId + '').html(btnHtml);// regresamos el contenido html original al botón
    } else {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            type: 'POST',
            url: '/cuentas/agregarCuenta',
            data: datos,
            success: function (response) {
                if (response.error != '') {
                    respuesta = response.error
                    toastr.error(respuesta)

                } else {
                    respuesta = response.mensaje
                    toastr.success(respuesta)
                }
                $('#' + btnId + '').prop('disabled', false); //desbloqueamos el botón
                $('#' + btnId + '').html(btnHtml);// regresamos el contenido html original al botón

            },
            error: function (error) {
                console.log(error.error);
                toastr.error(error.error)
                $('#' + btnId + '').prop('disabled', false); //desbloqueamos el botón
                $('#' + btnId + '').html(btnHtml);// regresamos el contenido html original al botón
            }
        })
    }
}
