<?php

use App\Http\Controllers\CuentasController;
use App\Http\Controllers\GuiaContabilizadoraController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\PrestamosController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\ContabilidadController;
use App\Http\Controllers\IngresosController;
use App\Http\Controllers\EgresosController;
use App\Http\Controllers\DeudoresController;
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
Route::get('/cuentas', [CuentasController::class, 'listaDeCuentas'])->name('listaDeCuentas')->middleware('can:acceso-cuentas');
Route::get('/cuentas/editar/{id}', [CuentasController::class, 'editarCuenta'])->name('editarCuenta')->middleware('can:acceso-cuentas');
Route::post('/cuentas/cambiosCuenta', [CuentasController::class, 'cambiosCuenta'])->name('cambiosCuenta')->middleware('can:acceso-cuentas');
Route::get('/cuentas/mostrarRegistrarCuenta', [CuentasController::class, 'mostrarRegistrarCuenta'])->middleware('can:acceso-cuentas');
Route::get('/cuentas/llenarSiguienteNivel', [CuentasController::class, 'llenarSiguienteNivel'])->middleware('can:acceso-cuentas');
Route::post('/cuentas/agregarCuenta', [CuentasController::class, 'agregarCuenta'])->middleware('can:acceso-cuentas');
Route::get('/cuentas/cargaExcel', [CuentasController::class, 'cargaExcel'])->name('cargaExcel')->middleware('can:acceso-cuentas');
Route::post('/importarExcel', [CuentasController::class, 'importarExcel'])->name('importarExcel')->middleware('can:acceso-cuentas');
Route::get('/plantillaExcel/{archivo}', [CuentasController::class, 'plantillaExcel'])->name('plantillaExcel')->middleware('can:acceso-cuentas');
Route::get('/limpiar-plan-cuentas', [CuentasController::class, 'limpiarCuentas'])->name('limpiarCuentas')->middleware('can:acceso-cuentas');

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
Route::get('/presupuesto/consulta-presupuesto-ingresos',[PresupuestoController::class, 'consultaPresupuestoIngresos'])->name('consultaPresupuestoIngresos')->middleware('can:acceso-presupuesto');
Route::get('/presupuesto/presupuesto-inicial-ingresos',[PresupuestoController::class, 'presupuestoInicialIngresos'])->name('presupuestoInicialIngresos')->middleware('can:acceso-presupuesto');
Route::post('/presupuesto/cargar-presupuesto-inicial-ingresos',[PresupuestoController::class, 'cargarPresupuestoInicialIngresos'])->name('cargarPresupuestoInicialIngresos')->middleware('can:acceso-presupuesto');
Route::get('/presupuesto/plantilla-presupuesto-inicial',[PresupuestoController::class, 'plantillaPresupuestoInicial'])->name('plantillaPresupuestoInicial')->middleware('can:acceso-presupuesto');

//Presupuesto egresos
Route::get('/presupuesto/consulta-presupuesto-egresos',[PresupuestoController::class, 'consultaPresupuestoEgresos'])->name('consultaPresupuestoEgresos')->middleware('can:acceso-presupuesto');
Route::get('/presupuesto/presupuesto-inicial-egresos',[PresupuestoController::class, 'presupuestoInicialEgresos'])->name('presupuestoInicialEgresos')->middleware('can:acceso-presupuesto');
Route::post('/presupuesto/cargar-presupuesto-inicial-egresos',[PresupuestoController::class, 'cargarPresupuestoInicialEgresos'])->name('cargarPresupuestoInicialEgresos')->middleware('can:acceso-presupuesto');
Route::get('/presupuesto/plantilla-presupuesto-inicial-egresos',[PresupuestoController::class, 'plantillaPresupuestoInicial'])->name('plantillaPresupuestoInicialEgresos')->middleware('can:acceso-presupuesto');

//Afectaciones líquidas
Route::get('/presupuesto/consulta-ampliaciones-reducciones',[PresupuestoController::class, 'consultaAmpliacionesReducciones'])->name('consultaAmpliacionesReducciones')->middleware('can:acceso-presupuesto');
Route::get('/presupuesto/ingresos/ampliacion',[PresupuestoController::class, 'ampliacionIngresos'])->name('ampliacionIngresos')->middleware('can:acceso-presupuesto');
Route::get('/presupuesto/egresos/ampliacion',[PresupuestoController::class, 'ampliacionEgresos'])->name('ampliacionEgresos')->middleware('can:acceso-presupuesto');
Route::get('/presupuesto/ingresos/reduccion',[PresupuestoController::class, 'reduccionIngresos'])->name('reduccionIngresos')->middleware('can:acceso-presupuesto');
Route::get('/presupuesto/egresos/reduccion',[PresupuestoController::class, 'reduccionEgresos'])->name('reduccionEgresos')->middleware('can:acceso-presupuesto');
Route::get('/presupuesto/verDetalleAfectacion/{evento}',[PresupuestoController::class, 'verDetalleAfectacion'])->name('verDetalleAfectacion')->middleware('can:acceso-presupuesto');
Route::get('/presupuesto/consulta-transferencias', [PresupuestoController::class, 'consultarTransferencias'])->name('consultaTransferencias')->middleware('role:Administrador');

