@extends('layouts.app')
@section('title', 'Cargar matriz')

@section('content')
     
    <div class="container mt-5">
        <div class="card shadow border-0 p-5">
            <h2>
                Carga de matrices de conversión
            </h2>
            <livewire:matriz-carga-form/>
        </div>
    </div>
@endsection
