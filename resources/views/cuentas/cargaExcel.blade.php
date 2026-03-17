@extends('layouts.app')

@section('titulo', 'Carga de Cuentas en Excel')

@section('content')
    <script src="{{ asset('js/importarExcel.js') }}"></script>
    <script src="{{ asset('js/metodos.js') }}"></script>
    <div class="container mt-5">
        <div class="card shadow border-0 p-5">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <h2 class="mt-3">
                        <a href="{{ url('/cuentas') }}" class="d-inline-block mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" height="1em"
                                viewBox="0 0 540 540"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. -->
                                <style>
                                    svg {
                                        fill: #198754
                                    }
                                </style>
                                <path
                                    d="M512 256A256 256 0 1 0 0 256a256 256 0 1 0 512 0zM217.4 376.9L117.5 269.8c-3.5-3.8-5.5-8.7-5.5-13.8s2-10.1 5.5-13.8l99.9-107.1c4.2-4.5 10.1-7.1 16.3-7.1c12.3 0 22.3 10 22.3 22.3l0 57.7 96 0c17.7 0 32 14.3 32 32l0 32c0 17.7-14.3 32-32 32l-96 0 0 57.7c0 12.3-10 22.3-22.3 22.3c-6.2 0-12.1-2.6-16.3-7.1z" />
                            </svg>
                        </a>
                        Cuentas
                    </h2>
                </div>
                <h4 class="">
                    Importar cuentas a partir de Excel
                </h4>
            </div>
            <form enctype="multipart/form-data" class="text-center text-md-end">
                @csrf
                <div class="mt-5">
                    <input type="file" name="file" id="file" required class="form-control" accept=".xls, .xlsx">
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2 mt-5">
                    <a href="{{ url('/plantillaExcel/plantilla_ejemplo_excel_cuentas.xlsx') }}"><button type="button"
                            id="btnDescargar" class="btn btn_primario shadow border-0">Descargar
                            plantilla</button></a>
                    <button type="button" id="btnImportar" class="btn btn_primario shadow border-0"
                        onclick="importarExcel(this)">Importar Excel</button>
                </div>
            </form>
        </div>
    </div>
    </div>
@endsection
