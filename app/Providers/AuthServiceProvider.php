<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('acceso-cuentas', function ($user) {
            // dd($user->rol);
            return in_array($user->rol->value, [
                'Administrador',
                'Jefe_Departamento_Contabilidad_Presupuesto',
                'Jefe_Oficina_Contabilidad_general'
            ]);
        });

        Gate::define('acceso-presupuesto', function ($user) {
            return in_array($user->rol->value, [
                'Administrador',
                'Jefe_Oficina_Control_Presupuestal', 
                'Analista'
            ]);
        });

        Gate::define('acceso-contabilidad-reportes', function ($user) {
            return in_array($user->rol->value, [
                'Administrador',
                'Jefe_Departamento_Contabilidad_Presupuesto', 
                'Jefe_Oficina_Contabilidad_general'
            ]);
        });

        Gate::define('acceso-contabilidad-consultar-carga', function ($user) {
            return in_array($user->rol->value, [
                'Administrador',
                'Jefe_Oficina_Contabilidad_general',
                'Analista'
            ]);
        });

    }
}
