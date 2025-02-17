@extends('layouts.app')
@section('title', 'Comprometido')

@section('content')
    <script src="{{ asset('js/Egresos/egresos.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Egresos capítulo 2000 Materiales/Suministros y 3000 Servicios generales</h2>
                    <h4>Comprometido </h4>
                    <livewire:egresos.egresos-capitulo2y3-comprometido-form/>
                </div>
            </div>
        </div>
    </div>
@endsection
