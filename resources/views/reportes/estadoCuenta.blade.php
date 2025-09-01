@extends('layouts.app')
@section('title', 'Estado de cuenta')

@section('content')
    <script src="{{ asset('js/Reportes/reportes.js') }}"></script>
     

    <div class="container mt-5">
        <div class="shadow rounded p-5">
            <h2 class="mb-4">Estado de cuenta</h2>
            <livewire:estado-cuenta />
        </div>
    </div>
@endsection
