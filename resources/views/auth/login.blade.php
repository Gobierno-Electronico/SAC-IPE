@extends('layouts.app')
@section('titulo', 'Inicio de sesión')
@section('content')

    <div class="container w-50">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow border mt-5 border-0">
                    <div class="card-header fs-4 bg-white border border-0 text-center pt-4">{{ __('Inicio de sesión') }}
                    </div>
                    <div class="card-body bg-white p-4 ">

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="row mb-3">
                                <label for="usuario"
                                    class="col-md-4 col-form-label text-md-end ">{{ __('Usuario') }}</label>

                                <div class="col-md-6">
                                    <input id="usuario" type="usuario"
                                        class="form-control @error('usuario') is-invalid @enderror" name="usuario"
                                        value="{{ old('usuario') }}" required autofocus>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="password"
                                    class="col-md-4 col-form-label text-md-end">{{ __('Contraseña') }}</label>

                                <div class="col-md-6">
                                    <input id="password" type="password"
                                        class="form-control @error('password') is-invalid @enderror" name="password"
                                        required autocomplete="current-password">
                                    @error('password')
                                        <script>
                                             toastr.error('Usuario o contraseña incorrectos', '', {
                                            timeOut: 5000
                                        });
                                        </script>
                                    @enderror
                                </div>
                            </div>



                            <div class="row mb-0 mt-4">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Iniciar sesión') }}
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