// Recandelarización y Reclasificación
Route::get('/presupuesto/recalendarizacion', [PresupuestoController::class, 'recalendarizacion'])->name('recalendarizacion')->middleware('can:acceso-presupuesto');

//Bitacora
Route::get('/bitacoras', [BitacoraController::class, 'listarBitacoras'])->name('listarBitacoras')->middleware('role:Tecnico');

//Balanza Armonizada
Route::get('/balanza', [ReportesController::class, 'balanza'])->name('balanzaArmonizada')->middleware('can:acceso-contabilidad-reportes');

//Libro Mayor
Route::get('/mayor', [ReportesController::class, 'mayor'])->name('libroMayor')->middleware('can:acceso-contabilidad-reportes');

//Libro Diario
Route::get('/diario', [ReportesController::class, 'diario'])->name('libroDiario')->middleware('can:acceso-contabilidad-reportes');

//Estado de cuenta
Route::get('/estado-cuenta', [ReportesController::class, 'estadoCuenta'])->name('estadoCuenta')->middleware('can:acceso-contabilidad-reportes');

//Carga contabilidad
Route::get('/contabilidad/poliza-inicial', [ContabilidadController::class, 'polizaInicial'])->name('polizaInicial')->middleware('can:acceso-contabilidad-consultar-carga');
Route::get('/contabilidad/plantilla-poliza-inicial', [ContabilidadController::class, 'plantillaPolizaInicial'])->name('plantillaActivos')->middleware('can:acceso-contabilidad-consultar-carga');
Route::get('/contabilidad/consulta-poliza-inicial', [ContabilidadController::class, 'consultaPolizaInicial'])->name('consultaPolizaInicial')->middleware('can:acceso-contabilidad-consultar-carga');
Route::post('/contabilidad/cargar-poliza-inicial', [ContabilidadController::class, 'cargarPolizaInicial'])->name('cargarPolizaInicial')->middleware('can:acceso-contabilidad-consultar-carga');
Route::get('/contabilidad/registro-poliza-diario', [ContabilidadController::class, 'registroPolizaDiario'])->name('registroPolizaDiario')->middleware('can:acceso-contabilidad-consultar-carga');
Route::get("/movimientos-diario", [ContabilidadController::class, 'movimientosDiario'])->name('movimientosDiario')->middleware('can:acceso-contabilidad-consultar-carga');
Route::get("/movimientos-deudores", [ContabilidadController::class, 'movimientosDeudores'])->name('movimientosDeudores')->middleware('can:acceso-contabilidad-consultar-carga');

//Tipos de presupuesto
Route::get("/tiposPresupuesto", [PresupuestoController::class, 'tiposPresupuesto'])->name('tiposPresupuesto')->middleware('can:acceso-presupuesto');

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
Route::get("/movimientos-ingresos", [IngresosController::class, 'consultarMovimientos'])->name('movimientosIngresos')->middleware('role:Administrador');


//Egresos
Route::get("/capitulo1-comprometido", [EgresosController::class, 'capitulo1Comprometido'])->name('capitulo1Comprometido')->middleware('role:Administrador');
Route::get("/capitulo1-devengado", [EgresosController::class, 'capitulo1Devengado'])->name('capitulo1Devengado')->middleware('role:Administrador');
Route::get("/capitulo1-devengadoCarga", [EgresosController::class, 'capitulo1DevengadoCarga'])->name('capitulo1DevengadoCarga')->middleware('role:Administrador');
Route::get("/capitulo1-ejercido", [EgresosController::class, 'capitulo1Ejercido'])->name('capitulo1Ejercido')->middleware('role:Administrador');
Route::get("/capitulo1-pagado", [EgresosController::class, 'capitulo1Pagado'])->name('capitulo1Pagado')->middleware('role:Administrador');
Route::get("/capitulo1-cancelaciones", [EgresosController::class, 'capitulo1Cancelaciones'])->name('capitulo1Cancelaciones')->middleware('role:Administrador');

