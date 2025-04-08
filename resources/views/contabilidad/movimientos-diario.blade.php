@extends('layouts.app')
@section('title', 'Movimientos de poliza diario')

@section('content')
    <script src="{{ asset('js/Presupuesto/reclasificacion.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Consulta de movimientos de poliza diario</h2>
                    <livewire:movimientos-diario-table/>
                </div>
            </div>
        </div>
    </div>
@endsection
