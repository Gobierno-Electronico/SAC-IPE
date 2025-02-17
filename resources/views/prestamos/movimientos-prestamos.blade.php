@extends('layouts.app')
@section('title', 'Movimientos de préstamos')

@section('content')
    <script src="{{ asset('js/Prestamos/prestamos.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Consulta de movimientos de préstamos</h2>
                    <livewire:prestamos.movimientos-prestamos-table/>
                </div>
            </div>
        </div>
    </div>
@endsection
