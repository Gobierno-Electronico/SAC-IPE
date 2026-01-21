@extends('layouts.app')
@section('title', 'Administración de permisos')

@section('content')
    <div class="row justify-content-center p-5">
        <div class="col-md-12">
            <div class="card shadow border-0">
                <div class="card-body bg-white p-5">
                    <h2>Administración de permisos de usuarios</h2>
                    <livewire:administracion-permisos-usuarios/>
                </div>
            </div>
        </div>
    </div>
@endsection