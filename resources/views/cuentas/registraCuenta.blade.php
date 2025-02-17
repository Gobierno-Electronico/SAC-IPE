@extends('layouts.app')
@section('titulo', 'Agregar cuenta')

@section('content')
    <script src="{{ asset('js/registraCuenta.js') }}"></script>
    <div class="container mt-5">
        <div class="card shadow border-0 p-5">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <h2 class="mt-3 mb-3">
                        <a href="{{url ('/cuentas')}}" class="d-inline-block mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" height="1em"
                                viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. -->
                                <style>
                                    svg {
                                        fill: #198754
                                    }
                                </style>
                                <path
                                    d="M512 256A256 256 0 1 0 0 256a256 256 0 1 0 512 0zM217.4 376.9L117.5 269.8c-3.5-3.8-5.5-8.7-5.5-13.8s2-10.1 5.5-13.8l99.9-107.1c4.2-4.5 10.1-7.1 16.3-7.1c12.3 0 22.3 10 22.3 22.3l0 57.7 96 0c17.7 0 32 14.3 32 32l0 32c0 17.7-14.3 32-32 32l-96 0 0 57.7c0 12.3-10 22.3-22.3 22.3c-6.2 0-12.1-2.6-16.3-7.1z" />
                            </svg>
                        </a>
                        Agregar cuenta
                    </h2>
                </div>
                <div class="col text-end">
                    <div class="col">
                        <svg xmlns="http://www.w3.org/2000/svg" height="2em" data-toggle="tooltip"
                        data-bs-placement="left" title="Los campos marcados con * son requeridos."
                            viewBox="0 0 512 512">
                            <style>
                                svg {
                                    fill: #198754
                                }
                            </style>
                            <path
                                d="M464 256A208 208 0 1 0 48 256a208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm169.8-90.7c7.9-22.3 29.1-37.3 52.8-37.3h58.3c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24V250.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1H222.6c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            <form>
                <div class="row g-3">
                    <div class="col-md-3 mb-3">
                        <label for="nivel" class="form-label">Nivel de cuenta *
                            <svg xmlns="http://www.w3.org/2000/svg" height="1em" data-toggle="tooltip"
                                data-bs-placement="top" title="Selecciona el nivel que tendrá la nueva cuenta."
                                viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. -->
                                <style>
                                    svg {
                                        fill: #198754
                                    }
                                </style>
                                <path
                                    d="M464 256A208 208 0 1 0 48 256a208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm169.8-90.7c7.9-22.3 29.1-37.3 52.8-37.3h58.3c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24V250.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1H222.6c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z" />
                            </svg>
                        </label>
                        <select class="form-select" name="nivel" id="nivel" onchange="seleccionarNivel()">
                            <option value="0" selected disabled>Seleccionar nivel...</option>
                            @for ($i = 1; $i < 10; $i++)
                                <option value="{{ $i }}">Nivel {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="txtCodigo" class="form-label">Código *
                            <svg xmlns="http://www.w3.org/2000/svg" height="1em" data-toggle="tooltip"
                                data-bs-placement="top" title="Es el código que se le asigna a la nueva cuenta."
                                viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. -->
                                <style>
                                    svg {
                                        fill: #198754
                                    }
                                </style>
                                <path
                                    d="M464 256A208 208 0 1 0 48 256a208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm169.8-90.7c7.9-22.3 29.1-37.3 52.8-37.3h58.3c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24V250.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1H222.6c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z" />
                            </svg>
                        </label>
                        <input class="form-control" type="number" name="txtCodigo" id="txtCodigo"
                            oninput="cocatenarPreview(event)">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="txtPreview" class="form-label">Vista previa del código de cuenta *
                            <svg xmlns="http://www.w3.org/2000/svg" height="1em" data-toggle="tooltip"
                                data-bs-placement="top" title="Vista previa de como quedará el código para la nueva cuenta"
                                viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. -->
                                <style>
                                    svg {
                                        fill: #198754
                                    }
                                </style>
                                <path
                                    d="M464 256A208 208 0 1 0 48 256a208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm169.8-90.7c7.9-22.3 29.1-37.3 52.8-37.3h58.3c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24V250.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1H222.6c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z" />
                            </svg>
                        </label>
                        <input class="form-control" type="text" name="txtPreview" id="txtPreview" readonly>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="">¿Es cuenta de registro? *</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="btnCuentaRegistro" id="flexRadioDefault1"
                                value="True">
                            <label class="form-check-label" for="flexRadioDefault1">
                                Sí
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="btnCuentaRegistro" id="flexRadioDefault2"
                                value="False">
                            <label class="form-check-label" for="flexRadioDefault2">
                                No
                            </label>
                        </div>
                    </div>
                </div>

                @if (isset($cuentasNivel1))
                    <div id="selectNivel1" class="col-md-4 mt-2 d-none">
                        <label for="1" class="form-label">Nivel 1 *</label>
                        <select class="form-select" name="1" id="1" onchange="llenarSiguienteNivel(this)">
                            <option value="0" selected disabled>Seleccionar cuenta...</option>
                            @foreach ($cuentasNivel1 as $cuenta)
                                <option value="{{ $cuenta->Codigo_cuenta }}">{{ $cuenta->Codigo_cuenta }} -
                                    {{ $cuenta->Descripcion_cuenta }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div id="selectNivel1" class="col-md-4 mt-2 d-none">
                        <label for="1" class="form-label">Nivel 1 *</label>
                        <select class="form-select" name="1" id="1" onchange="llenarSiguienteNivel(this)">
                            <option value="0" selected disabled>Seleccionar cuenta...</option>
                        </select>
                    </div>
                @endif

                <div id="contenedorSelects" class="mt-2">
                    <div class="col-4 mt-2 d-none" id="div2">
                        <label for="2" class="form-label">Nivel 2 *</label>
                        <select name="2" id="2" class="form-select" onchange="llenarSiguienteNivel(this)">
                        </select>
                    </div>
                    <div class="col-4 mt-2 d-none" id="div3">
                        <label for="3" class="form-label">Nivel 3 *</label>
                        <select name="3" id="3" class="form-select" onchange="llenarSiguienteNivel(this)">
                        </select>
                    </div>
                    <div class="col-4 mt-2 d-none" id="div4">
                        <label for="4" class="form-label">Nivel 4 *</label>
                        <select name="4" id="4" class="form-select" onchange="llenarSiguienteNivel(this)">
                        </select>
                    </div>
                    <div class="col-4 mt-2 d-none" id="div5">
                        <label for="5" class="form-label">Nivel 5 *</label>
                        <select name="5" id="5" class="form-select" onchange="llenarSiguienteNivel(this)">
                        </select>
                    </div>
                    <div class="col-4 mt-2 d-none" id="div6">
                        <label for="6" class="form-label">Nivel 6 *</label>
                        <select name="6" id="6" class="form-select" onchange="llenarSiguienteNivel(this)">
                        </select>
                    </div>
                    <div class="col-4 mt-2 d-none" id="div7">
                        <label for="7" class="form-label">Nivel 7 *</label>
                        <select name="7" id="7" class="form-select" onchange="llenarSiguienteNivel(this)">
                        </select>
                    </div>
                    <div class="col-4 mt-2 d-none" id="div8">
                        <label for="8" class="form-label">Nivel 8 *</label>
                        <select name="8" id="8" class="form-select" onchange="llenarSiguienteNivel(this)">
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-4">
                    <div class="col-md-6 mb-3">
                        <label for="txtDescripcion" class="form-label">Descripción de la cuenta *</label>
                        <textarea class="form-control" name="txtDescripcion" id="txtDescripcion" cols="10" rows="5"></textarea>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col">
                        <a href="{{url ('/cuentas/mostrarRegistrarCuenta')}}" class="btn btn-outline-success shadow border-1">Limpiar campos</a>
                    </div>
                    <div class="col text-end">
                        <button id="btnAgregar" type="button" class="btn btn-success shadow border-0" onclick="agregarCuenta(this)">Agregar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
