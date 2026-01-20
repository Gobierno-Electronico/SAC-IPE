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
use Illuminate\Http\Request;


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

Route::post('/set-anio-fiscal', function (Request $request) {
    session(['anioSeleccionado' => (int) $request->anio]);
    return response()->json(['ok' => true]);
});


Route::get('/fuente/cargar', [ReportesController::class, 'mostrarVistaCargafuente'])->name('cargarfuente');
//Catálogos
Route::get('/catalogos/{tipo}', [ReportesController::class, 'mostrarClasificadores'])->name('mostrarClasificadores');
Route::get('/matriz/cargar', [ReportesController::class, 'mostrarVistaCargaMatriz'])->name('cargarMatriz');
Route::get('/matriz/consultar', [ReportesController::class, 'mostrarVistaConsultaMatriz'])->name('consultarMatriz');
//Cuentas
Route::get('/cuentas', [CuentasController::class, 'listaDeCuentas'])->name('listaDeCuentas');
Route::get('/cuentas/editar/{id}', [CuentasController::class, 'editarCuenta'])->name('editarCuenta');
Route::post('/cuentas/cambiosCuenta', [CuentasController::class, 'cambiosCuenta'])->name('cambiosCuenta');
Route::get('/cuentas/mostrarRegistrarCuenta', [CuentasController::class, 'mostrarRegistrarCuenta']);
Route::get('/cuentas/llenarSiguienteNivel', [CuentasController::class, 'llenarSiguienteNivel']);
Route::post('/cuentas/agregarCuenta', [CuentasController::class, 'agregarCuenta']);
Route::get('/cuentas/cargaExcel', [CuentasController::class, 'cargaExcel'])->name('cargaExcel');
Route::post('/importarExcel', [CuentasController::class, 'importarExcel'])->name('importarExcel');
Route::get('/plantillaExcel/{archivo}', [CuentasController::class, 'plantillaExcel'])->name('plantillaExcel');
Route::get('/limpiar-plan-cuentas', [CuentasController::class, 'limpiarCuentas'])->name('limpiarCuentas');

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
Route::get('/presupuesto/consulta-presupuesto-ingresos',[PresupuestoController::class, 'consultaPresupuestoIngresos'])->name('consultaPresupuestoIngresos');
Route::get('/presupuesto/presupuesto-inicial-ingresos',[PresupuestoController::class, 'presupuestoInicialIngresos'])->name('presupuestoInicialIngresos');
Route::post('/presupuesto/cargar-presupuesto-inicial-ingresos',[PresupuestoController::class, 'cargarPresupuestoInicialIngresos'])->name('cargarPresupuestoInicialIngresos');
Route::get('/presupuesto/plantilla-presupuesto-inicial',[PresupuestoController::class, 'plantillaPresupuestoInicial'])->name('plantillaPresupuestoInicial');

//Presupuesto egresos
Route::get('/presupuesto/consulta-presupuesto-egresos',[PresupuestoController::class, 'consultaPresupuestoEgresos'])->name('consultaPresupuestoEgresos');
Route::get('/presupuesto/presupuesto-inicial-egresos',[PresupuestoController::class, 'presupuestoInicialEgresos'])->name('presupuestoInicialEgresos');
Route::post('/presupuesto/cargar-presupuesto-inicial-egresos',[PresupuestoController::class, 'cargarPresupuestoInicialEgresos'])->name('cargarPresupuestoInicialEgresos');
Route::get('/presupuesto/plantilla-presupuesto-inicial-egresos',[PresupuestoController::class, 'plantillaPresupuestoInicial'])->name('plantillaPresupuestoInicialEgresos');

//Afectaciones líquidas
Route::get('/presupuesto/consulta-ampliaciones-reducciones',[PresupuestoController::class, 'consultaAmpliacionesReducciones'])->name('consultaAmpliacionesReducciones');
Route::get('/presupuesto/ingresos/ampliacion',[PresupuestoController::class, 'ampliacionIngresos'])->name('ampliacionIngresos');
Route::get('/presupuesto/egresos/ampliacion',[PresupuestoController::class, 'ampliacionEgresos'])->name('ampliacionEgresos');
Route::get('/presupuesto/ingresos/reduccion',[PresupuestoController::class, 'reduccionIngresos'])->name('reduccionIngresos');
Route::get('/presupuesto/egresos/reduccion',[PresupuestoController::class, 'reduccionEgresos'])->name('reduccionEgresos');
Route::get('/presupuesto/verDetalleAfectacion/{evento}',[PresupuestoController::class, 'verDetalleAfectacion'])->name('verDetalleAfectacion');
Route::get('/presupuesto/consulta-transferencias', [PresupuestoController::class, 'consultarTransferencias'])->name('consultaTransferencias');

// Recandelarización y Reclasificación
Route::get('/presupuesto/recalendarizacion', [PresupuestoController::class, 'recalendarizacion'])->name('recalendarizacion');

//Bitacora
Route::get('/bitacoras', [BitacoraController::class, 'listarBitacoras'])->name('listarBitacoras')->middleware('role:Administrador');

