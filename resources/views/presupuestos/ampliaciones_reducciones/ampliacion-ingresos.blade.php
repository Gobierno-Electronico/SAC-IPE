@extends('layouts.app')
@section('title', 'Ampliación presupuestal ingresos')

@section('content')
<script src="{{ asset('js/Presupuesto/ampliaciones.js') }}"></script>
    {{-- <script src="{{ asset('js/Presupuesto/consultaPresupuesto.js') }}"></script> --}}
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card shadow border mt-5 border-0">
                    <div class="card-body bg-white p-5">
                           
                        <livewire:afectaciones-ingresos-form :$tipo estado='INGRESOS' estadoOriginal='INGRESOS'/>

                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
