<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use GMP;
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

        // Gates para catálogos_____________________________________________________
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

        //Gates para presupuesto___________________________________________________________
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
            'presupuesto.consultas',
            fn($user) =>
            $user->puede('presupuesto.consultas')
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
            'presupuesto.afectaciones_ingresos',
            fn($user) =>
            $user->puede('presupuesto.afectaciones_ingresos')
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
            'presupuesto.afectaciones_egresos',
            fn($user) =>
            $user->puede('presupuesto.afectaciones_egresos')
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

        //Gates para contabilidad_______________________________________________________________________
        Gate::define(
            'contabilidad',
            fn($user) =>
            $user->puede('contabilidad')
        );

        Gate::define(
            'contabilidad.reportes',
            fn($user)  =>
            $user->puede('contabilidad.reportes')
        );

        Gate::define(
            'contabilidad.reportes.balanza_armonizada',
            fn($user)  =>
            $user->puede('contabilidad.reportes.balanza_armonizada')
        );

        Gate::define(
            'contabilidad.reportes.libro_mayor',
            fn($user)  =>
            $user->puede('contabilidad.reportes.libro_mayor')
        );

        Gate::define(
            'contabilidad.reportes.libro_diario',
            fn($user)  =>
            $user->puede('contabilidad.reportes.libro_diario')
        );

        Gate::define(
            'contabilidad.reportes.estado_de_cuenta',
            fn($user)  =>
            $user->puede('contabilidad.reportes.estado_de_cuenta')
        );

        Gate::define(
            'contabilidad.reportes.estado_de_actividades',
            fn($user)  =>
            $user->puede('contabilidad.reportes.estado_de_actividades')
        );

        Gate::define(
            'contabilidad.reportes.estado_de_situacion_financiera',
            fn($user)  =>
            $user->puede('contabilidad.reportes.estado_de_situacion_financiera')
        );

        Gate::define(
            'contabilidad.reportes.estado_de_cambios_en_la_situacion_financiera',
            fn($user)  =>
            $user->puede('contabilidad.reportes.estado_de_cambios_en_la_situacion_financiera')
        );

        Gate::define(
            'contabilidad.reportes.estado_de_analitico_del_activo',
            fn($user)  =>
            $user->puede('contabilidad.reportes.estado_de_analitico_del_activo')
        );

        Gate::define(
            'contabilidad.consultar',
            fn($user)  =>
            $user->puede('contabilidad.consultar')
        );

        Gate::define(
            'contabilidad.consultar.poliza_inicial',
            fn($user)  =>
            $user->puede('contabilidad.consultar.poliza_inicial')
        );

        Gate::define(
            'contabilidad.consultar.poliza_diario',
            fn($user)  =>
            $user->puede('contabilidad.consultar.poliza_diario')
        );

        Gate::define(
            'contabilidad.consultar.deudores',
            fn($user)  =>
            $user->puede('contabilidad.consultar.deudores')
        );

        Gate::define(
            'contabilidad.carga',
            fn($user)  =>
            $user->puede('contabilidad.carga')
        );

        Gate::define(
            'contabilidad.carga.poliza_inicial',
            fn($user)  =>
            $user->puede('contabilidad.carga.poliza_inicial')
        );

        Gate::define(
            'contabilidad.carga.poliza_diario',
            fn($user)  =>
            $user->puede('contabilidad.carga.poliza_diario')
        );

        Gate::define(
            'contabilidad.carga.auxiliares',
            fn($user)  =>
            $user->puede('contabilidad.carga.auxiliares')
        );

        //Gates para Ingresos_____________________________________________________
        Gate::define(
            'ingresos',
            fn($user)  =>
            $user->puede('ingresos')
        );

        Gate::define(
            'ingresos.devengado',
            fn($user)  =>
            $user->puede('ingresos.devengado')
        );

        Gate::define(
            'ingresos.recaudado',
            fn($user)  =>
            $user->puede('ingresos.recaudado')
        );

        Gate::define(
            'ingresos.cobro_en_especie',
            fn($user)  =>
            $user->puede('ingresos.cobro_en_especie')
        );

        Gate::define(
            'ingresos.ingresos_por_clasificar',
            fn($user)  =>
            $user->puede('ingresos.ingresos_por_clasificar')
        );

        Gate::define(
            'ingresos.depositos_en_bancos',
            fn($user)  =>
            $user->puede('ingresos.depositos_en_bancos')
        );

        Gate::define(
            'ingresos.devengado_prev_recaudado',
            fn($user)  =>
            $user->puede('ingresos.devengado_prev_recaudado')
        );

        Gate::define(
            'ingresos.devengado_prev_recaudado_ejercicios_anteriores',
            fn($user)  =>
            $user->puede('ingresos.devengado_prev_recaudado_ejercicios_anteriores')
        );

        //Gates para egresos_______________________________________________________________
        Gate::define(
            'egresos',
            fn($user)  =>
            $user->puede('egresos')
        );

        Gate::define(
            'egresos.capitulo1000',
            fn($user)  =>
            $user->puede('egresos.capitulo1000')
        );

        Gate::define(
            'egresos.capitulo1000.comprometido',
            fn($user)  =>
            $user->puede('egresos.capitulo1000.comprometido')
        );

        Gate::define(
            'egresos.capitulo1000.devengado',
            fn($user)  =>
            $user->puede('egresos.capitulo1000.devengado')
        );

        Gate::define(
            'egresos.capitulo1000.ejercido',
            fn($user)  =>
            $user->puede('egresos.capitulo1000.ejercido')
        );

        Gate::define(
            'egresos.capitulo1000.pagado',
            fn($user)  =>
            $user->puede('egresos.capitulo1000.pagado')
        );

        Gate::define(
            'egresos.capitulo2000y3000',
            fn($user)  =>
            $user->puede('egresos.capitulo2000y3000')
        );

        Gate::define(
            'egresos.capitulo2000y3000.comprometido',
            fn($user)  =>
            $user->puede('egresos.capitulo2000y3000.comprometido')
        );

        Gate::define(
            'egresos.capitulo2000y3000.devengado',
            fn($user)  =>
            $user->puede('egresos.capitulo2000y3000.devengado')
        );

        Gate::define(
            'egresos.capitulo2000y3000.ejercido',
            fn($user)  =>
            $user->puede('egresos.capitulo2000y3000.ejercido')
        );

        Gate::define(
            'egresos.capitulo2000y3000.pagado',
            fn($user)  =>
            $user->puede('egresos.capitulo2000y3000.pagado')
        );

        Gate::define(
            'egresos.capitulo4000',
            fn($user)  =>
            $user->puede('egresos.capitulo4000')
        );

        Gate::define(
            'egresos.capitulo4000.comprometido',
            fn($user)  =>
            $user->puede('egresos.capitulo4000.comprometido')
        );

        Gate::define(
            'egresos.capitulo4000.devengado',
            fn($user)  =>
            $user->puede('egresos.capitulo4000.devengado')
        );

        Gate::define(
            'egresos.capitulo4000.ejercido',
            fn($user)  =>
            $user->puede('egresos.capitulo4000.ejercido')
        );

        Gate::define(
            'egresos.capitulo4000.pagado',
            fn($user)  =>
            $user->puede('egresos.capitulo4000.pagado')
        );

        Gate::define(
            'egresos.capitulo5000',
            fn($user)  =>
            $user->puede('egresos.capitulo5000')
        );

        Gate::define(
            'egresos.capitulo5000.comprometido',
            fn($user)  =>
            $user->puede('egresos.capitulo5000.comprometido')
        );

        Gate::define(
            'egresos.capitulo5000.devengado',
            fn($user)  =>
            $user->puede('egresos.capitulo5000.devengado')
        );

        Gate::define(
            'egresos.capitulo5000.ejercido',
            fn($user)  =>
            $user->puede('egresos.capitulo5000.ejercido')
        );

        Gate::define(
            'egresos.capitulo5000.pagado',
            fn($user)  =>
            $user->puede('egresos.capitulo5000.pagado')
        );

        //Gates para préstamos_____________________________________
        Gate::define(
            'prestamos',
            fn($user) =>
            $user->puede('prestamos')
        );

        Gate::define(
            'prestamos.otorgamiento_compromiso-devengado',
            fn($user) =>
            $user->puede('prestamos.otorgamiento_compromiso-devengado')
        );

        Gate::define(
            'prestamos.otorgamiento_compromiso-devengado.prestamos_iniciales',
            fn($user) =>
            $user->puede('prestamos.otorgamiento_compromiso-devengado.prestamos_iniciales')
        );

        Gate::define(
            'prestamos.otorgamiento_compromiso-devengado.prestamos_con_renovacion',
            fn($user) =>
            $user->puede('prestamos.otorgamiento_compromiso-devengado.prestamos_con_renovacion')
        );

        Gate::define(
            'prestamos.otorgamiento_ejercido-pagado-recaudado',
            fn($user) =>
            $user->puede('prestamos.otorgamiento_ejercido-pagado-recaudado')
        );

        Gate::define(
            'prestamos.otorgamiento_ejercido-pagado-recaudado.prestamos_iniciales',
            fn($user) =>
            $user->puede('prestamos.otorgamiento_ejercido-pagado-recaudado.prestamos_iniciales')
        );

        Gate::define(
            'prestamos.otorgamiento_ejercido-pagado-recaudado.prestamos_con_renovacion',
            fn($user) =>
            $user->puede('prestamos.otorgamiento_ejercido-pagado-recaudado.prestamos_con_renovacion')
        );

        Gate::define(
            'prestamos.recuperacion_recaudado',
            fn($user) =>
            $user->puede('prestamos.recuperacion_recaudado')
        );

        Gate::define(
            'prestamos.recuperacion_recaudado.prestamos_iniciales',
            fn($user) =>
            $user->puede('prestamos.recuperacion_recaudado.prestamos_iniciales')
        );

        Gate::define(
            'prestamos.recuperacion_recaudado.prestamos_con_renovacion',
            fn($user) =>
            $user->puede('prestamos.recuperacion_recaudado.prestamos_con_renovacion')
        );

        Gate::define(
            'prestamos.cancelacion_prestamos',
            fn($user) =>
            $user->puede('prestamos.cancelacion_prestamo')
        );

        //Gates para deudores_____________________________________________________________
        Gate::define(
            'deudores',
            fn($user) =>
            $user->puede('deudores')
        );

        Gate::define(
            'deudores.otorgamiento_de_anticipo-viaticos-fondo_fijo',
            fn($user) =>
            $user->puede('deudores.otorgamiento_de_anticipo-viaticos-fondo_fijo')
        );

        Gate::define(
            'deudores.reintegro_de_anticipo-viaticos-fondo_fijo',
            fn($user) =>
            $user->puede('deudores.reintegro_de_anticipo-viaticos-fondo_fijo')
        );

        Gate::define(
            'deudores.comprobacion_de_anticipo-viaticos-cancelacion_de_fondo_fijo',
            fn($user) =>
            $user->puede('deudores.comprobacion_de_anticipo-viaticos-cancelacion_de_fondo_fijo')
        );

        Gate::define(
            'deudores.pago_de_retenciones',
            fn($user) =>
            $user->puede('deudores.pago_de_retenciones')
        );

        //Gates para consulta de movimientos_________________________________________________________________
        Gate::define(
            'consultar_movimientos',
            fn($user) =>
            $user->puede('consultar_movimientos')
        );

        Gate::define(
            'consultar_movimientos.egresos',
            fn($user) =>
            $user->puede('consultar_movimientos.egresos')
        );

        Gate::define(
            'consultar_movimientos.ingresos',
            fn($user) =>
            $user->puede('consultar_movimientos.ingresos')
        );

        Gate::define(
            'consultar_movimientos.prestamos',
            fn($user) =>
            $user->puede('consultar_movimientos.prestamos')
        );

        Gate::define(
            'consultar_movimientos.concluidos',
            fn($user) =>
            $user->puede('consultar_movimientos.concluidos')
        );

        //bGates para borrar movimientos contables_________________________________________________________________
        Gate::define(
            'botonBorrarMovimiento',
            fn($user) =>
            $user->puede('botonBorrarMovimiento')
        );
    }
}
