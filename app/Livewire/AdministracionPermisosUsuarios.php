<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\Actividad;

class AdministracionPermisosUsuarios extends Component
{
    public $usuarios;
    public $usuarioSeleccionadoId = null;
    public $nombreUsuarioSeleccionado = "";

    public $actividades = [];
    public $permisosSeleccionados = [];
    public $busquedaUsuario = '';


    public function mount()
    {
        $this->usuarios = User::orderBy('nombre')->get();
        $this->actividades = Actividad::orderBy('nombre_actividad')->get();
    }

    public function seleccionarUsuario($userId)
    {
        $this->usuarioSeleccionadoId = $userId;
        $usuario = User::findOrFail($userId);
        $this->nombreUsuarioSeleccionado = $usuario->nombre . " " . $usuario->apellido_paterno . " " . $usuario->apellido_materno;

        $this->permisosSeleccionados = User::findOrFail($userId)
            ->actividades()
            ->pluck('actividades.id')
            ->toArray();
    }

    public function guardarPermisos()
    {
        try {
            DB::beginTransaction();
            User::findOrFail($this->usuarioSeleccionadoId)
                ->actividades()
                ->sync($this->permisosSeleccionados);
            DB::commit();
            $this->dispatch('mostrarMensaje', mensaje: 'Permisos actualizados', tipo: 'success', tiempo: 3000);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al actualizar los permisos ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al actualizar los permisos', tipo: 'error', tiempo: 3000);
        }
    }

    public function render()
    {
        $this->usuarios = User::where(function ($q) {
            $q->where('nombre', 'like', "%{$this->busquedaUsuario}%")
                ->orWhere('apellido_paterno', 'like', "%{$this->busquedaUsuario}%")
                ->orWhere('apellido_materno', 'like', "%{$this->busquedaUsuario}%");
        })
            ->orderBy('nombre')
            ->get();

        return view('livewire.administracion-permisos-usuarios', [
            'usuarios' => $this->usuarios,
        ]);

    }
}
