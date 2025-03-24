@extends('layouts.app')
@section('title', 'Ingresos por clasificar')

@section('content')
<script src="{{ asset('js/Ingresos/ingresos.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Ingresos por clasificar</h2>
                    <livewire:ingresos-por-clasificar-form/>
                </div>
            </div>
        </div>
    </div>
@endsection
