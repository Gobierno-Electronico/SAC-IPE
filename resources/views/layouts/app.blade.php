<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <!-- JQuery and Toastr -->
    {{-- <script src="//unpkg.com/alpinejs" defer></script> --}}
    <script src="https://code.jquery.com/jquery-3.6.3.min.js"
        integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <!-- Scripts -->
    <script src="https://kit.fontawesome.com/5a7f009297.js" crossorigin="anonymous"></script>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @vite(['resources/css/layouts/app.css', 'resources/css/layouts/loading.css', 'resources/css/layouts/loadingDots.css'])

</head>

<body>
    <div id="app">

        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <img class="img-fluid logo_encabezado" src="{{ asset('imagenes/ipe_logo.png') }}" alt="veracruz logo">
                <a class="navbar-brand" href="{{ url('/') }}">
                    Sistema de Armonización Contable
                </a>

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
                            @tecnico
                                <li class="nav-item dropdown">
                                    <a id="navbarCuentas" class="nav-link" href="/bitacoras" role="button">
                                        {{ __('Bitácora') }}</a>
                                </li>
                            @endtecnico
                            <li class="nav-item dropdown">
                                <a id="navbarCuentas" class="nav-link" href="/cuentas" role="button">
                                    {{ __('Cuentas') }}</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                    aria-expanded="false" v-pre>
                                    {{ __('Presupuesto') }}
                                </a>

                                <ul class="dropdown-menu">
                                    <li class="dropend">
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Cargar Presupuesto</a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" id=""
                                                    href="{{ route('presupuestoInicialIngresos') }}" method="GET">
                                                    {{ __('Ingresos') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" id=""
                                                    href="{{ route('presupuestoInicialEgresos') }}" method="GET">
                                                    {{ __('Egresos') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="dropend">
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Consultar Presupuesto</a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" id=""
                                                    href="{{ route('consultaPresupuestoIngresos') }}" method="GET">
                                                    {{ __('Ingresos') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" id=""
                                                    href="{{ route('consultaPresupuestoEgresos') }}" method="GET">
                                                    {{ __('Egresos') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="dropend">
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Consultas</a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" id=""
                                                    href="{{ route('tiposPresupuesto') }}" method="GET">
                                                    {{ __('Tipos de presupuesto') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" id="" wire:navigate.hover
                                                    href="{{ route('consultaAmpliacionesReducciones') }}" method="GET">
                                                    {{ __('Ampliaciones/Reducciones') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="dropend">
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Afectaciones ingresos</a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" id=""
                                                    href="{{ route('ampliacionIngresos') }}" method="GET">
                                                    {{ __('Ampliación') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" id=""
                                                    href="{{ route('reduccionIngresos') }}" method="GET">
                                                    {{ __('Reducción') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="dropend">
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Afectaciones egresos</a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" id=""
                                                    href="{{ route('ampliacionEgresos') }}" method="GET">
                                                    {{ __('Ampliación') }}
                                                </a>

                                            </li>
                                            <li>
                                                <a class="dropdown-item" id=""
                                                    href="{{ route('reduccionEgresos') }}" method="GET">
                                                    {{ __('Reducción') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" id="" href="{{ route('recalendarizacion') }}"
                                            method="GET">
                                            {{ __('Reclasificación/Recalendarización') }}
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                    aria-expanded="false" v-pre>
                                    {{ __('Contabilidad') }}
                                </a>

                                <ul class="dropdown-menu">

                                    <li class="dropend">
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Reportes</a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('balanzaArmonizada') }}" method="GET">
                                                    {{ __('Balanza armonizada') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('libroMayor') }}" method="GET">
                                                    {{ __('Libro mayor') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('libroDiario') }}" method="GET">
                                                    {{ __('Libro diario') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li>

                                    <li class="dropend">
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Consultar</a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('consultaPolizaInicial') }}" method="GET">
                                                    {{ __('Póliza inicial') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li>

                                    <li class="dropend">
                                        <a href="#" class="dropdown-item dropdown-toggle"
                                            data-bs-toggle="dropdown">Carga</a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" id="" onclick="mostrarCargando()"
                                                    href="{{ route('polizaInicial') }}" method="GET">
                                                    {{ __('Póliza inicial') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li>

                                    <li class="dropend">
                                        <a class="dropdown-item" id="" href="{{ route('movimientos') }}"
                                            method="GET">
                                            {{ __('Movimientos') }}
                                        </a>
                                    </li>

                                </ul>


                            </li>

                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" role="button"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                    aria-expanded="false" v-pre>
                                    {{ __('Ingresos') }}
                                </a>

                                <ul class="dropdown-menu">

                                    <li class="dropend">
                                        <a href="{{ route('ingresosDevengado') }}" method="GET" class="dropdown-item "
                                            onclick="mostrarCargando()">Devengado</a>
                                    </li>

                                    <li class="dropend">
                                        <a href="{{ route('ingresosRecaudado') }}" method="GET" class="dropdown-item"
                                            onclick="mostrarCargando()">Recaudado</a>
                                    </li>

                                    {{-- Las funcionalidades comentadas a continuación estaban en desarrollo pero realizando un análisis junto con el equipo de contabilidad se llegó a la conclusión de que no formarían parte del alcance de la primera versión del sistema, aunque en versiones siguientes si serán desarrolladas, por lo cual, no se deben eliminar del sistema, simplemente inhabilitarlas un tiempo --}}
                                    {{-- <li class="dropend">
                                        <a class="dropdown-item dropdown-toggle" href=""
                                            data-bs-toggle="dropdown">Devolución</a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{route('autorizacionDevolucion')}}" method="GET" onclick="mostrarCargando()">
                                                    Autorización
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{route('pagoDevolucion')}}" method="GET" onclick="mostrarCargando()">
                                                    Pago
                                                </a>
                                            </li>
                                        </ul>
                                    </li>

                                    <li class="dropend">
                                        <a class="dropdown-item dropdown-toggle" href=""
                                            data-bs-toggle="dropdown">Reintegro</a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{route('autorizacionReintegro')}}" method="GET" onclick="mostrarCargando()">
                                                    Autorización
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{route('pagoReintegro')}}" method="GET" onclick="mostrarCargando()">
                                                    Pago
                                                </a>
                                            </li>
                                        </ul>
                                    </li> --}}

                                    <li class="dropend">


                                        <a class="dropdown-item" href="{{ route('cobroEspecie') }}" method="GET"
                                            onclick="mostrarCargando()">
                                            Cobro en especie
                                        </a>

                                        {{-- <li>
                                                <a class="dropdown-item" href="{{route('devolucionEspecie')}}" method="GET" onclick="mostrarCargando()">
                                                    Devolución
                                                </a>
                                            </li> --}}

                                    </li>

                                    <li class="dropend">
                                        <a href="{{ route('ingresosPorClasificar') }}" method="GET"
                                            class="dropdown-item" onclick="mostrarCargando()">Ingresos por clasificar</a>
                                    </li>

                                    <li class="dropend">
                                        <a href="{{ route('depositosBancos') }}" method="GET" class="dropdown-item"
                                            onclick="mostrarCargando()">Depósitos en bancos</a>
                                    </li>

                                    <li class="dropend">
                                        <a href="{{ route('devengadoRecaudado') }}" method="GET" class="dropdown-item"
                                            onclick="mostrarCargando()">Devengado prev. recaudado</a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true"
                                    aria-expanded="false" v-pre>
                                    {{ __('Egresos') }}
                                </a>

                                <ul class="dropdown-menu">
                                    <li class="dropend">
                                        <a class="dropdown-item dropdown-toggle" href=""
                                            data-bs-toggle="dropdown">Capítulo 1000 Servicios personales</a>
                                        <ul class="dropdown-menu">
                
                                        </ul>
                                    </li>

                                    <li class="dropend">
                                        <a class="dropdown-item dropdown-toggle" href=""
                                            data-bs-toggle="dropdown">Capítulo 2000 y 3000 Materiales y servicios</a>
                                        <ul class="dropdown-menu">
                
                                        </ul>
                                    </li>

                                    <li class="dropend">
                                        <a class="dropdown-item dropdown-toggle" href=""
                                            data-bs-toggle="dropdown">Capítulo 4000 Transferencias y Pensiones</a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{route('capitulo4Comprometido')}}" method="GET" onclick="mostrarCargando()">
                                                    Comprometido
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{route('capitulo4Devengado')}}" method="GET" onclick="mostrarCargando()">
                                                    Devengado
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{route('capitulo4Ejercido')}}" method="GET" onclick="mostrarCargando()">
                                                    Ejercido
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{route('capitulo4Pagado')}}" method="GET" onclick="mostrarCargando()">
                                                    Pagado
                                                </a>
                                            </li>
                                        </ul>
                                    </li>

                                    <li class="dropend">
                                        <a class="dropdown-item dropdown-toggle" href=""
                                            data-bs-toggle="dropdown">Capítulo 5000 Bienes</a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{route('capitulo5Comprometido')}}" method="GET" onclick="mostrarCargando()">
                                                    Comprometido
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{route('capitulo4Devengado')}}" method="GET" onclick="mostrarCargando()">
                                                    Devengado
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{route('capitulo5Ejercido')}}" method="GET" onclick="mostrarCargando()">
                                                    Ejercido
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{route('capitulo4Pagado')}}" method="GET" onclick="mostrarCargando()">
                                                    Pagado
                                                </a>
                                            </li>
                                        </ul>
                                    </li>

                                    <li class="dropend">
                                        <a class="dropdown-item dropdown-toggle" href=""
                                            data-bs-toggle="dropdown">Capítulo 7000 Préstamos</a>
                                        <ul class="dropdown-menu">
                
                                        </ul>
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
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->nombre }}

                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">

                                    @admin
                                        <a class="dropdown-item" href="{{ route('listaDeUsuarios') }}">
                                            {{ __('Administración de usuarios') }}
                                        </a>
                                    @endadmin
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                        onclick="event.preventDefault();
                                                 document.getElementById('logout-form').submit();">
                                        {{ __('Cerrar sesión') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                        class="d-none">
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

</body>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<script>
    function mostrarCargando() {
        $('#loadingScreen').prop('hidden', false);
        let mensajeEdoSolicitud = toastr.info("Cargando, espere un momento por favor . . .", "", {
            timeOut: "0"
        });

    }

    function esconderCargando() {
        $('#loadingScreen').prop('hidden', true);
    }
</script>

</html>
