<?php

use App\Http\Controllers\AfectacionesLiquidasController;
use App\Http\Controllers\CuentasController;
use App\Http\Controllers\GuiaContabilizadoraController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\ContabilidadController;
use App\Http\Controllers\IngresosController;
use App\Http\Controllers\EgresosController;
use Illuminate\Support\Facades\Route;
USE App\Http\Controllers\HomeController;
use App\Http\Middleware\ResetPassword;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Auth::routes();

Route::get('/', function () {
    return redirect('home');
});
Route::get('/home', [HomeController::class, 'index'])->name('home');

//Cuentas
Route::get('/cuentas', [CuentasController::class, 'listaDeCuentas'])->name('listaDeCuentas')->middleware('role:Administrador');
Route::get('/cuentas/editar/{id}', [CuentasController::class, 'editarCuenta'])->name('editarCuenta')->middleware('role:Administrador');
Route::post('/cuentas/cambiosCuenta', [CuentasController::class, 'cambiosCuenta'])->name('cambiosCuenta')->middleware('role:Administrador');
Route::get('/cuentas/mostrarRegistrarCuenta', [CuentasController::class, 'mostrarRegistrarCuenta'])->middleware('role:Administrador');
Route::get('/cuentas/llenarSiguienteNivel', [CuentasController::class, 'llenarSiguienteNivel'])->middleware('role:Administrador');
Route::post('/cuentas/agregarCuenta', [CuentasController::class, 'agregarCuenta'])->middleware('role:Administrador');
Route::get('/cuentas/cargaExcel', [CuentasController::class, 'cargaExcel'])->name('cargaExcel')->middleware('role:Administrador');
Route::post('/importarExcel', [CuentasController::class, 'importarExcel'])->name('importarExcel')->middleware('role:Administrador');
Route::get('/plantillaExcel/{archivo}', [CuentasController::class, 'plantillaExcel'])->name('plantillaExcel')->middleware('role:Administrador');
Route::get('/limpiar-plan-cuentas', [CuentasController::class, 'limpiarCuentas'])->name('limpiarCuentas')->middleware('role:Administrador');

//Usuarios
Route::get('/usuarios', [UsuariosController::class, 'listaDeUsuarios'])->name('listaDeUsuarios')->middleware('role:Administrador');
Route::get('/usuarios/editar/{id}', [UsuariosController::class, 'editarUsuario'])->name('editarUsuario')->middleware('role:Administrador');
Route::get('/usuarios/resetPassword', [UsuariosController::class, 'cambiarPasswordVista'])->name('cambiarPasswordVista')->withoutMiddleware([ResetPassword::class]);
Route::post('/usuarios/guardar', [UsuariosController::class, 'cambiosUsuario'])->name('cambiosUsuario')->middleware('role:Administrador');
Route::post('/usuarios/resetPassword', [UsuariosController::class, 'cambiarPassword'])->name('cambiarPassword')->withoutMiddleware([ResetPassword::class]);

//Guia contabilizadora
Route::get('/crear-guia-contabilizadora', [GuiaContabilizadoraController::class, 'crearGuiaContabilizadora'])->name('crearGuiaContabilizadora')->middleware('role:Administrador');
Route::get('/relacionar-cuentas-cuentas', [GuiaContabilizadoraController::class, 'relacionarCuentasCuentas'])->name('relacionarCuentasCuentas')->middleware('role:Administrador');
Route::get('/relacionar-cuentas-cuentas-seguidos', [GuiaContabilizadoraController::class, 'relacionarCuentasCuentasSeguidas'])->name('relacionarCuentasCuentasSeguidas')->middleware('role:Administrador');
Route::get('/guia-contabilizadora', [GuiaContabilizadoraController::class,'visualizarGuiaContabilizadora'])->name('guiaContabilizadora')->middleware('role:Administrador');

//CRI
Route::get('/relacionarCuentasCRI', [CuentasController::class, 'relacionarCuentasCRI'])->name('relacionarCuentasCRI')->middleware('role:Administrador');

//CFF
Route::get('/relacionarCuentasCFF', [CuentasController::class, 'relacionarCuentasCFF'])->name('relacionarCuentasCFF')->middleware('role:Administrador');

