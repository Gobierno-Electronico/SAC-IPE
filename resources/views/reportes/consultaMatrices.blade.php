@extends('layouts.app')
@section('title', 'Consultar matriz')

@section('content')
     
    <div class="container mt-5">
        <div class="card shadow border-0 p-5">
            <h2>
                Consulta de matrices de conversión
            </h2>
            <livewire:matriz-consulta-form/>
        </div>
    </div>
@endsection
