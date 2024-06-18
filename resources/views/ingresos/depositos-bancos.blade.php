@extends('layouts.app')
@section('title', 'Depósitos en bancos')

@section('content')
<script src="{{ asset('js/Ingresos/ingresos.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">  
                <div class="card-body bg-white p-5"> 
                    <h2>Depósitos en bancos</h2>
                    <livewire:depositos-bancos-form/>    
                </div>
            </div>
        </div>
    </div>
@endsection