@extends('layouts.app')
@section('title', 'Reintegro de anticipo')

@section('content')
    <script src="{{ asset('js/Ingresos/ingresos.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Reintegro de anticipos / Viáticos / Cancelación de Fondos Fijos</h2>
                    <livewire:deudores-reintegro-anticipo-form />
                </div>
            </div>
        </div>
    </div>
@endsection