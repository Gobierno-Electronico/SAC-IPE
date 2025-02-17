@extends('layouts.app')
@section('title', 'Ejercido')

@section('content')
    <script src="{{ asset('js/Egresos/egresos.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Egresos capítulo 1000 Servicios Personales</h2>
                    <h4>Ejercido</h4>
                    <livewire:egresos.egresos-capitulo1-ejercido-form/>
                </div>
            </div>
        </div>
    </div>
@endsection
