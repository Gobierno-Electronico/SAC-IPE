@extends('layouts.app')
@section('title', 'Detalle de afectación presupuestal')

@section('content')
     
    <script src="{{ asset('js/Presupuesto/ampliaciones.js') }}"></script>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <h2>Detalle de afectación presupuestal</h2>
                <livewire:detalle-afectacion-table :$evento />
            </div>
        </div>
    </div>
@endsection
