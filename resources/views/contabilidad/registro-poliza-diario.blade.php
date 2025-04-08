@extends('layouts.app')
@section('title', 'Póliza diario')

@section('content')
<script src="{{ asset('js/Presupuesto/reclasificacion.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Registro de Póliza Diario</h2>
                    <h4>Diversos conceptos</h4>
                    <livewire:registro-poliza-diario-form/>
                </div>
            </div>
        </div>
    </div>
@endsection
