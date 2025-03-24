@extends('layouts.app')
@section('title', 'Consulta de Presupuesto')

@section('content')
    <script src="{{ asset('js/Presupuesto/consultaPresupuesto.js') }}"></script>
     

    <div class="container">
        <h2 class="mb-4">Consulta de presupuestos de ingresos</h2>
        <livewire:presupuesto-ingresos-table />
    </div>
@endsection
