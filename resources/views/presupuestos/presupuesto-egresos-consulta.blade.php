@extends('layouts.app')
@section('title', 'Consulta de Presupuesto')

@section('content')
    <script src="{{ asset('js/Presupuesto/consultaPresupuesto.js') }}"></script>
     

    <div class="container" style="max-width: 80%;">
        <h2 class="mb-4">Consulta de presupuestos de egresos</h2>
        <livewire:presupuesto-egresos-table/>
    </div>
@endsection
