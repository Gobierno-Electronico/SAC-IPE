@extends('layouts.app')
@section('title', 'Pago de reintegro')

@section('content')
     
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">  
                <div class="card-body bg-white p-5"> 
                    <h2>Pago de reintegro</h2>
                    <livewire:pago-reintegro-form/>    
                </div>
            </div>
        </div>
    </div>
@endsection