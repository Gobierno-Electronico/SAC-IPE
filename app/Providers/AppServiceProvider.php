<?php

namespace App\Providers;

use App\Enums\RolEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Builder::macro('search', function ($fields, $string) {
            if (!$string)
                return $this; // Si la cadena de búsqueda está vacía, retornamos el builder sin modificaciones

            $fields = Arr::wrap($fields); // Envuelve el valor dado en un array si no es un array.

            return $this->where(function ($query) use ($fields, $string) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'like', '%' . $string . '%');
                }
            });
            // return $string ? $this->where($field, 'like', '%' . $string . '%') : $this;
        });

        Builder::macro('searchByYear', function ($field, $year) {
            if (!$year) {
                return $this; // Si el año no está especificado, retornamos el builder sin modificaciones
            }
        
            return $this->where(function ($query) use ($field, $year) {
                $query->whereYear($field, $year);
            });
        });

        Builder::macro('interaccion', function ($tabla, $identificadorPropio, $identficadorExterno, $campoWhere ,$valorWhere) {
            if (!$valorWhere)
                return $this; // Si la cadena de búsqueda está vacía, retornamos el builder sin modificaciones


            return $this->join($tabla, $identificadorPropio, '=', $tabla . '.' . $identficadorExterno)
                ->where($tabla . '.' . $campoWhere , '=', $valorWhere);

        });

        Builder::macro('interaccionCuenta', function ($tabla, $identificadorPropio, $identficadorExterno, $campoWhere, $campoWhere2 ,$valorWhere ,$valorWhere2) {
            if (!$valorWhere || !$valorWhere2)
                return $this; // Si la cadena de búsqueda está vacía, retornamos el builder sin modificaciones


            return $this->join($tabla, $identificadorPropio, '=', $tabla . '.' . $identficadorExterno)
                ->where([[$tabla . '.' . $campoWhere , '=', $valorWhere], [$tabla . '.' . $campoWhere2, '=', $valorWhere2]]);
        });


        Blade::if('admin', function () {
            return Auth::user()?->rol == RolEnum::ADMINISTRADOR;
        });

        Blade::if('tecnico', function () {
            return Auth::user()?->rol == RolEnum::TECNICO;
        });

        Blade::if('general', function () {
            return Auth::user()?->rol == RolEnum::GENERAL;
        });

        Blade::if('jefe_oficina', function () {
            return Auth::user()?->rol == RolEnum::JEFE_OFICINA;
        });

        Blade::if('capturista', function () {
            return Auth::user()?->rol == RolEnum::CAPTURISTA;
        });

        Blade::if('analista', function () {
            return Auth::user()?->rol == RolEnum::ANALISTA;
        });
    }
}
