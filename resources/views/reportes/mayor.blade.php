@extends('layouts.app')
@section('title', 'Libro mayor')

@section('content')
    <script src="{{ asset('js/Presupuesto/balanzaArmonizada.js') }}"></script>
     

    <div class="container mt-5">
        <div class="shadow rounded p-5">
            <h2 class="mb-4">Libro mayor</h2>
            <livewire:libro-mayor />
        </div>
    </div>
@endsection
