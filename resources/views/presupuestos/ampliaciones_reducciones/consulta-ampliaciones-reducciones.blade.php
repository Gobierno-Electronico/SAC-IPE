@extends('layouts.app')
@section('title', 'Consulta de Ampliaciones y Reducciones')

@section('content')
     

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <h2>Consulta de ampliaciones y reducciones</h2>
                <livewire:consulta-ampliaciones-reducciones-table />
            </div>
        </div>
    </div>
@endsection