//Presupuesto ingresos
Route::get('/presupuesto/consulta-presupuesto-ingresos',[PresupuestoController::class, 'consultaPresupuestoIngresos'])->name('consultaPresupuestoIngresos')->middleware('role:Administrador');
Route::get('/presupuesto/presupuesto-inicial-ingresos',[PresupuestoController::class, 'presupuestoInicialIngresos'])->name('presupuestoInicialIngresos')->middleware('role:Administrador');
Route::post('/presupuesto/cargar-presupuesto-inicial-ingresos',[PresupuestoController::class, 'cargarPresupuestoInicialIngresos'])->name('cargarPresupuestoInicialIngresos')->middleware('role:Administrador');
Route::get('/presupuesto/plantilla-presupuesto-inicial',[PresupuestoController::class, 'plantillaPresupuestoInicial'])->name('plantillaPresupuestoInicial')->middleware('role:Administrador');

//Presupuesto egresos
Route::get('/presupuesto/consulta-presupuesto-egresos',[PresupuestoController::class, 'consultaPresupuestoEgresos'])->name('consultaPresupuestoEgresos')->middleware('role:Administrador');
Route::get('/presupuesto/presupuesto-inicial-egresos',[PresupuestoController::class, 'presupuestoInicialEgresos'])->name('presupuestoInicialEgresos')->middleware('role:Administrador');
Route::post('/presupuesto/cargar-presupuesto-inicial-egresos',[PresupuestoController::class, 'cargarPresupuestoInicialEgresos'])->name('cargarPresupuestoInicialEgresos')->middleware('role:Administrador');
Route::get('/presupuesto/plantilla-presupuesto-inicial-egresos',[PresupuestoController::class, 'plantillaPresupuestoInicial'])->name('plantillaPresupuestoInicialEgresos')->middleware('role:Administrador');

//Afectaciones líquidas
Route::get('/presupuesto/consulta-ampliaciones-reducciones',[PresupuestoController::class, 'consultaAmpliacionesReducciones'])->name('consultaAmpliacionesReducciones')->middleware('role:Administrador');
Route::get('/presupuesto/ingresos/ampliacion',[PresupuestoController::class, 'ampliacionIngresos'])->name('ampliacionIngresos')->middleware('role:Administrador');
Route::get('/presupuesto/egresos/ampliacion',[PresupuestoController::class, 'ampliacionEgresos'])->name('ampliacionEgresos')->middleware('role:Administrador');
Route::get('/presupuesto/ingresos/reduccion',[PresupuestoController::class, 'reduccionIngresos'])->name('reduccionIngresos')->middleware('role:Administrador');
Route::get('/presupuesto/egresos/reduccion',[PresupuestoController::class, 'reduccionEgresos'])->name('reduccionEgresos')->middleware('role:Administrador');
Route::get('/presupuesto/verDetalleAfectacion/{evento}',[PresupuestoController::class, 'verDetalleAfectacion'])->name('verDetalleAfectacion')->middleware('role:Administrador');

// Recandelarización y Reclasificación
Route::get('/presupuesto/recalendarizacion', [PresupuestoController::class, 'recalendarizacion'])->name('recalendarizacion')->middleware('role:Administrador');

//Bitacora
Route::get('/bitacoras', [BitacoraController::class, 'listarBitacoras'])->name('listarBitacoras')->middleware('role:Tecnico');

//Balanza Armonizada
Route::get('/balanza', [ReportesController::class, 'balanza'])->name('balanzaArmonizada')->middleware('role:Administrador');

//Libro Mayor
Route::get('/mayor', [ReportesController::class, 'mayor'])->name('libroMayor')->middleware('role:Administrador');

//Libro Diario
Route::get('/diario', [ReportesController::class, 'diario'])->name('libroDiario')->middleware('role:Administrador');

//Carga contabilidad
Route::get('/contabilidad/poliza-inicial', [ContabilidadController::class, 'polizaInicial'])->name('polizaInicial')->middleware('role:Administrador');
Route::get('/contabilidad/plantilla-poliza-inicial', [ContabilidadController::class, 'plantillaPolizaInicial'])->name('plantillaActivos')->middleware('role:Administrador');
Route::get('/contabilidad/consulta-poliza-inicial', [ContabilidadController::class, 'consultaPolizaInicial'])->name('consultaPolizaInicial')->middleware('role:Administrador');
Route::post('/contabilidad/cargar-poliza-inicial', [ContabilidadController::class, 'cargarPolizaInicial'])->name('cargarPolizaInicial')->middleware('role:Administrador');

