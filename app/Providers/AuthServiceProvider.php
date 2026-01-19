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
            'catalogos',
            fn($user) =>
            $user->puede('catalogos')
        );

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

        Gate::define(
            'catalogos.clasificador_tipo_gasto',
            fn($user) =>
            $user->puede('catalogos.clasificador_tipo_gasto')
        );

        Gate::define(
            'catalogos.clasificador_objeto_gasto',
            fn($user) =>
            $user->puede('catalogos.clasificador_objeto_gasto')
        );

        Gate::define(
            'catalogos.clasificador_fuente_financiamiento',
            fn($user) =>
            $user->puede('catalogos.clasificador_fuente_financiamiento')
        );

        Gate::define(
            'catalogos.clasificador_rubro_ingreso',
            fn($user) =>
            $user->puede('catalogos.clasificador_rubro_ingreso')
        );

        Gate::define(
            'catalogos.matrices_conversion',
            fn($user) =>
            $user->puede('catalogos.matrices_conversion')
        );

        Gate::define(
            'catalogos.matrices_conversion.carga',
            fn($user) =>
            $user->puede('catalogos.matrices_conversion.carga')
        );

        Gate::define(
            'catalogos.matrices_conversion.consulta',
            fn($user) =>
            $user->puede('catalogos.matrices_conversion.consulta')
        );

        Gate::define(
            'presupuesto',
            fn($user) =>
            $user->puede('presupuesto')
        );

        Gate::define(
            'presupuesto.cargar_presupuesto',
            fn($user) =>
            $user->puede('presupuesto.cargar_presupuesto')
        );

        Gate::define(
            'presupuesto.cargar_presupuesto.ingresos',
            fn($user) =>
            $user->puede('presupuesto.cargar_presupuesto.ingresos')
        );

        Gate::define(
            'presupuesto.cargar_presupuesto.egresos',
            fn($user) =>
            $user->puede('presupuesto.cargar_presupuesto.egresos')
        );

        Gate::define(
            'presupuesto.consulta_presupuesto',
            fn($user) =>
            $user->puede('presupuesto.consulta_presupuesto')
        );

        Gate::define(
            'presupuesto.consulta_presupuesto.ingresos',
            fn($user) =>
            $user->puede('presupuesto.consulta_presupuesto.ingresos')
        );

        Gate::define(
            'presupuesto.consulta_presupuesto.egresos',
            fn($user) =>
            $user->puede('presupuesto.consulta_presupuesto.egresos')
        );

        Gate::define(
            'presupuesto.consultas.tipos_de_presupuesto',
            fn($user) =>
            $user->puede('presupuesto.consultas.tipos_de_presupuesto')
        );

        Gate::define(
            'presupuesto.consultas.ampliaciones-reducciones',
            fn($user) =>
            $user->puede('presupuesto.consultas.ampliaciones-reducciones')
        );

        Gate::define(
            'presupuesto.consultas.consultar_transferencias',
            fn($user) =>
            $user->puede('presupuesto.consultas.consultar_transferencias')
        );

        Gate::define(
            'presupuesto.afectaciones_ingresos.ampliacion',
            fn($user) =>
            $user->puede('presupuesto.afectaciones_ingresos.ampliacion')
        );

        Gate::define(
            'presupuesto.afectaciones_ingresos.reduccion',
            fn($user) =>
            $user->puede('presupuesto.afectaciones_ingresos.reduccion')
        );

        Gate::define(
            'presupuesto.afectaciones_egresos.ampliacion',
            fn($user) =>
            $user->puede('presupuesto.afectaciones_egresos.ampliacion')
        );

        Gate::define(
            'presupuesto.afectaciones_egresos.reduccion',
            fn($user) =>
            $user->puede('presupuesto.afectaciones_egresos.reduccion')
        );

        Gate::define(
            'presupuesto.reclasificacion-recalendarizacion',
            fn($user) =>
            $user->puede('presupuesto.reclasificacion-recalendarizacion')
        );

        Gate::define(
            'contabilidad',
            fn($user) =>
            $user->puede('contabilidad')
        );
    }
}