Route::get("/capitulo2y3-comprometido", [EgresosController::class, 'capitulo2y3Comprometido'])->name('capitulo2y3Comprometido')->middleware('role:Administrador');
Route::get("/capitulo2y3-devengado", [EgresosController::class, 'capitulo2y3Devengado'])->name('capitulo2y3Devengado')->middleware('role:Administrador');
Route::get("/capitulo2y3-ejercido", [EgresosController::class, 'capitulo2y3Ejercido'])->name('capitulo2y3Ejercido')->middleware('role:Administrador');
Route::get("/capitulo2y3-pagado", [EgresosController::class, 'capitulo2y3Pagado'])->name('capitulo2y3Pagado')->middleware('role:Administrador');
Route::get("/capitulo4-comprometido", [EgresosController::class, 'capitulo4Comprometido'])->name('capitulo4Comprometido')->middleware('role:Administrador');
Route::get("/capitulo4-devengado", [EgresosController::class, 'capitulo4Devengado'])->name('capitulo4Devengado')->middleware('role:Administrador');
Route::get("/capitulo4-ejercido", [EgresosController::class, 'capitulo4Ejercido'])->name('capitulo4Ejercido')->middleware('role:Administrador');
Route::get("/capitulo4-pagado", [EgresosController::class, 'capitulo4Pagado'])->name('capitulo4Pagado')->middleware('role:Administrador');
Route::get("/capitulo5-comprometido", [EgresosController::class, 'capitulo5Comprometido'])->name('capitulo5Comprometido')->middleware('role:Administrador');
Route::get("/capitulo5-devengado", [EgresosController::class, 'capitulo5Devengado'])->name('capitulo5Devengado')->middleware('role:Administrador');
Route::get("/capitulo5-ejercido", [EgresosController::class, 'capitulo5Ejercido'])->name('capitulo5Ejercido')->middleware('role:Administrador');
Route::get("/capitulo5-pagado", [EgresosController::class, 'capitulo5Pagado'])->name('capitulo5Pagado')->middleware('role:Administrador');
Route::get("/movimientos-egresos", [EgresosController::class, 'consultarMovimientos'])->name('movimientosEgresos')->middleware('role:Administrador');
Route::get("capitulo1/plantillaCompromiso1000", [EgresosController::class, 'plantillaCargaComprometidoCapitulo1000'])->name('plantillaCompromiso1000')->middleware('role:Administrador');
Route::get("/capitulo1/plantillaDevengado1000", [EgresosController::class, 'plantillaCargaDevengado1000'])->name('plantillaCompromiso1000')->middleware('role:Administrador');


//Prestamos
Route::get("/capitulo7-otorgamiento-compromiso-devengado-prestamosIniciales", [PrestamosController::class, 'capitulo7CompromisoDevengadoPrestamosIniciales'])->name('capitulo7CompromisoDevengadoPrestamosIniciales')->middleware('role:Administrador');
Route::get("/capitulo7-otorgamiento-compromiso-devengado-prestamosRenovacion", [PrestamosController::class, 'capitulo7CompromisoDevengadoPrestamosRenovacion'])->name('capitulo7CompromisoDevengadoPrestamosRenovacion')->middleware('role:Administrador');

Route::get("/capitulo7-otorgamiento-ejercido-pagado-recaudado-prestamosIniciales", [PrestamosController::class, 'capitulo7EjercidoPagadoRecaudadoPrestamosIniciales'])->name('capitulo7EjercidoPagadoRecaudadoPrestamosIniciales')->middleware('role:Administrador');
Route::get("/capitulo7-otorgamiento-ejercido-pagado-recaudado-prestamosRenovacion", [PrestamosController::class, 'capitulo7EjercidoPagadoRecaudadoPrestamosRenovacion'])->name('capitulo7EjercidoPagadoRecaudadoPrestamosRenovacion')->middleware('role:Administrador');

Route::get("/capitulo7-recuperacion-recaudado-prestamosIniciales", [PrestamosController::class, 'capitulo7RecaudadoPrestamosIniciales'])->name('capitulo7RecaudadoPrestamosIniciales')->middleware('role:Administrador');
Route::get("/capitulo7-recuperacion-recaudado-prestamosRenovacion", [PrestamosController::class, 'capitulo7RecaudadoPrestamosRenovacion'])->name('capitulo7RecaudadoPrestamosRenovacion')->middleware('role:Administrador');
Route::get("/movimientos-prestamos", [PrestamosController::class, 'consultarMovimientos'])->name('movimientosPrestamos')->middleware('role:Administrador');

//Deudores
Route::get("/deudores-otorgamiento-anticipo", [DeudoresController::class, 'otorgamientoAnticipo'])->name('otorgamientoAnticipo')->middleware('role:Administrador');
Route::get("/deudores-reintegro-anticipo", [DeudoresController::class, 'reintegroAnticipo'])->name('reintegroAnticipo')->middleware('role:Administrador');
Route::get("/deudores-comprobacion-anticipo", [DeudoresController::class, 'comprobacionAnticipo'])->name('comprobacionAnticipo')->middleware('role:Administrador');

//ruta de prueba
Route::get("/bancos", [IngresosController::class, 'bancos'])->name('bancos')->middleware('role:Administrador');






