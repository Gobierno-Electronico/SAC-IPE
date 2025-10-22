<div>
    <div wire:loading.delay.long>
        <div
            style="display: flex; justify-content: center; align-items: center; background-color: black; position: fixed; top: 0; left: 0; z-index: 9999; width: 100%; height: 100%; opacity: .75;">
            <div class="la-timer la-2x">
                <div></div>
            </div>
        </div>
    </div>

    <div class="pb-4 pt-3 h-auto">
        <div class="d-flex flex-row gap-3">

            <div class="d-flex flex-column" style="flex: 1 1 30%;">
                <label for="filtroDescripcion" class="fw-bold mb-1">Buscar</label>
                <input type="text" id="filtroDescripcion" class="form-control rounded-1 shadow-sm border-0 p-2"
                    placeholder="Escriba..." wire:model.live="filtroDescripcion">
            </div>

            <div class="d-flex flex-column" style="flex: 1 1 40%;">
                <label for="cuenta" class="fw-bold mb-1">Cuenta</label>
                <select class="form-select rounded-1 shadow-sm border-0 p-2" id="cuenta" wire:model="cuenta">
                    <option value="">Seleccionar cuenta</option>
                    @foreach ($cuentas as $cuenta)
                        <option value="{{ $cuenta->id }}">
                            {{ $cuenta->Codigo_cuenta . '  ' . $cuenta->Descripcion_cuenta }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="d-flex flex-column" style="flex: 0 0 15%;">
                <label for="fechaInicio" class="fw-bold mb-1">Fecha inicial</label>
                <input type="date" id="fechaInicio" name="fechaInicio"
                    class="form-control rounded-1 shadow-sm border-0 p-2" wire:model="fechaInicio">
            </div>

            <div class="d-flex flex-column" style="flex: 0 0 15%;">
                <label for="fechaFin" class="fw-bold mb-1">Fecha final</label>
                <input type="date" id="fechaFin" name="fechaFin"
                    class="form-control rounded-1 shadow-sm border-0 p-2" wire:model="fechaFin">
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex flex-row-reverse">
        <button wire:click="generarEstadoCuenta('X')" type="button"
            class="btn btn-success shadow border-1 mt-3 mt-md-0 ms-3">
            Generar Excel
        </button>
        <button wire:click="generarEstadoCuenta('PDF')" type="button" class="btn btn-success shadow border-1 mt-3 mt-md-0">
            Generar PDF
        </button>
    </div>

</div>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('generarReporteJasper', (data) => {
        let { cuenta, fechaInicio, fechaFin, descripcionCuenta, formato } = data;

        const inicio = dayjs(fechaInicio);
        const fin = dayjs(fechaFin);

        const esMesCompleto = inicio.isSame(inicio.startOf("month"), "day") &&
            fin.isSame(fin.endOf("month"), "day") &&
            inicio.isSame(fin, "month");

        let nombrereporte = '';

        if(esMesCompleto){
            nombrereporte = 'ReporteEstadoDeCuenta';
        }else{
            nombrereporte = 'ReporteEstadoDeCuentaPorRango';
        }

        const wsUrl = "http://" + window.IP_PORT + "/" + window.NOMBRE_REPORTEADOR + "/webresources/service/report?name=" + nombrereporte + "&params=";

        let url = `${wsUrl}Cuenta;${cuenta},DescripcionCuenta;${descripcionCuenta},FechaInicio;${fechaInicio},FechaFin;${fechaFin}&formato=${formato}`;

        console.log("Llamando Jasper con URL:", url);

        let mensajeEdoSolicitud = toastr.info("Procesando solicitud, espere un momento...", "", { timeOut: "0" });

        fetch(url, { method: "GET" })
            .then((response) => {
                if (!response.ok) {
                    toastr.error("Problemas al procesar la solicitud");
                    mensajeEdoSolicitud.remove();
                } else {
                    response.text().then((reporteUrl) => {
                        window.open(reporteUrl);
                        mensajeEdoSolicitud.remove();
                    });
                }
            })
            .catch((error) => {
                mensajeEdoSolicitud.remove();
                toastr.error("Error al conectar con Jasper");
            });
    });
});
</script>