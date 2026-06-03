<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('vendor/dayjs/dayjs.min.js') }}"></script>

    <!-- Scripts -->
    <script>
        window.IP_PORT = @json(config('app.ip_port'));
        window.NOMBRE_REPORTEADOR = @json(config('app.nombre_reporteador'));
    </script>
    <script src="{{ asset('js/anio.js') }}"></script>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @vite(['resources/css/layouts/app.css', 'resources/css/layouts/loading.css', 'resources/css/layouts/loadingDots.css'])
    <link rel="stylesheet" href="{{ asset('vendor/toastr/toastr.min.css') }}">
    <script>
        window.añoSelect = null;
    </script>

</head>

<body class="d-flex flex-column min-vh-100">

    <style>

        .bg_secundario{
            background-color: #a8253d62
        }
        .bg_primario{
            background-color: #7A1737; 
        }
        .btn_primario{
            background-color: #7A1737; 
            color: white;
        }
        .btn_primario:hover{
            background-color: #500b21;
            color: white;
        }
        /* Color para el título del sistema (Brand) */
        .navbar-light .navbar-brand {
            color: #7A1737 !important;
            font-weight: bold;
        }

        /* Color para los links del menú */
        .navbar-light .navbar-nav .nav-link{
            color: #A8253C !important;
        }

        /* Efecto al pasar el mouse (un poco más claro o con opacidad) */
        .navbar-light .navbar-nav .nav-link:hover {
            color: #7A1737;
            opacity: 0.8;
        }
    </style>
    <div id="app">

        @if (!$hayPresupuestoCompleto || !$haySaldosIniciales)
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    mostrarMensajeAperturaSistema();
                });
            </script>
        @endif

        <nav class="navbar navbar-expand-sm navbar-light bg-white shadow-sm">
            <div class="ms-3 flex-grow-1">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                    <img src="{{ asset('imagenes/logo_sac_ipe_1.png') }}" alt="Logo SAC-IPE" class="me-3"
                        style="height: 70px; width: auto;">

                    <div class="d-flex flex-column border-start ps-3"
                        style="border-color: rgba(192, 192, 192, 0.4) !important;">
                        <span class="fw-bold" style="color: #7A1737; line-height: 1.2;">
                            Sistema de <br> Armonización Contable
                        </span>
                        <small class="text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">SAC-IPE</small>
                    </div>
                </a>
            </div>
            <div class="container-fluid w-auto me-3">

                @auth
                    <li class="nav-item d-flex align-items-center me-2">
                        <span id="ejercicioActual" class="fw-bold"
                            style="white-space: nowrap; display: none; color: #7A1737;">
                            Ejercicio actual: <span id="anioNavbar"></span>
                        </span>
                    </li>
                    <li class="nav-item d-none d-md-block mx-2" 
        style="border-left: 2px solid rgba(122, 23, 55, 0.2); height: 30px; align-self: center; border-radius: 2px;">
    </li>
                @endauth

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Iniciar sesión') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                @can('catalogos')
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                        aria-expanded="false" v-pre>
                                        {{ __('Catálogos') }}
                                    </a>
                                @endcan

                                <ul class="dropdown-menu">
                                    <li class="nav-item dropdown">
                                        @can('catalogos.cuentas')
                                            <a id="navbarCuentas" class="dropdown-item" href="/cuentas"
                                                onclick="mostrarCargando()" role="button">
                                                {{ __('Cuentas') }}</a>
                                        @endcan
                                    </li>
                                    <li class="nav-item dropdown">
                                        @can('catalogos.clasificador_administrativo')
                                            <a class="dropdown-item" href="/catalogos/CA" id="navbarCA"
                                                onclick="mostrarCargando()" role="button">
                                                {{ __('Clasificador Administrativo') }}
                                            </a>
                                        @endcan
                                    </li>
                                    <li class="nav-item dropdown">
                                        @can('catalogos.clasificador_programatico')
                                            <a class="dropdown-item" href="/catalogos/CP" id="navbarCP"
                                                onclick="mostrarCargando()" role="button">
                                                {{ __('Clasificador Programático') }}
                                            </a>
                                        @endcan
                                    </li>
                                    <li class="nav-item dropdown">
                                        @can('catalogos.clasificador_funcional_gasto')
                                            <a class="dropdown-item" href="/catalogos/CFG" id="navbarCFG"
                                                onclick="mostrarCargando()" role="button">
                                                {{ __('Clasificador Funcional Gasto') }}
                                            </a>
                                        @endcan
                                    </li>
                                    <li class="nav-item dropdown">
                                        @can('catalogos.clasificador_tipo_gasto')
                                            <a class="dropdown-item" href="/catalogos/CTG" id="navbarCTG"
                                                onclick="mostrarCargando()" role="button">
                                                {{ __('Clasificador Tipo Gasto') }}
                                            </a>
                                        @endcan
                                    </li>
                                    <li class="nav-item dropdown">
                                        @can('catalogos.clasificador_objeto_gasto')
                                            <a class="dropdown-item" href="/catalogos/COG" id="navbarCOG"
                                                onclick="mostrarCargando()" role="button">
                                                {{ __('Clasificador Objeto Gasto') }}
                                            </a>
                                        @endcan
                                    </li>
                                    <li class="nav-item dropdown">
                                        @can('catalogos.clasificador_fuente_financiamiento')
                                            <a class="dropdown-item" href="/catalogos/CFF" id="navbarCFF"
                                                onclick="mostrarCargando()" role="button">
                                                {{ __('Clasificador Fuente Financiamiento') }}
                                            </a>
                                        @endcan
                                    </li>
                                    <li class="nav-item dropdown">
                                        @can('catalogos.clasificador_rubro_ingreso')
                                            <a class="dropdown-item" href="/catalogos/CRI" id="navbarCRI"
                                                onclick="mostrarCargando()" role="button">
                                                {{ __('Clasificador Rubro Ingreso') }}
                                            </a>
                                        @endcan
                                    </li>

                                    <li class="dropdown dropend">
                                        @can('catalogos.matrices_conversion')
                                            <a href="#" class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown"
                                                role="button">
                                                Matrices de Conversión
                                            </a>
                                        @endcan
                                        <ul class="dropdown-menu">
                                            <li class="nav-item dropdown">
                                                <a class="dropdown-item" href="{{ route('cargarMatriz') }}"
                                                    id="navbarMatriz" onclick="mostrarCargando()" role="button">
                                                    {{ __('Carga') }}
                                                </a>
                                            </li>
                                            <li class="nav-item dropdown">
                                                <a class="dropdown-item" href="{{ route('consultarMatriz') }}"
                                                    id="navbarCRI" onclick="mostrarCargando()" role="button">
                                                    {{ __('Consulta') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li>

                            </li>
                        </ul>
                        </li>

                        <li class="nav-item dropdown">
                            @can('presupuesto')
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                    aria-expanded="false" v-pre>
                                    {{ __('Presupuesto') }}
                                </a>
                            @endcan

                            <ul class="dropdown-menu">
                                <li class="dropend">
                                    @can('presupuesto.cargar_presupuesto')
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Cargar
                                            Presupuesto</a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('presupuesto.cargar_presupuesto.ingresos')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('presupuestoInicialIngresos') }}" method="GET">
                                                    {{ __('Ingresos') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('presupuesto.cargar_presupuesto.egresos')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('presupuestoInicialEgresos') }}" method="GET">
                                                    {{ __('Egresos') }}
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>
                                <li class="dropend">
                                    @can('presupuesto.consulta_presupuesto')
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Consultar
                                            Presupuesto</a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('presupuesto.consulta_presupuesto.ingresos')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('consultaPresupuestoIngresos') }}" method="GET">
                                                    {{ __('Ingresos') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('presupuesto.consulta_presupuesto.egresos')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('consultaPresupuestoEgresos') }}" method="GET">
                                                    {{ __('Egresos') }}
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>
                                <li class="dropend">
                                    @can('presupuesto.consultas')
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Consultas</a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('presupuesto.consultas.tipos_de_presupuesto')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('tiposPresupuesto') }}" method="GET">
                                                    {{ __('Tipos de presupuesto') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('presupuesto.consultas.ampliaciones-reducciones')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    wire:navigate.hover href="{{ route('consultaAmpliacionesReducciones') }}"
                                                    method="GET">
                                                    {{ __('Ampliaciones/Reducciones') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('presupuesto.consultas.consultar_transferencias')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    wire:navigate.hover href="{{ route('consultaTransferencias') }}"
                                                    method="GET">
                                                    {{ __('Consultar tranferencias') }}
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>
                                <li class="dropend">
                                    @can('presupuesto.afectaciones_ingresos')
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Afectaciones
                                            ingresos</a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('presupuesto.afectaciones_ingresos.ampliacion')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('ampliacionIngresos') }}" method="GET">
                                                    {{ __('Ampliación') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('presupuesto.afectaciones_ingresos.reduccion')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('reduccionIngresos') }}" method="GET">
                                                    {{ __('Reducción') }}
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>
                                <li class="dropend">
                                    @can('presupuesto.afectaciones_egresos')
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Afectaciones
                                            egresos</a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('presupuesto.afectaciones_egresos.ampliacion')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('ampliacionEgresos') }}" method="GET">
                                                    {{ __('Ampliación') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('presupuesto.afectaciones_egresos.reduccion')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('reduccionEgresos') }}" method="GET">
                                                    {{ __('Reducción') }}
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    @can('presupuesto.reclasificacion-recalendarizacion')
                                        <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                            href="{{ route('recalendarizacion') }}" method="GET">
                                            {{ __('Reclasificación/Recalendarización') }}
                                        </a>
                                    @endcan
                                </li>
                            </ul>
                        </li>


                        <li class="nav-item dropdown">
                            @can('contabilidad')
                                @if ($hayPresupuestoCompleto)
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                        aria-expanded="false" v-pre>
                                        {{ __('Contabilidad') }}
                                    </a>
                                @else
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle disabled" href="#"
                                        role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                        aria-haspopup="true" aria-expanded="false" v-pre disabled>
                                        {{ __('Contabilidad') }}
                                    </a>
                                @endif
                            @endcan

                            <ul class="dropdown-menu">
                                <li class="dropend">
                                    @can('contabilidad.reportes')
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Reportes</a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('contabilidad.reportes.balanza_armonizada')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('balanzaArmonizada') }}" method="GET">
                                                    {{ __('Balanza armonizada') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('contabilidad.reportes.libro_mayor')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('libroMayor') }}" method="GET">
                                                    {{ __('Libro mayor') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('contabilidad.reportes.libro_diario')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('libroDiario') }}" method="GET">
                                                    {{ __('Libro diario') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('contabilidad.reportes.estado_de_cuenta')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('estadoCuenta') }}" method="GET">
                                                    {{ __('Estado de cuenta') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('contabilidad.reportes.estado_de_actividades')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('estadoActividades') }}" method="GET">
                                                    {{ __('Estado de actividades') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('contabilidad.reportes.estado_de_situacion_financiera')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('estadoSituacionFinanciera') }}" method="GET">
                                                    {{ __('Estado de situación financiera') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('contabilidad.reportes.estado_de_cambios_en_la_situacion_financiera')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('estadoCambiosSituacionFinanciera') }}" method="GET">
                                                    {{ __('Estado de cambios en la situación financiera') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('contabilidad.reportes.estado_de_analitico_del_activo')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('estadoAnaliticoDelActivo') }}" method="GET">
                                                    {{ __('Estado de analítico del activo') }}
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>


                                <li class="dropend">
                                    @can('contabilidad.consultar')
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Consultar</a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('contabilidad.consultar.poliza_inicial')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('consultaPolizaInicial') }}" method="GET">
                                                    {{ __('Póliza inicial') }}
                                                </a>
                                            @endcan
                                            @can('contabilidad.consultar.poliza_diario')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('movimientosDiario') }}" method="GET">
                                                    {{ __('Póliza diario') }} </a>
                                            @endcan
                                            @can('contabilidad.consultar.deudores')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('movimientosDeudores') }}" method="GET">
                                                    {{ __('Deudores') }}
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>



                                <li class="dropend">
                                    @can('contabilidad.carga')
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Carga</a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('contabilidad.carga.poliza_inicial')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('polizaInicial') }}" method="GET">
                                                    {{ __('Póliza inicial') }}
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('contabilidad.carga.poliza_diario')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('registroPolizaDiario') }}">
                                                    Póliza diario
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('contabilidad.carga.auxiliares')
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('auxiliares') }}">
                                                    Auxiliares
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>

                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            @can('ingresos')
                                @if ($haySaldosIniciales)
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" role="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                        aria-expanded="false" v-pre>
                                        {{ __('Ingresos') }}
                                    </a>
                                @else
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle disabled" role="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                        aria-expanded="false" v-pre disabled>
                                        {{ __('Ingresos') }}
                                    </a>
                                @endif
                            @endcan

                            <ul class="dropdown-menu">

                                <li class="dropend">
                                    @can('ingresos.devengado')
                                        <a href="{{ route('ingresosDevengado') }}" method="GET" class="dropdown-item "
                                            onclick="mostrarCargando()">Devengado</a>
                                    @endcan
                                </li>

                                <li class="dropend">
                                    @can('ingresos.recaudado')
                                        <a href="{{ route('ingresosRecaudado') }}" method="GET" class="dropdown-item"
                                            onclick="mostrarCargando()">Recaudado</a>
                                    @endcan
                                </li>

                                {{-- Las funcionalidades comentadas a continuación estaban en desarrollo pero realizando un
                            análisis junto con el equipo de contabilidad se llegó a la conclusión de que no formarían
                            parte del alcance de la primera versión del sistema, aunque en versiones siguientes si serán
                            desarrolladas, por lo cual, no se deben eliminar del sistema, simplemente inhabilitarlas un
                            tiempo --}}
                                {{-- <li class="dropend">
                                <a class="dropdown-item dropdown-toggle" href=""
                                    data-bs-toggle="dropdown">Devolución</a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{route('autorizacionDevolucion')}}" method="GET"
                                            onclick="mostrarCargando()">
                                            Autorización
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{route('pagoDevolucion')}}" method="GET"
                                            onclick="mostrarCargando()">
                                            Pago
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="dropend">
                                <a class="dropdown-item dropdown-toggle" href="" data-bs-toggle="dropdown">Reintegro</a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{route('autorizacionReintegro')}}" method="GET"
                                            onclick="mostrarCargando()">
                                            Autorización
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{route('pagoReintegro')}}" method="GET"
                                            onclick="mostrarCargando()">
                                            Pago
                                        </a>
                                    </li>
                                </ul>
                            </li> --}}

                                <li class="dropend">
                                    @can('ingresos.cobro_en_especie')
                                        <a class="dropdown-item" href="{{ route('cobroEspecie') }}" method="GET"
                                            onclick="mostrarCargando()">
                                            Cobro en especie
                                        </a>
                                    @endcan

                                    {{--
                            <li>
                                <a class="dropdown-item" href="{{route('devolucionEspecie')}}" method="GET"
                                    onclick="mostrarCargando()">
                                    Devolución
                                </a>
                            </li> --}}

                                </li>
                                <li class="dropend">
                                    @can('ingresos.ingresos_por_clasificar')
                                        <a href="{{ route('ingresosPorClasificar') }}" method="GET" class="dropdown-item"
                                            onclick="mostrarCargando()">Ingresos por clasificar</a>
                                    @endcan
                                </li>
                                <li class="dropend">
                                    @can('ingresos.depositos_en_bancos')
                                        <a href="{{ route('depositosBancos') }}" method="GET" class="dropdown-item"
                                            onclick="mostrarCargando()">Depósitos en bancos</a>
                                    @endcan
                                </li>
                                <li class="dropend">
                                    @can('ingresos.devengado_prev_recaudado')
                                        <a href="{{ route('devengadoRecaudado') }}" method="GET" class="dropdown-item"
                                            onclick="mostrarCargando()">Devengado prev. recaudado</a>
                                    @endcan
                                </li>

                                <li class="dropend">
                                    @can('ingresos.devengado_prev_recaudado_ejercicios_anteriores')
                                        <a href="{{ route('devengadoRecaudadoEjerciciosAnteriores') }}" method="GET"
                                            class="dropdown-item" onclick="mostrarCargando()">Devengado prev. recaudado
                                            ejercicios anteriores</a>
                                    @endcan
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            @can('egresos')
                                @if ($haySaldosIniciales)
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                        aria-expanded="false" v-pre>
                                        {{ __('Egresos') }}
                                    </a>
                                @else
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle disabled" href="#"
                                        role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                        aria-haspopup="true" aria-expanded="false" v-pre disabled>
                                        {{ __('Egresos') }}
                                    </a>
                                @endif
                            @endcan

                            <ul class="dropdown-menu">
                                <li class="dropend">
                                    @can('egresos.capitulo1000')
                                        <a class="dropdown-item dropdown-toggle" href="" data-bs-toggle="dropdown">
                                            Capítulo 1000 Servicios personales
                                        </a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('egresos.capitulo1000.comprometido')
                                                <a class="dropdown-item" href="{{ route('capitulo1Comprometido') }}"
                                                    onclick="mostrarCargando()">
                                                    Comprometido
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('egresos.capitulo1000.devengado')
                                                <a class="dropdown-item" href="{{ route('capitulo1Devengado') }}"
                                                    onclick="mostrarCargando()">
                                                    Devengado
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('egresos.capitulo1000.ejercido')
                                                <a class="dropdown-item" href="{{ route('capitulo1Ejercido') }}"
                                                    onclick="mostrarCargando()">
                                                    Ejercido
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('egresos.capitulo1000.pagado')
                                                <a class="dropdown-item" href="{{ route('capitulo1Pagado') }}"
                                                    onclick="mostrarCargando()">
                                                    Pagado
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>


                                <li class="dropend">
                                    @can('egresos.capitulo2000y3000')
                                        <a class="dropdown-item dropdown-toggle" href="" data-bs-toggle="dropdown">
                                            Capítulo 2000 y 3000 Materiales y servicios
                                        </a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('egresos.capitulo2000y3000.comprometido')
                                                <a class="dropdown-item" href="{{ route('capitulo2y3Comprometido') }}"
                                                    onclick="mostrarCargando()">
                                                    Comprometido
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('egresos.capitulo2000y3000.devengado')
                                                <a class="dropdown-item" href="{{ route('capitulo2y3Devengado') }}"
                                                    onclick="mostrarCargando()">
                                                    Devengado
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('egresos.capitulo2000y3000.ejercido')
                                                <a class="dropdown-item" href="{{ route('capitulo2y3Ejercido') }}"
                                                    onclick="mostrarCargando()">
                                                    Ejercido
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('egresos.capitulo2000y3000.pagado')
                                                <a class="dropdown-item" href="{{ route('capitulo2y3Pagado') }}"
                                                    onclick="mostrarCargando()">
                                                    Pagado
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>


                                <li class="dropend">
                                    @can('egresos.capitulo4000')
                                        <a class="dropdown-item dropdown-toggle" href="" data-bs-toggle="dropdown">
                                            Capítulo 4000 Transferencias y Pensiones
                                        </a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('egresos.capitulo4000.comprometido')
                                                <a class="dropdown-item" href="{{ route('capitulo4Comprometido') }}"
                                                    onclick="mostrarCargando()">
                                                    Comprometido
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('egresos.capitulo4000.devengado')
                                                <a class="dropdown-item" href="{{ route('capitulo4Devengado') }}"
                                                    onclick="mostrarCargando()">
                                                    Devengado
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('egresos.capitulo4000.ejercido')
                                                <a class="dropdown-item" href="{{ route('capitulo4Ejercido') }}"
                                                    onclick="mostrarCargando()">
                                                    Ejercido
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('egresos.capitulo4000.pagado')
                                                <a class="dropdown-item" href="{{ route('capitulo4Pagado') }}"
                                                    onclick="mostrarCargando()">
                                                    Pagado
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>


                                <li class="dropend">
                                    @can('egresos.capitulo5000')
                                        <a class="dropdown-item dropdown-toggle" href="" data-bs-toggle="dropdown">
                                            Capítulo 5000 Bienes
                                        </a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('egresos.capitulo5000.comprometido')
                                                <a class="dropdown-item" href="{{ route('capitulo5Comprometido') }}"
                                                    onclick="mostrarCargando()">
                                                    Comprometido
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('egresos.capitulo5000.devengado')
                                                <a class="dropdown-item" href="{{ route('capitulo5Devengado') }}"
                                                    onclick="mostrarCargando()">
                                                    Devengado
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('egresos.capitulo5000.ejercido')
                                                <a class="dropdown-item" href="{{ route('capitulo5Ejercido') }}"
                                                    onclick="mostrarCargando()">
                                                    Ejercido
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('egresos.capitulo5000.pagado')
                                                <a class="dropdown-item" href="{{ route('capitulo5Pagado') }}"
                                                    onclick="mostrarCargando()">
                                                    Pagado
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>

                            </ul>


                        </li>

                        <li class="nav-item dropdown">
                            @can('prestamos')
                                @if ($haySaldosIniciales)
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                        aria-expanded="false" v-pre>
                                        {{ __('Préstamos') }}
                                    </a>
                                @else
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle disabled" href="#"
                                        role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                        aria-haspopup="true" aria-expanded="false" v-pre disabled>
                                        {{ __('Préstamos') }}
                                    </a>
                                @endif
                            @endcan

                            <ul class="dropdown-menu">
                                <li class="dropend">
                                    @can('prestamos.otorgamiento_compromiso-devengado')
                                        <a class="dropdown-item dropdown-toggle" href=""
                                            data-bs-toggle="dropdown">Otorgamiento
                                            (Compromiso-Devengado)</a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('prestamos.otorgamiento_compromiso-devengado.prestamos_iniciales')
                                                <a class="dropdown-item"
                                                    href="{{ route('capitulo7CompromisoDevengadoPrestamosIniciales') }}"
                                                    method="GET" onclick="mostrarCargando()">
                                                    Préstamos Iniciales
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('prestamos.otorgamiento_compromiso-devengado.prestamos_con_renovacion')
                                                <a class="dropdown-item"
                                                    href="{{ route('capitulo7CompromisoDevengadoPrestamosRenovacion') }}"
                                                    method="GET" onclick="mostrarCargando()">
                                                    Préstamos con Renovación
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>

                                <li class="dropend">
                                    @can('prestamos.otorgamiento_ejercido-pagado-recaudado')
                                        <a class="dropdown-item dropdown-toggle" href=""
                                            data-bs-toggle="dropdown">Otorgamiento
                                            (Ejercido-Pagado-Recaudado)</a>
                                    @endcan
                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('prestamos.otorgamiento_ejercido-pagado-recaudado.prestamos_iniciales')
                                                <a class="dropdown-item"
                                                    href="{{ route('capitulo7EjercidoPagadoRecaudadoPrestamosIniciales') }}"
                                                    method="GET" onclick="mostrarCargando()">
                                                    Préstamos Iniciales
                                                </a>
                                            @endcan
                                        </li>
                                        <li>
                                            @can('prestamos.otorgamiento_ejercido-pagado-recaudado.prestamos_con_renovacion')
                                                <a class="dropdown-item"
                                                    href="{{ route('capitulo7EjercidoPagadoRecaudadoPrestamosRenovacion') }}"
                                                    method="GET" onclick="mostrarCargando()">
                                                    Préstamos con Renovación
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>

                                <li class="dropend">
                                    @can('prestamos.recuperacion_recaudado')
                                        <a class="dropdown-item dropdown-toggle" href="" data-bs-toggle="dropdown">
                                            Recuperación (Recaudado)
                                        </a>
                                    @endcan

                                    <ul class="dropdown-menu">
                                        <li>
                                            @can('prestamos.recuperacion_recaudado.prestamos_iniciales')
                                                <a class="dropdown-item"
                                                    href="{{ route('capitulo7RecaudadoPrestamosIniciales') }}"
                                                    method="GET" onclick="mostrarCargando()">
                                                    Préstamos Iniciales
                                                </a>
                                            @endcan
                                        </li>

                                        <li>
                                            @can('prestamos.recuperacion_recaudado.prestamos_con_renovacion')
                                                <a class="dropdown-item"
                                                    href="{{ route('capitulo7RecaudadoPrestamosRenovacion') }}"
                                                    method="GET" onclick="mostrarCargando()">
                                                    Préstamos con Renovación
                                                </a>
                                            @endcan
                                        </li>
                                    </ul>
                                </li>


                                <li>
                                    @can('prestamos.cancelacion_prestamos')
                                        <a class="dropdown-item"
                                            href="{{ route('capitulo7RecuperacionEjerciciosAnteriores') }}" method="GET"
                                            onclick="mostrarCargando()">
                                            Cancelación de préstamos
                                        </a>
                                    @endcan
                                </li>


                            </ul>

                        </li>

                        <li class="nav-item dropdown">
                            @can('deudores')
                                @if ($haySaldosIniciales)
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                        aria-expanded="false" v-pre>
                                        {{ __('Deudores') }}
                                    </a>
                                @else
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle disabled" href="#"
                                        role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                        aria-haspopup="true" aria-expanded="false" v-pre disabled>
                                        {{ __('Deudores') }}
                                    </a>
                                @endif
                            @endcan

                            <ul class="dropdown-menu">
                                <li>
                                    @can('deudores.otorgamiento_de_anticipo-viaticos-fondo_fijo')
                                        <a class="dropdown-item" href="{{ route('otorgamientoAnticipo') }}" method="GET"
                                            onclick="mostrarCargando()">
                                            Otorgamiento de anticipo/Viáticos/Fondo Fijo
                                        </a>
                                    @endcan
                                </li>

                                <li>
                                    @can('deudores.reintegro_de_anticipo-viaticos-fondo_fijo')
                                        <a class="dropdown-item" href="{{ route('reintegroAnticipo') }}" method="GET"
                                            onclick="mostrarCargando()">
                                            Reintegro de anticipo/Viáticos/Fondo Fijo
                                        </a>
                                    @endcan
                                </li>

                                <li>
                                    @can('deudores.comprobacion_de_anticipo-viaticos-cancelacion_de_fondo_fijo')
                                        <a class="dropdown-item" href="{{ route('comprobacionAnticipo') }}" method="GET"
                                            onclick="mostrarCargando()">
                                            Comprobación de anticipo/Viáticos/Cancelación de Fondo Fijo
                                        </a>
                                    @endcan
                                </li>

                                <li>
                                    @can('deudores.pago_de_retenciones')
                                        <a class="dropdown-item" href="{{ route('comprobacionAnticipo') }}" method="GET"
                                            onclick="mostrarCargando()">
                                            Pago de retenciones
                                        </a>
                                    @endcan
                                </li>
                            </ul>

                        </li>

                        <style>
                            .dropdown-item.active,
                            .dropdown-item.show {
                                background-color: #007bff;
                                /* Cambia esto al color que desees */
                                color: #fff;
                                /* Cambia esto al color que desees */
                            }
                        </style>
                        <li class="nav-item dropdown">
                            @can('consultar_movimientos')
                                @if ($haySaldosIniciales)
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" role="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                        aria-expanded="false" v-pre>
                                        {{ __('Consultar movimientos') }}
                                    </a>
                                @else
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle disabled" role="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                        aria-expanded="false" v-pre disabled>
                                        {{ __('Consultar movimientos') }}
                                    </a>
                                @endif
                            @endcan
                            <ul class="dropdown-menu">
                                <li class="dropend">
                                    @can('consultar_movimientos.egresos')
                                        <a href="{{ route('movimientosEgresos') }}" method="GET" class="dropdown-item"
                                            onclick="mostrarCargando()">
                                            Egresos
                                        </a>
                                    @endcan
                                </li>

                                <li class="dropend">
                                    @can('consultar_movimientos.ingresos')
                                        <a href="{{ route('movimientosIngresos') }}" method="GET" class="dropdown-item"
                                            onclick="mostrarCargando()">
                                            Ingresos
                                        </a>
                                    @endcan
                                </li>

                                <li class="dropend">
                                    @can('consultar_movimientos.prestamos')
                                        <a href="{{ route('movimientosPrestamos') }}" method="GET" class="dropdown-item"
                                            onclick="mostrarCargando()">
                                            Préstamos
                                        </a>
                                    @endcan
                                </li>

                                <li class="dropend">
                                    @can('consultar_movimientos.concluidos')
                                        <a href="{{ route('capitulo1Cancelaciones') }}" method="GET"
                                            class="dropdown-item" onclick="mostrarCargando()">
                                            Concluidos
                                        </a>
                                    @endcan
                                </li>
                            </ul>

                        </li>


                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ Auth::user()->usuario }}

                            </a>

                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">

                                @admin
                                    <a class="dropdown-item" href="{{ route('listaDeUsuarios') }}">
                                        {{ __('Administración de usuarios') }}
                                    </a>
                                    <a class="dropdown-item" href="{{ route('adminPermisos') }}">
                                        {{ __('Administración de permisos') }}
                                    </a>
                                    <a class="dropdown-item" onclick="mostrarCargando()" href="/bitacoras">
                                        {{ __('Bitácora') }}
                                    </a>
                                @endadmin
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="
                                        localStorage.removeItem('añoSelect');
                                        event.preventDefault();
                                        document.getElementById('logout-form').submit();
                                    ">
                                    {{ __('Cerrar sesión') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    @endguest

                    </ul>
                </div>
            </div>

        </nav>

        <main class="py-4">
            <x-mensaje />
            @yield('content')
        </main>
    </div>
    <x-loading />
    <!-- Modal Selección de Año -->
    <div class="modal fade" id="modalSeleccionAnio" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Seleccione el año de trabajo</h5>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="selectAnio">Año</label>
                        <select id="selectAnio" class="form-select">
                            <option value="" selected disabled>Seleccione un año</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button id="btnAceptarAnio" class="btn btn_primario">
                        Aceptar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <footer class="footer mt-auto py-4 bg-white" style="border-top: 1px solid rgba(192, 192, 192, 0.4);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4 text-center text-md-start">
                    <img src="{{ asset('imagenes/ipe_logo_1.png') }}" alt="Logo Adicional"
                        style="height: 70px; width: auto;">
                </div>

                <div class="col-md-4 text-center">
                    <span style="color: #7A1737;">
                        © {{ date('Y') }} Sistema de Armonización Contable
                    </span>
                    <p class="small text-muted mb-0">Instituto de Pensiones del Estado de Veracruz</p>
                </div>

                <div class="col-md-4 text-center text-md-end">
                    <img src="{{ asset('imagenes/logos_gobierno.png') }}" alt="Logo Adicional"
                        style="height: 70px; width: auto;">
                </div>
            </div>
        </div>
    </footer>

</body>

<script>
    function mostrarCargando() {
        $('#loadingScreen').prop('hidden', false);
        toastr.info("Cargando, espere un momento por favor . . .", "", {
            timeOut: 0
        });
    }

    function esconderCargando() {
        $('#loadingScreen').prop('hidden', true);
    }

    function mostrarMensajeAperturaSistema() {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 4000,
        };

        toastr.error(
            'Para iniciar el registro de movimientos primero cargue el presupuesto y los saldos iniciales del ejercicio actual',
            'ATENCIÓN'
        );
    }
</script>

@auth
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const modalElement = document.getElementById('modalSeleccionAnio');
            if (!modalElement) return;

            const modalAnio = new bootstrap.Modal(modalElement, {
                backdrop: 'static',
                keyboard: false
            });


            if (!localStorage.getItem('añoSelect')) {
                modalAnio.show();
            }

            document.getElementById('btnAceptarAnio').addEventListener('click', function() {
                const anio = document.getElementById('selectAnio').value;

                if (!anio) {
                    toastr.warning('Debe seleccionar un año');
                    return;
                }


                localStorage.setItem('añoSelect', anio);
                window.añoSelect = anio;
                fetch('/seleccionar-anio', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            anio
                        })
                    })
                    .then(() => {
                        modalAnio.hide();

                        location.reload();
                    });
            });

        });
    </script>
@endauth

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const anioGuardado = localStorage.getItem('añoSelect');
        if (!anioGuardado) return;

        window.añoSelect = anioGuardado;

        const texto = document.getElementById('ejercicioActual');
        const anioSpan = document.getElementById('anioNavbar');

        if (texto && anioSpan) {
            anioSpan.textContent = anioGuardado;
            texto.style.display = 'inline';
        }

    });
</script>




</html>
