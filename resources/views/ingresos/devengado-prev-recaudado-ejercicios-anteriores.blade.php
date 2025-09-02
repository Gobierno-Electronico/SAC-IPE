@extends('layouts.app')
@section('title', 'Devengado prev. Recaudado')

@section('content')
    <script src="{{ asset('js/Ingresos/ingresos.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">  
                <div class="card-body bg-white p-5"> 
                    <h2>Ingresos devengado previamente recaudado ejercicios anteriores</h2>
                    <livewire:ingresos-devengado-prev-recaudado-ejercicios-anteriores-form/>    
                </div>
            </div>
        </div>
    </div>
@endsection