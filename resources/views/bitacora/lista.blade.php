@extends('layouts.app')
@section('titulo', 'Bitácoras')
@section('content')
    <div class="container">
        <h1>Registros de bitácora</h1>
        <livewire:bitacoras-table></livewire:bitacoras-table>
    </div>
    
@endsection