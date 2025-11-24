@extends('layouts.app')
@section('title', 'Libro diario')

@section('content')
    <div class="container mt-5">
        <div class="shadow rounded p-5 mx-auto w-75">
            <h2 class="mb-4">Libro diario</h2>
            <livewire:libro-diario />
        </div>
    </div>
@endsection
