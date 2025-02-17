@extends('layouts.app')
@section('title', 'Pagado')

@section('content')
    <script src="{{ asset('js/Egresos/egresos.js') }}"></script>
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Egresos capítulo 4000 transferencias y pensiones</h2>
                    <h4>Pagado</h4>
                    <livewire:egresos.egresos-capitulo4-pagado-form/>
                </div>
            </div>
        </div>
    </div>
@endsection
