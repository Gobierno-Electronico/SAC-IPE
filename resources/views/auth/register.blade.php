@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border mt-5 border-0">
                <div class="card-body bg-white p-5">
                    <div class="d-flex flex-row mb-3">
                        <a href="{{ route('listaDeUsuarios')  }}" class="d-inline-block mt-1">
                            <i class="fa-solid fa-circle-left" style="color: #198754; font-size: 1.5rem;"></i>
                        </a>
                        <h2 class="mx-4">Registrar usuario</h2>
                    </div>
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="nombre" class="col-md-4 col-form-label text-md-end">{{ __('Nombre') }}</label>

                            <div class="col-md-6">
                                <input id="nombre" type="text" class="form-control @error('nombre') is-invalid @enderror" name="nombre" value="{{ old('nombre') }}" required autocomplete="nombre" autofocus>

                                @error('nombre')
                                    <script>
                                        toastr.error('Nombre invalido', '', {
                                            timeOut: 5000
                                        });
                                    </script>
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="apellido_paterno" class="col-md-4 col-form-label text-md-end">{{ __('Apellido paterno') }}</label>

                            <div class="col-md-6">
                                <input id="apellido_paterno" type="text" class="form-control @error('apellido_paterno') is-invalid @enderror" name="apellido_paterno" value="{{ old('apellido_paterno') }}" required autocomplete="apellido_paterno" autofocus>

                                @error('apellido_paterno')
                                    <script>
                                        toastr.error('Apellido Paterno invalido', '', {
                                            timeOut: 5000
                                        });
                                    </script>
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="apellido_materno" class="col-md-4 col-form-label text-md-end">{{ __('Apellido materno') }}</label>

                            <div class="col-md-6">
                                <input id="apellido_materno" type="text" class="form-control @error('apellido_materno') is-invalid @enderror" name="apellido_materno" value="{{ old('apellido_materno') }}" required autocomplete="apellido_materno" autofocus>

                                @error('apellido_materno')
                                    <script>
                                        toastr.error('Apellido Materno invalido', '', {
                                            timeOut: 5000
                                        });
                                    </script>
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="usuario" class="col-md-4 col-form-label text-md-end">{{ __('Nombre de usuario') }}</label>

                            <div class="col-md-6">
                                <input id="usuario" type="text" class="form-control @error('usuario') is-invalid @enderror" name="usuario" value="{{ old('usuario') }}" required autocomplete="usuario" autofocus>

                                @error('usuario')
                                    <script>
                                        toastr.error('Nombre de usuario invalido', '', {
                                            timeOut: 5000
                                        });
                                    </script>
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>


                        <div class="row mb-3">
                            <label for="rol" class="col-md-4 col-form-label text-md-end">{{ __('Cargo') }}</label>
                            <div class="col-md-6">
                                <select name="rol" id="rol" class="form-control @error('rol') is-invalid @enderror" name="rol" required>
                                    <option value="" disabled selected>{{ __('Selecciona un cargo') }}</option>
                                    @foreach (App\Enums\RolEnum::valores() as $key => $value)
                                        <option value="{{ $key }}" >{{ $key }}</option>
                                    @endforeach
                                </select>
                                @error('rol')
                                    <script>
                                        toastr.error('Cargo invalido', '', {
                                            timeOut: 5000
                                        });
                                    </script>
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="numero_de_personal" class="col-md-4 col-form-label text-md-end">{{ __('Número de personal') }}</label>

                            <div class="col-md-6">
                                <input id="numero_de_personal" type="number" min="1" onkeypress="return event.charCode >= 48 && event.charCode <= 57" class="form-control @error('numero_de_personal') is-invalid @enderror" name="numero_de_personal" value="{{ old('numero_de_personal') }}" required autocomplete="numero_de_personal">

                                @error('numero_de_personal')
                                    <script>
                                        toastr.error('Numero de personal invalido', '', {
                                            timeOut: 5000
                                        });
                                    </script>
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Contraseña') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                @error('password')
                                    <script>
                                        toastr.error('Contreseña invalida', '', {
                                            timeOut: 5000
                                        });
                                    </script>
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirmar Contraseña') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div> --}}

                        <div class="row mb-0">
                            <div class="d-flex col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-success ms-auto">
                                    {{ __('Registrar usuario') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
