@props(['value', 'mensajeBoton', 'accion', 'titulo'])

<div class="modal fade" id="confirmModal{{$value}}" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmModalLabel">{{ $titulo }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          {{ $slot }}
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="confirmBtn"  data-bs-dismiss="modal" wire:click='{{ $accion }}'>{{ $mensajeBoton }}</button>
        </div>
      </div>
    </div>
</div>

