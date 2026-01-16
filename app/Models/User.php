<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RolEnum;
use App\Models\Actividad;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'apellido_materno',
        'apellido_paterno',
        'rol',
        'usuario',
        'password',
        'numero_de_personal',
        'password_restaurada'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'rol' => RolEnum::class
    ];

    public function actividades()
    {
        return $this->belongsToMany(
            Actividad::class,
            'permisos_actividades_usuarios', // tabla pivote
            'id_usuario',                        // FK usuario
            'id_actividad'                    // FK actividad
        )->withTimestamps();
    }

    public function puede(string $actividad): bool
    {
        // Superusuario
        if ($this->rol->value === 'Administrador') {
            return true;
        }

        return $this->actividades->contains('nombre_actividad', $actividad);
    }
}
