<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DB;
use Illuminate\Support\Facades\View;
use Log;

class VerificarEstadoSistema
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $anio = session('anioSeleccionado');
        Log::info($anio);

        $polizasPresupuestales = DB::table('polizas')
            ->select('categoria', 'evento')
            ->where('tipo_poliza', 'P')
            ->whereYear('fecha', $anio)
            ->groupBy('categoria', 'evento')
            ->get();

        $hayPresupuestoCompleto = $polizasPresupuestales->count() === 7;

        $haySaldosIniciales = DB::table('polizas')
            ->where('categoria', 'SALDO INICIAL')
            ->whereYear('fecha', $anio)
            ->exists();

        View::share([
            'hayPresupuestoCompleto' => $hayPresupuestoCompleto,
            'haySaldosIniciales' => $haySaldosIniciales,
        ]);

        return $next($request);
    }
}