//Balanza Armonizada
Route::get('/balanza', [ReportesController::class, 'balanza'])->name('balanzaArmonizada');

//Libro Mayor
Route::get('/mayor', [ReportesController::class, 'mayor'])->name('libroMayor');

//Libro Diario
Route::get('/diario', [ReportesController::class, 'diario'])->name('libroDiario');

//Estado de cuenta
Route::get('/estado-cuenta', [ReportesController::class, 'estadoCuenta'])->name('estadoCuenta');

//Estados financieros
Route::get('/estado-actividades', [ReportesController::class, 'estadoActividades'])->name('estadoActividades');
Route::get('/estado-situacion-financiera', [ReportesController::class, 'estadoSituacionFinanciera'])->name('estadoSituacionFinanciera');
Route::get('/estado-cambios-situacion-financiera', [ReportesController::class, 'estadoCambiosSituacionFinanciera'])->name('estadoCambiosSituacionFinanciera');
Route::get('/estado-analitico-del-activo', [ReportesController::class, 'estadoAnaliticoDelActivo'])->name('estadoAnaliticoDelActivo');

//Carga contabilidad
Route::get('/contabilidad/poliza-inicial', [ContabilidadController::class, 'polizaInicial'])->name('polizaInicial');
Route::get('/contabilidad/plantilla-poliza-inicial', [ContabilidadController::class, 'plantillaPolizaInicial'])->name('plantillaActivos');
Route::get('/contabilidad/consulta-poliza-inicial', [ContabilidadController::class, 'consultaPolizaInicial'])->name('consultaPolizaInicial');
Route::post('/contabilidad/cargar-poliza-inicial', [ContabilidadController::class, 'cargarPolizaInicial'])->name('cargarPolizaInicial');
Route::get('/contabilidad/registro-poliza-diario', [ContabilidadController::class, 'registroPolizaDiario'])->name('registroPolizaDiario');
Route::get("/movimientos-diario", [ContabilidadController::class, 'movimientosDiario'])->name('movimientosDiario');
Route::get("/movimientos-deudores", [ContabilidadController::class, 'movimientosDeudores'])->name('movimientosDeudores');
Route::get('/contabilidad/auxiliares', [ContabilidadController::class, 'auxiliares'])->name('auxiliares');
Route::post('/contabilidad/cargar-auxiliares', [ContabilidadController::class, 'registrarAuxiliares'])->name('registrarAuxiliares');
Route::get('/contabilidad/plantilla-auxiliares', [ContabilidadController::class, 'plantillaAuxiliares'])->name('plantillaAuxiliares');

//Tipos de presupuesto
Route::get("/tiposPresupuesto", [PresupuestoController::class, 'tiposPresupuesto'])->name('tiposPresupuesto');

Route::get("/movimientos", [PresupuestoController::class, 'movimientos'])->name('movimientos')->middleware('role:Administrador');

//Ingresos
Route::get("/ingresos-por-clasificar", [IngresosController::class, 'ingresosPorClasificar'])->name('ingresosPorClasificar');
Route::get("/depositos-bancos", [IngresosController::class, 'depositosBancos'])->name('depositosBancos');
Route::get("/devengado-prev-recaudado", [IngresosController::class, 'devengadoRecaudado'])->name('devengadoRecaudado');
Route::get("/devengado-prev-recaudado-ejercicios-anteriores", [IngresosController::class, 'devengadoRecaudadoEjerciciosAnteriores'])->name('devengadoRecaudadoEjerciciosAnteriores');
Route::get("/ingresos-devengado", [IngresosController::class, 'ingresosDevengado'])->name('ingresosDevengado');
Route::get("/ingresos-recaudado", [IngresosController::class, 'ingresosRecaudado'])->name('ingresosRecaudado');
Route::get("/autorizacion-devolucion", [IngresosController::class, 'autorizacionDevolucion'])->name('autorizacionDevolucion');
Route::get("/pago-devolucion", [IngresosController::class, 'pagoDevolucion'])->name('pagoDevolucion');
Route::get("/autorizacion-reintegro", [IngresosController::class, 'autorizacionReintegro'])->name('autorizacionReintegro');
Route::get("/pago-reintegro", [IngresosController::class, 'pagoReintegro'])->name('pagoReintegro');
Route::get("/cobro-especie", [IngresosController::class, 'cobroEspecie'])->name('cobroEspecie');
Route::get("/devolucion-especie", [IngresosController::class, 'devolucionEspecie'])->name('devolucionEspecie');
Route::get("/movimientos-ingresos", [IngresosController::class, 'consultarMovimientos'])->name('movimientosIngresos');


//Egresos
Route::get("/capitulo1-comprometido", [EgresosController::class, 'capitulo1Comprometido'])->name('capitulo1Comprometido');
Route::get("/capitulo1-devengado", [EgresosController::class, 'capitulo1Devengado'])->name('capitulo1Devengado');
Route::get("/capitulo1-devengadoCarga", [EgresosController::class, 'capitulo1DevengadoCarga'])->name('capitulo1DevengadoCarga');
Route::get("/capitulo1-ejercido", [EgresosController::class, 'capitulo1Ejercido'])->name('capitulo1Ejercido');
Route::get("/capitulo1-pagado", [EgresosController::class, 'capitulo1Pagado'])->name('capitulo1Pagado');
Route::get("/capitulo1-cancelaciones", [EgresosController::class, 'capitulo1Cancelaciones'])->name('capitulo1Cancelaciones');

