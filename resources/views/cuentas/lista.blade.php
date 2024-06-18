@extends('layouts.app')
@section('titulo', 'Cuentas')

@section('content')
    {{-- {{dd(session()->all())}} --}}

    <div class="container">
        <h2>Lista de cuentas</h2>
        <livewire:cuentas-table></livewire:cuentas-table>
        <div class="mt-4 text-end">
            <a href="{{ url('/cuentas/mostrarRegistrarCuenta') }}" type="button" class="btn btn-success shadow border-0">Nueva
                cuenta</a>
            <a href="{{ url('/cuentas/cargaExcel') }}" type="button" class="btn btn-success ms-2 shadow border-0"> Importar
                Excel</a>
        </div>
    </div>
@endsection

<script>
   
</script>
