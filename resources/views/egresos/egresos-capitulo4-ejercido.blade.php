@extends('layouts.app')
@section('title', 'Ejercido')

@section('content')
    <script src="{{ asset('js/Egresos/egresos.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Egresos capítulo 4000 transferencias y pensiones</h2>
                    <h4>Ejercido</h4>
                    <livewire:egresos-capitulo4-ejercido-form/>
                </div>
            </div>
        </div>
    </div>
@endsection