Route::get("/capitulo2y3-comprometido", [EgresosController::class, 'capitulo2y3Comprometido'])->name('capitulo2y3Comprometido');
Route::get("/capitulo2y3-devengado", [EgresosController::class, 'capitulo2y3Devengado'])->name('capitulo2y3Devengado');
Route::get("/capitulo2y3-ejercido", [EgresosController::class, 'capitulo2y3Ejercido'])->name('capitulo2y3Ejercido');
Route::get("/capitulo2y3-pagado", [EgresosController::class, 'capitulo2y3Pagado'])->name('capitulo2y3Pagado');
Route::get("/capitulo4-comprometido", [EgresosController::class, 'capitulo4Comprometido'])->name('capitulo4Comprometido');
Route::get("/capitulo4-devengado", [EgresosController::class, 'capitulo4Devengado'])->name('capitulo4Devengado');
Route::get("/capitulo4-ejercido", [EgresosController::class, 'capitulo4Ejercido'])->name('capitulo4Ejercido');
Route::get("/capitulo4-pagado", [EgresosController::class, 'capitulo4Pagado'])->name('capitulo4Pagado');
Route::get("/capitulo5-comprometido", [EgresosController::class, 'capitulo5Comprometido'])->name('capitulo5Comprometido');
Route::get("/capitulo5-devengado", [EgresosController::class, 'capitulo5Devengado'])->name('capitulo5Devengado');
Route::get("/capitulo5-ejercido", [EgresosController::class, 'capitulo5Ejercido'])->name('capitulo5Ejercido');
Route::get("/capitulo5-pagado", [EgresosController::class, 'capitulo5Pagado'])->name('capitulo5Pagado');
Route::get("/movimientos-egresos", [EgresosController::class, 'consultarMovimientos'])->name('movimientosEgresos');
Route::get("capitulo1/plantillaCompromiso1000", [EgresosController::class, 'plantillaCargaComprometidoCapitulo1000'])->name('plantillaCompromiso1000');
Route::get("/capitulo1/plantillaDevengado1000", [EgresosController::class, 'plantillaCargaDevengado1000'])->name('plantillaDevengado1000');


//Prestamos
Route::get("/capitulo7-otorgamiento-compromiso-devengado-prestamosIniciales", [PrestamosController::class, 'capitulo7CompromisoDevengadoPrestamosIniciales'])->name('capitulo7CompromisoDevengadoPrestamosIniciales');
Route::get("/capitulo7-otorgamiento-compromiso-devengado-prestamosRenovacion", [PrestamosController::class, 'capitulo7CompromisoDevengadoPrestamosRenovacion'])->name('capitulo7CompromisoDevengadoPrestamosRenovacion');

Route::get("/capitulo7-otorgamiento-ejercido-pagado-recaudado-prestamosIniciales", [PrestamosController::class, 'capitulo7EjercidoPagadoRecaudadoPrestamosIniciales'])->name('capitulo7EjercidoPagadoRecaudadoPrestamosIniciales');
Route::get("/capitulo7-otorgamiento-ejercido-pagado-recaudado-prestamosRenovacion", [PrestamosController::class, 'capitulo7EjercidoPagadoRecaudadoPrestamosRenovacion'])->name('capitulo7EjercidoPagadoRecaudadoPrestamosRenovacion');

Route::get("/capitulo7-recuperacion-recaudado-prestamosIniciales", [PrestamosController::class, 'capitulo7RecaudadoPrestamosIniciales'])->name('capitulo7RecaudadoPrestamosIniciales');
Route::get("/capitulo7-recuperacion-recaudado-prestamosRenovacion", [PrestamosController::class, 'capitulo7RecaudadoPrestamosRenovacion'])->name('capitulo7RecaudadoPrestamosRenovacion');
Route::get("/movimientos-prestamos", [PrestamosController::class, 'consultarMovimientos'])->name('movimientosPrestamos');

Route::get("/capitulo7-recuperacion-ejercicios-anteriores", [PrestamosController::class, 'capitulo7RecuperacionEjerciciosAnteriores'])->name('capitulo7RecuperacionEjerciciosAnteriores');

//Deudores
Route::get("/deudores-otorgamiento-anticipo", [DeudoresController::class, 'otorgamientoAnticipo'])->name('otorgamientoAnticipo');
Route::get("/deudores-reintegro-anticipo", [DeudoresController::class, 'reintegroAnticipo'])->name('reintegroAnticipo');
Route::get("/deudores-comprobacion-anticipo", [DeudoresController::class, 'comprobacionAnticipo'])->name('comprobacionAnticipo');

//ruta de prueba
Route::get("/bancos", [IngresosController::class, 'bancos'])->name('bancos');






