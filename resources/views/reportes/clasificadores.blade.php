@extends('layouts.app')
@section('title', 'Catálogos')

@section('content')

    <div class="container mt-5">
        <div class="shadow rounded p-5">
            <h2 class="mb-4">{{ $titulo }}</h2>
            <livewire:clasificadores-table :tipo="$tipo" :titulo="$titulo"/>
        </div>
    </div>
    
@endsection
