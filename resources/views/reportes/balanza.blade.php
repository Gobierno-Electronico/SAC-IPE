@extends('layouts.app')
@section('title', 'Balanza Armonizada')

@section('content')
    <script src="{{ asset('js/Presupuesto/balanzaArmonizada.js') }}"></script>
     

    <div class="container mt-5">
        <div class="shadow rounded p-5 mx-auto w-75">
            <h2 class="mb-4">Balanza armonizada</h2>
            <livewire:balanza-form />
        </div>
    </div>
@endsection
