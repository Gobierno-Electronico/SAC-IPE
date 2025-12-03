@extends('layouts.app')
@section('title', 'Estado de situación financiera')

@section('content')
    <div class="container mt-5">
        <div class="shadow rounded p-5 mx-auto w-75">
            <h2 class="mb-4">Estado de situación financiera</h2>
            <livewire:estado-situacion-financiera />
        </div>
    </div>
@endsection
