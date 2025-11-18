@extends('layouts.app')
@section('title', 'Cargar fuente')

@section('content')
     
    <div class="container mt-5">
        <div class="card shadow border-0 p-5">
            <h2>
                Carga de documentos fuente
            </h2>
            <livewire:fuente-carga-form/>
        </div>
    </div>
@endsection
