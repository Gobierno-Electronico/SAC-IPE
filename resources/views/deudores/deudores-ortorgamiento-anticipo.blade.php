@extends('layouts.app')
@section('title', 'Otorgamiento de anticipo')

@section('content')
    <script src="{{ asset('js/Ingresos/ingresos.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Otorgamiento de anticipo / Viáticos / Fondo Fijo</h2>
                    <livewire:deudores-otorgamiento-anticipo-form />
                </div>
            </div>
        </div>
    </div>
@endsection