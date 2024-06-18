@extends('layouts.app')
@section('titulo', 'Tipos de presupuesto')
@section('content')
    <div class="container mt-5">
        <div class="shadow rounded p-5">
            <livewire:tipos-presupuesto-form/>
        </div>
    </div>
    
@endsection