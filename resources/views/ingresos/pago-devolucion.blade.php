@extends('layouts.app')
@section('title', 'Pago de devolución')

@section('content')
     
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">  
                <div class="card-body bg-white p-5"> 
                    <h2>Pago de devolución</h2>
                    <livewire:pago-devolucion-form/>    
                </div>
            </div>
        </div>
    </div>
@endsection