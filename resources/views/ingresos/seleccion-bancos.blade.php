@extends('layouts.app')
@section('title', 'Selección de bancos')

@section('content')
     
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">  
                <div class="card-body bg-white p-5"> 
                    <h2>Ingresos</h2>
                    <livewire:seleccion-bancos-form/>
                </div>
            </div>
        </div>
    </div>
@endsection