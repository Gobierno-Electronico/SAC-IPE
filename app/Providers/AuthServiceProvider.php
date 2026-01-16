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

        Gate::define(
            'catalogos.cuentas',
            fn($user) =>
            $user->puede('catalogos.cuentas')
        );

        Gate::define(
            'catalogos.clasificador_administrativo',
            fn($user) =>
            $user->puede('catalogos.clasificador_administrativo')
        );

        Gate::define(
            'catalogos.clasificador_programatico',
            fn($user) =>
            $user->puede('catalogos.clasificador_programatico')
        );

        Gate::define(
            'catalogos.clasificador_funcional_gasto',
            fn($user) =>
            $user->puede('catalogos.clasificador_funcional_gasto')
        );
    }
}
