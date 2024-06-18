@extends('layouts.app')
@section('title', 'Reducción presupuestal egresos')

@section('content')
<script src="{{ asset('js/Presupuesto/ampliaciones.js') }}"></script>

    {{-- <script src="{{ asset('js/Presupuesto/consultaPresupuesto.js') }}"></script> --}}
     

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card shadow border mt-5 border-0">
                    <div class="card-body bg-white p-5">
                            {{-- <a href="{{ route('listaDeUsuarios')  }}" class="d-inline-block mt-1">
                                <i class="fa-solid fa-circle-left" style="color: #198754; font-size: 1.5rem;"></i>
                            </a> --}}
                        <livewire:afectaciones-ingresos-form :$tipo estado='EGRESOS' estadoOriginal='EGRESOS'/>

                    </div>
                </div>
            </div>
        </div>
    </div>


    </div>
@endsection
