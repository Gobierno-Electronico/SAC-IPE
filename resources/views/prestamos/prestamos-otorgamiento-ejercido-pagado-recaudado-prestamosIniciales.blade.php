@extends('layouts.app')
@section('title', 'Otorgamiento (Ejercido-Pagado-Recaudado)')

@section('content')
    <script src="{{ asset('js/Prestamos/prestamos.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Otorgamiento (Ejercido-Pagado-Recaudado)</h2>
                    <h4>Ingreso recaudado y Egreso ejercido - pagado</h4>
                    <livewire:prestamos.prestamos-otorgamiento-ejercido-pagado-recaudado-prestamosIniciales-form/>
                </div>
            </div>
        </div>
    </div>
@endsection
