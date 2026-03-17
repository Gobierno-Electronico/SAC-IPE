@extends('layouts.app')
@section('titulo', 'Editar cuenta')
@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('js/actualizarCuenta.js') }}"></script>
    <div class="container mt-5">
        <div class="card shadow border-0 p-5">
            <div class="card-body">
                <p class="fs-2 mt-3"> <a href="{{ url('/cuentas') }}" class="d-inline-block mb-3"><svg
                            xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                            <style>
                                svg {
                                    fill: #198754
                                }
                            </style>
                            <path
                                d="M512 256A256 256 0 1 0 0 256a256 256 0 1 0 512 0zM217.4 376.9L117.5 269.8c-3.5-3.8-5.5-8.7-5.5-13.8s2-10.1 5.5-13.8l99.9-107.1c4.2-4.5 10.1-7.1 16.3-7.1c12.3 0 22.3 10 22.3 22.3l0 57.7 96 0c17.7 0 32 14.3 32 32l0 32c0 17.7-14.3 32-32 32l-96 0 0 57.7c0 12.3-10 22.3-22.3 22.3c-6.2 0-12.1-2.6-16.3-7.1z" />
                        </svg> </a>
                    </a> Editar cuenta</p>
                    <form method="POST" action="{{ route('cambiosCuenta') }}" id="formCuenta">
                    @csrf
                    <input type="hidden" name="id" value="{{ $cuenta->id }}">
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="Cuenta_codigo">Código de cuenta</label>
                            <input type="text" name="Cuenta_codigo"
                                value="{{ old('Cuenta_codigo', $cuenta->Codigo_cuenta) }}" class="form-control" required
                                disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="Descripcion_cuenta">Descripción de cuenta</label>
                            <textarea class="form-control" name="Descripcion_cuenta" required rows="1">{{ old('Descripcion_cuenta', $cuenta->Descripcion_cuenta) }}</textarea>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="nivel" class="form-label">Nivel de cuenta</label>
                            <select class="form-select" name="nivel" id="nivel" disabled>
                                @for ($i = 1; $i < 10; $i++)
                                    <option value="{{ $i }}" {{ $i == $cuenta->Nivel ? 'selected' : '' }}>Nivel
                                        {{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="Cuenta_codigo">Cuenta de registro</label>
                            <input type="text" name="Cuenta_codigo"
                                value="{{ old('Cuenta_codigo', $cuenta->Cuenta_registro) == 1 ? 'Sí' : 'No' }}"
                                class="form-control" required disabled>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-end">
                        <button type="button" id="btnActualizar" class="btn btn_primario shadow border-0"
                            onclick="actualizarCuenta(this)">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
