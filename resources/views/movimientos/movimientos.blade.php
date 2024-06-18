@extends('layouts.app')
@section('title', 'Movimientos')

@section('content')
    {{-- <script src="{{ asset('js/Presupuesto/consultaPresupuesto.js') }}"></script> --}}
     
    <div class="container mt-5">
        <div class="card shadow border-0 p-5">
            <livewire:movimientos-form/>
        </div>
    </div>
@endsection
