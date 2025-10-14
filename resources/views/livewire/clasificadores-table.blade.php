<div class="d-flex flex-column gap-3">
    <div class="shadow rounded">
        <div class="table-responsive">

            <table class="table small text-gray-500">
                <thead class="text-gray-700 text-uppercase bg-light">
                    <tr>
                        @foreach ($this->columns() as $column)
                            <th wire:click="sort('{{ $column->key }}')">
                                <div class="py-2 px-3 d-flex align-items-center">
                                    <a class=" text-black text-decoration-none" href="#"> {{ $column->label }}
                                    </a>
                                    @if ($sortBy === $column->key)
                                        @if ($sortDirection === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="flecha_orden"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="flecha_orden"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    @endif
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datos as $row)
                        <tr class="hover:bg-light">
                            @foreach ($this->columns() as $column)
                                <td class="px-4 align-middle cursor-pointer">
                                    <x-dynamic-component :component="$column->component" :value="$row[$column->key]" />
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>


            </table>
        </div>
        
    </div>
    <div>
        {{ $datos->links() }}
        
        <button id="botonMovimiento" value="{{ $tipo }}" hidden></button>
        <button id="botonFecha" value="{{ $fecha }}" hidden></button>
        <button id="botonHora" value="{{ $hora }}" hidden></button>
        <button id="botonTitulo" value="{{ $titulo }}" hidden></button>

        <div class="text-end mt-3">
            <button id="botonPoliza" onclick="generarPDF(this)" type="button"
                class="btn btn-success shadow border-1 mt-3 mt-md-0">
                Generar reporte
            </button>
        </div>
    </div>



    <script>
        let IP_PORT = window.IP_PORT
        let NOMBRE_REPORTEADOR = window.NOMBRE_REPORTEADOR

        function generarPDF(btn) {
            let url;
            let btnId = btn.id; //obtenemos el id del boton
            let btnHtml = $("#" + btnId + "").html(); //obtenemos el contenido html de mi boton
            let spinner =
                '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
            $("#" + btnId + "").html(spinner);
            $("#" + btnId + "").prop("disabled", true);
            $('#loadingScreen').prop('hidden', false);

            let nombreReporte = '';
            tipoClasificador = $('#botonMovimiento').val();
            switch (tipoClasificador) {
                case 'CA':  
                case 'CP':
                case 'CFG':
                case 'CTG':
                    nombreReporte = 'ReporteClasificadoresEgresos'
                    break
                case 'COG':
                    nombreReporte = 'ReporteClasificadoresCOG'
                    break
                case 'CFF':
                case 'CRI':
                    nombreReporte = 'ReporteClasificadoresIngresos'
                    break
            }
            console.log(nombreReporte)

            const wsUrl = "http://" + IP_PORT + "/" + NOMBRE_REPORTEADOR + "/webresources/service/report?name=" +
                nombreReporte + "&params="
            url =
                `${wsUrl}Fecha;${$('#botonFecha').val()},Hora;${$('#botonHora').val()},Titulo;${$('#botonTitulo').val()},Tipo;${tipoClasificador}`;
            let mensajeEdoSolicitud = toastr.info("Procesando solicitud, espere un momento por favor . . .", "", {
                timeOut: "0"
            });
                    console.log("URL generada:", url);

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
    </script>

</div>
