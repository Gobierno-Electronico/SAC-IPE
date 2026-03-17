
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border mt-5 border-0">
                <div class="card-body bg-white p-5">
                    <div class="d-flex flex-row mb-3">
                        {{-- <a href="{{ route('listaDeUsuarios')  }}" class="d-inline-block mt-1">
                            <i class="fa-solid fa-circle-left" style="color: #198754; font-size: 1.5rem;"></i>
                        </a> --}}
                        <h2 class="mx-4">Cambiar contraseña</h2>
                    </div>
                    <form method="POST" action="{{ route('cambiarPassword') }}">
                        @csrf

                        <div class="row mb-3">
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
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirmar contraseña') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="d-flex col-md-6 offset-md-4">
                                <button type="submit" class="btn btn_primario ms-auto">
                                    {{ __('Cambiar contraseña') }}
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
