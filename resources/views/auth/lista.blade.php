@extends('layouts.app')
@section('titulo', 'Usuarios')

@section('content')
    {{-- {{dd(session()->all())}} --}}

    <div class="container">
        <h2>Lista de usuarios</h2>
        <livewire:usuarios-table></livewire:usuarios-table>
            <form class="d-flex mt-2" action="/register">
                <button type="submit" class="btn btn_primario ms-auto">Nuevo usuario</button>
            </form>




    </div>

@endsection

<script></script>
