@extends('layouts.app')
@section('titulo', 'Guía contabilizadora')

@section('content')
    {{-- {{dd(session()->all())}} --}}

    <div class="container">
        <h2>Guía contabilizadora</h2>
        <div class="row">
            <div class='col'>
                <livewire:guia-contabilizadora-clasificadores-table></livewire:guia-contabilizadora-clasificadores-table-table>
            </div>
            <div class='col'>
                <livewire:guia-contabilizadora-conceptos-table></livewire:guia-contabilizadora-conceptos-table-table>
            </div>
            <div class='col'>
                <livewire:guia-contabilizadora-cuentas-table></livewire:guia-contabilizadora-cuentas-table-table>

            </div>
            <x-modal value="CuentaCuenta" mensajeBoton="" accion="" titulo="Cuenta Relacionada"></x-modal>

        {{-- <livewire:cuentas-table></livewire:cuentas-table>
        <div class="mt-4 text-end">
            <a href="{{ url('/cuentas/mostrarRegistrarCuenta') }}" type="button" class="btn btn-success shadow border-0">Nueva
                cuenta</a>
            <a href="{{ url('/cuentas/cargaExcel') }}" type="button" class="btn btn-success ms-2 shadow border-0"> Importar
                Excel</a>
        </div> --}}
    </div>
@endsection

<script>
    window.addEventListener('post-created', event => {
        const modal = $("#confirmModalCuentaCuenta")
        const cuenta = (event.__livewire.params.info);
        const bottonBorrar = $("#confirmModalCuentaCuenta #confirmBtn").remove()
        $("#confirmModalCuentaCuenta .modal-body").html(
            `Codigo cuenta: ${cuenta.Codigo_cuenta}<br>Descripción: ${cuenta.Descripcion_cuenta}`
        );
        modal.modal("show")
    });
</script>
