@extends('layouts.app')
@section('titulo', 'Armonización contable')

@section('content')
<div class="container d-flex justify-content-center align-items-center" 
     style="min-height: 65vh; background: radial-gradient(circle, rgba(255,255,255,1) 0%, rgba(248,249,250,1) 100%);">
    
    <div class="text-center">
        <div class="mb-4">
            <img src="{{ asset('imagenes/logo_sac_ipe_1.png') }}" alt="Logo SAC-IPE" 
                 style="height: 180px; width: auto; filter: drop-shadow(0px 10px 20px rgba(0,0,0,0.05));">
        </div>

        <hr class="mx-auto mb-4" style="width: 60px; height: 5px; background-color: #7A1737; border: none; opacity: 1; border-radius: 10px;">

        <div class="d-flex flex-column">
            <h1 class="fw-bold text-uppercase" style="color: #7A1737; font-size: 2.5rem; letter-spacing: -1px; line-height: 1;">
                Sistema de <br> 
                <span style="font-weight: 900;">Armonización Contable</span>
            </h1>
            
            <div class="mt-3 d-flex align-items-center justify-content-center">
                <span style="height: 1px; width: 30px; background: #C0C0C0;"></span>
                <span class="mx-3 text-muted fw-light" style="font-size: 1.2rem; letter-spacing: 8px; text-transform: uppercase;">
                    SAC-IPE
                </span>
                <span style="height: 1px; width: 30px; background: #C0C0C0;"></span>
            </div>
        </div>

        <p class="mt-5 text-muted small fw-light">
            Bienvenido
        </p>
    </div>
</div>
@endsection