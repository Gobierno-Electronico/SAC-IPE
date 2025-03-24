@extends('layouts.app')
@section('title', 'Otorgamiento (Compromiso-Devengado)')

@section('content')
    <script src="{{ asset('js/Prestamos/prestamos.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Otorgamiento préstamos iniciales (Compromiso-Devengado)</h2>
                    <h4>Ingreso devengado y Egreso comprometido - devengado</h4>
                    <livewire:prestamos-otorgamiento-compromiso-devengado-prestamosIniciales-form/>
                </div>
            </div>
        </div>
    </div>
@endsection
