<div class="row">
    {{-- Usuarios --}}
    <div class="col-md-4">
        <h5>Usuarios</h5>

        <div class="mb-2">
            <input type="text" class="form-control" placeholder="Buscar usuario..." wire:model.live="busquedaUsuario">
        </div>

        <div class="border rounded" style="max-height: 450px; overflow-y: auto;">
            <ul class="list-group list-group-flush">
                @foreach ($usuarios as $usuario)
                    <li
                        class="list-group-item
            {{ $usuarioSeleccionadoId === $usuario->id ? 'bg_secundario bg-opacity-25' : '' }}">

                        <button class="btn btn-link p-0 text-start text-dark w-100"
                            wire:click="seleccionarUsuario({{ $usuario->id }})" wire:loading.attr="disabled"
                            wire:target="seleccionarUsuario({{ $usuario->id }})">

                            <span wire:loading.remove wire:target="seleccionarUsuario({{ $usuario->id }})">
                                {{ $usuario->nombre }}
                                {{ $usuario->apellido_paterno }}
                                {{ $usuario->apellido_materno }}
                            </span>

                            <span wire:loading wire:target="seleccionarUsuario({{ $usuario->id }})">
                                Cargando...
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>

        </div>
    </div>



    {{-- Permisos --}}
    <div class="col-md-8">
        @if ($usuarioSeleccionadoId)
            <h5>Permisos {{ $nombreUsuarioSeleccionado }}</h5>

            <div class="border rounded p-3" style="max-height: 500px; overflow-y: auto;" wire:loading.attr="disabled"
                wire:target="guardarPermisos" wire:loading.class="opacity-50">

                @foreach ($actividades as $actividad)
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" wire:model="permisosSeleccionados"
                            value="{{ $actividad->id }}" id="permiso_{{ $actividad->id }}" wire:loading.attr="disabled"
                            wire:target="guardarPermisos">

                        <label class="form-check-label" for="permiso_{{ $actividad->id }}">
                            {{ str_replace('.', ' > ', $actividad->nombre_actividad) }}
                        </label>
                    </div>
                @endforeach
            </div>

            <button class="btn btn_primario mt-3" wire:click="guardarPermisos" wire:loading.attr="disabled"
                wire:target="guardarPermisos">

                <span wire:loading.remove wire:target="guardarPermisos">
                    Guardar permisos
                </span>

                <span wire:loading wire:target="guardarPermisos">
                    Guardando...
                </span>
            </button>
        @else
            <p class="text-muted">Seleccione un usuario</p>
        @endif
    </div>

</div>
<script>
    window.addEventListener('mostrarCargando', () => {
        $('#loadingScreen').prop('hidden', false);
    });

    window.addEventListener('esconderCargando', () => {
        $('#loadingScreen').prop('hidden', true);
        toastr.clear();
    });
</script>