//Tipos de presupuesto
Route::get("/tiposPresupuesto", [PresupuestoController::class, 'tiposPresupuesto'])->name('tiposPresupuesto')->middleware('role:Administrador');

Route::get("/movimientos", [PresupuestoController::class, 'movimientos'])->name('movimientos')->middleware('role:Administrador');

//Ingresos
Route::get("/ingresos-por-clasificar", [IngresosController::class, 'ingresosPorClasificar'])->name('ingresosPorClasificar')->middleware('role:Administrador');
Route::get("/depositos-bancos", [IngresosController::class, 'depositosBancos'])->name('depositosBancos')->middleware('role:Administrador');
Route::get("/devengado-prev-recaudado", [IngresosController::class, 'devengadoRecaudado'])->name('devengadoRecaudado')->middleware('role:Administrador');
Route::get("/ingresos-devengado", [IngresosController::class, 'ingresosDevengado'])->name('ingresosDevengado')->middleware('role:Administrador');
Route::get("/ingresos-recaudado", [IngresosController::class, 'ingresosRecaudado'])->name('ingresosRecaudado')->middleware('role:Administrador');
Route::get("/autorizacion-devolucion", [IngresosController::class, 'autorizacionDevolucion'])->name('autorizacionDevolucion')->middleware('role:Administrador');
Route::get("/pago-devolucion", [IngresosController::class, 'pagoDevolucion'])->name('pagoDevolucion')->middleware('role:Administrador');
Route::get("/autorizacion-reintegro", [IngresosController::class, 'autorizacionReintegro'])->name('autorizacionReintegro')->middleware('role:Administrador');
Route::get("/pago-reintegro", [IngresosController::class, 'pagoReintegro'])->name('pagoReintegro')->middleware('role:Administrador');
Route::get("/cobro-especie", [IngresosController::class, 'cobroEspecie'])->name('cobroEspecie')->middleware('role:Administrador');
Route::get("/devolucion-especie", [IngresosController::class, 'devolucionEspecie'])->name('devolucionEspecie')->middleware('role:Administrador');

//Egresos
Route::get("/capitulo2y3-comprometido", [EgresosController::class, 'capitulo2y3Comprometido'])->name('capitulo2y3Comprometido')->middleware('role:Administrador');
Route::get("/capitulo2y3-ejercido", [EgresosController::class, 'capitulo2y3Ejercido'])->name('capitulo2y3Ejercido')->middleware('role:Administrador');
Route::get("/capitulo4-comprometido", [EgresosController::class, 'capitulo4Comprometido'])->name('capitulo4Comprometido')->middleware('role:Administrador');
Route::get("/capitulo4-devengado", [EgresosController::class, 'capitulo4Devengado'])->name('capitulo4Devengado')->middleware('role:Administrador');
Route::get("/capitulo4-ejercido", [EgresosController::class, 'capitulo4Ejercido'])->name('capitulo4Ejercido')->middleware('role:Administrador');
Route::get("/capitulo4-pagado", [EgresosController::class, 'capitulo4Pagado'])->name('capitulo4Pagado')->middleware('role:Administrador');
Route::get("/capitulo5-comprometido", [EgresosController::class, 'capitulo5Comprometido'])->name('capitulo5Comprometido')->middleware('role:Administrador');
Route::get("/capitulo5-devengado", [EgresosController::class, 'capitulo5Devengado'])->name('capitulo5Devengado')->middleware('role:Administrador');
Route::get("/capitulo5-ejercido", [EgresosController::class, 'capitulo5Ejercido'])->name('capitulo5Ejercido')->middleware('role:Administrador');
Route::get("/capitulo5-pagado", [EgresosController::class, 'capitulo5Pagado'])->name('capitulo5Pagado')->middleware('role:Administrador');

//ruta de prueba
Route::get("/bancos", [IngresosController::class, 'bancos'])->name('bancos')->middleware('role:Administrador');






