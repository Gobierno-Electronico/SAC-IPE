@extends('layouts.app')
@section('title', 'Consulta de póliza inicial')

@section('content')
    <script src="{{ asset('js/Contabilidad/consultaPolizaInicial.js') }}"></script>

    <div class="container" style="max-width: 80%;">
        <h2 class="mb-4">Consulta de póliza inicial</h2>
        <livewire:poliza-inicial-table/>
    </div>
@endsection
