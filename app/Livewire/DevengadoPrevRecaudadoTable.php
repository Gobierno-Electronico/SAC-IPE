<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use DB;
use Log;
use App\Clases\Column;
use App\Http\Controllers\BitacoraController;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use App\Models\Cuenta;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use App\Enums\EstatusEvento;

class DevengadoPrevRecaudadoTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $numeroPoliza;
    public $numeroEvento;
    public $numeroPolizaRemanente;
    public $totalRegistrosPorCuentaPago = 0;

    public function render()
    {
        return view('livewire.devengado-prev-recaudado-table');
    }

    public function query(): Builder
    {
        return Poliza::query();
    }

    public function data()
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = array_slice($this->cacheData, $this->perPage * ($currentPage - 1), $this->perPage);
        return new LengthAwarePaginator($currentItems, count($this->cacheData), $this->perPage, $currentPage);
    }

    public function columns(): array
    {
        return [
            Column::make('area', 'Area'),
            Column::make('partida', 'Partida'),
            Column::make('cuentaPago', 'Cuenta de pago'),
            Column::make('mes', 'Mes'),
            Column::make('movimiento', 'Movimiento'),
            Column::make('ejecutar', 'PPTO por ejecutar')->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    public function edit($id)
    {
        try {
            //code...
            $this->recalcularDisponibilidad($id);
            foreach ($this->dataCompleta as $key => $registro) {
                if ($registro['id'] == $id) {
                    $datosRegistro = [
                        'area' => $registro['areaResponsableId'],
                        'cuenta' => $registro['cuentaId'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe'],
                        'agregarIVA' => $registro['agregarIVA'],
                        'cuentaPago' => $registro['cuentaPagoId']
                    ];
                    unset($this->dataCompleta[$key]);
                    $this->dataCompleta = array_values($this->dataCompleta);
                    $this->dispatch('llenar-formulario', $datosRegistro);
                    break;
                }
            }

            foreach ($this->cacheData as $key => $registro) {
                if ($registro['id'] == $id) {
                    unset($this->cacheData[$key]);
                    $this->cacheData = array_values($this->cacheData);
                    break;
                }
            }

            // Recalculamos los totales solo después de eliminar el registro
            $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al editar en Devengado previamente recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function delete($id)
    {
        try {
            //code...
            $this->recalcularDisponibilidad($id);
            foreach ($this->cacheData as $key => $registro) {
                if ($registro['id'] == $id) {
                    unset($this->cacheData[$key]);
                    $this->cacheData = array_values($this->cacheData);
                    break;
                }
            }

            foreach ($this->dataCompleta as $key => $registro) {
                if ($registro['id'] == $id) {
                    unset($this->dataCompleta[$key]);
                    $this->dataCompleta = array_values($this->dataCompleta);
                    break;
                }
            }

            // Recalculamos los totales solo después de eliminar el registro
            $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al eliminar en Devengado previamente recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al eliminar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function recalcularDisponibilidad($id)
    {
        $datosSeleccionado = [];
        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                $datosSeleccionado = [
                    'codigoArea' => $registro['codigoAreaResponsable'],
                    'codigoCuenta' => $registro['codigoCuenta'],
                    'mes' => $registro['mes'],
                    'evento' => $registro['evento']
                ];
            }
        }

        $totalImportes = 0;
        foreach ($this->cacheData as $key => $movimiento) {
            if ($movimiento['id'] != $id && str_contains($movimiento['area'], $datosSeleccionado['codigoArea']) && str_contains($movimiento['partida'], $datosSeleccionado['codigoCuenta']) && $movimiento['mes'] == $datosSeleccionado['mes'] && $movimiento['evento'] == $datosSeleccionado['evento']) {
                if ($totalImportes == 0) {
                    $movimiento['disponibilidad'] = bcsub($movimiento['ejecutar'], $movimiento['importe'], 2);
                    $totalImportes += $movimiento['importe'];
                } else {
                    $movimiento['disponibilidad'] = bcsub(bcsub($movimiento['ejecutar'], $totalImportes, 2), $movimiento['importe'], 2);
                    $totalImportes += $movimiento['importe'];
                }
                $this->cacheData[$key] = $movimiento;
            }
        }
    }

    public function changeState($value) {}

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try {
            if (bccomp((string)($this->total + $registro['importe']), (string)$registro['montoEvento'], 2) == 1) {
                $this->dispatch('mostrarMensaje', mensaje: 'Monto total del evento superado', tipo: 'error', tiempo: 3000);
                return;
            }

            $anioActual = Carbon::now()->year;
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $registro['cuentaId'])->where('concepto_id', '=', 14)->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Por ejecutar)%')->first();


            $solvencia = DB::select('EXEC SolvenciaCuentaArea @area = ?, @cuenta = ?, @anio = ?, @mes = ?', array($registro['codigoAreaResponsable'], $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $registro['mes']));
            $solvenciaCuentaPago = DB::select('EXEC SolvenciaIngresosPorClasificar @cuenta = ?, @cuentaPago = ?, @evento =?', array($registro['codigoCuenta'], $registro['codigoCuentaPago'], $registro['evento']));
            $this->sumarRegistrosPorCuentaPago($registro);
            if ($this->totalRegistrosPorCuentaPago + $registro['importe'] > $solvenciaCuentaPago[0]->Total) {
                $this->dispatch('mostrarMensaje', mensaje: 'Solvencia de la cuenta de pago insuficiente', tipo: 'error', tiempo: 3000);
                return;
            }

            $totalDisponible = $solvencia[0]->Solvencia - $registro['importe'];
            $totalImportes = 0;

            foreach ($this->cacheData as $movimiento) {
                if (str_contains($movimiento['area'], $registro['codigoAreaResponsable']) && str_contains($movimiento['partida'], $registro['codigoCuenta']) && $movimiento['mes'] == $registro['mes'] && $movimiento['evento'] == $registro['evento']) {
                    $totalImportes += $movimiento['importe'];
                }
            }

            if ($totalImportes > 0) {
                $totalDisponible = bcsub(bcsub($solvencia[0]->Solvencia, $totalImportes, 2), $registro['importe'], 2);
            }

            if ($totalDisponible < 0) {
                $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar insuficiente', tipo: 'error', tiempo: 3000);
                return;
            }

            $nuevoRegistro = [
                'id' => 0,
                'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                'cuentaPago' => $registro['codigoCuentaPago'] . ' ' . $registro['descripcionCuentaPago'],
                'mes' => $registro['mes'],
                'evento' => $registro['evento'],
                'movimiento' => 'DEVENGADO PREVIAMENTE RECAUDADO',
                'ejecutar' => $solvencia[0]->Solvencia,
                'importe' => $registro['importe'],
                'disponibilidad' => $totalDisponible
            ];
            array_push($this->cacheData, $nuevoRegistro);
            array_push($this->dataCompleta, $registro);
            $this->total = 0;
            foreach ($this->cacheData as $key => $registro) {
                $this->cacheData[$key]['id'] = $key + 1; // El ID comienza en 1
                $this->dataCompleta[$key]['id'] = $key + 1;
                $this->total += $registro['importe'];
            }
            $this->dispatch('cambioTotal', total: $this->total);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en Devengado previamente recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function sumarRegistrosPorCuentaPago($registro)
    {
        $this->totalRegistrosPorCuentaPago = 0;
        foreach ($this->dataCompleta as $key => $movimiento) {
            if ($registro['codigoCuentaPago'] == $movimiento['codigoCuentaPago']) {
                $this->totalRegistrosPorCuentaPago += $movimiento['importe'];
            }
        }
    }

    #[On('finalizar-registros')]
    public function finalizarRegistros()
    {
        if (empty($this->cacheData)) {
            $this->dispatch('mostrarMensaje', mensaje: 'Tabla sin registros', tipo: 'error', tiempo: 3000);
            return;
        }

        try {
            $idUsuarioRegistrante = Auth::id();
            $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) as numero_poliza')
                ->where('tipo_poliza', '=', 'I')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            sort($numerosPolizas);
            $this->numeroPoliza = (int)end($numerosPolizas) + 1;

            $this->numeroEvento = $this->dataCompleta[0]['evento'];
            $polizasInicialesIngresosPorClasificar = Poliza::where('tipo_poliza', '=', 'I')->where('categoria', '=', 'INGRESOS POR CLASIFICAR')
                ->where('evento', '=', $this->numeroEvento)->get();
            $anioActual = Carbon::now()->year;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);

            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar un devengado previamente recaudado con evento: ' . $this->numeroEvento, request());

            DB::beginTransaction();

            foreach ($this->dataCompleta as $movimiento) {
                $movimiento['importe'] = doubleval($movimiento['importe']);
                $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $movimiento['cuentaId'])->where('concepto_id', '=', 14)
                    ->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();
                $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                    ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                    ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();
                $importeMovimiento = $movimiento['importe'];

                $polizas = [
                    [
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $movimiento['codigoAreaResponsable'],
                        'tipo_poliza' => 'I',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $movimiento['fechaAfectacion'],
                        'cuenta' => $movimiento['codigoCuenta'],
                        'cuentaRelacionada' => $movimiento['codigoCuentaPago'],
                        'concepto' => $movimiento['descripcionCuenta'],
                        'total' => abs($importeMovimiento),
                        'mes' => $movimiento['mes'],
                        'descripcion' => $movimiento['observaciones'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $interaccionCuentaConceptoPrincipal->tipo_interaccion,
                        'validado' => false,
                        'estatus_evento' => EstatusEvento::ACTIVO->value,
                        'categoria' => 'INGRESOS DEVENGADO PREVIAMENTE RECAUDADO',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]
                ];
                foreach ($interaccionCuentaCuentas as $key => $dataCuenta) {
                    $importe = $movimiento['importe'];
                    if (str_contains($dataCuenta['Descripcion_cuenta'], 'IVA')) {
                        if ($movimiento['iva'] > 0) {
                            $importe = $movimiento['iva'];
                        } else {
                            //Saltamos la interacción con iva que no quieren que se le agregue el IVA, esto para no mostrarlo en la poliza
                            continue;
                        }
                    }
                    if ($dataCuenta['tipo_interaccion'] == 'Contable - Abono' &&  !str_contains($dataCuenta['Descripcion_cuenta'], 'IVA')) {
                        $importe = $importe - $movimiento['iva'];
                    }
                    array_push($polizas, [
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $movimiento['codigoAreaResponsable'],
                        'tipo_poliza' => 'I',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $movimiento['fechaAfectacion'],
                        'cuenta' => $dataCuenta['Codigo_cuenta'],
                        'cuentaRelacionada' => $movimiento['codigoCuentaPago'],
                        'concepto' => $dataCuenta['Descripcion_cuenta'],
                        'total' => $importe,
                        'mes' => $movimiento['mes'],
                        'descripcion' => $movimiento['observaciones'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $dataCuenta['tipo_interaccion'],
                        'validado' => false,
                        'estatus_evento' => EstatusEvento::ACTIVO->value,
                        'categoria' => 'INGRESOS DEVENGADO PREVIAMENTE RECAUDADO',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }
                Poliza::insert($polizas);
            }


            $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) as numero_poliza')
                ->where('tipo_poliza', '=', 'IAUX')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            sort($numerosPolizas);
            $this->numeroPolizaRemanente = (int)end($numerosPolizas) + 1;

            $polizaDevengadoPreviamenteRecaudado = Poliza::where('tipo_poliza', '=', 'I')
                ->where('categoria', '=', 'INGRESOS DEVENGADO PREVIAMENTE RECAUDADO')
                ->where('evento', '=', $this->numeroEvento)
                ->where('concepto', 'LIKE', '%(Devengado)%')
                ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->get();

            // Log::info($polizasInicialesIngresosPorClasificar);

            //se agrupan las polizas de ingresos por clasificar por si hay más de un registro con la misma cuenta, pero solo en las contable - abono, porque las contable cargo si las necesitamos por separado.
            $contablesCargoAgrupados = $polizasInicialesIngresosPorClasificar->filter(function ($item) {
                return $item->tipo_interaccion === 'Contable - Cargo';
            })->groupBy('cuenta')->map(function ($group) {
                $firstItem = $group->first()->toArray();
                return array_merge($firstItem, [
                    'total' => $group->sum('total'),
                ]);
            })->values();

            $polizasInicialesIngresosPorClasificar = $polizasInicialesIngresosPorClasificar->map(function ($item) use ($contablesCargoAgrupados) {
                if ($item->tipo_interaccion === 'Contable - Cargo') {
                    $agrupado = $contablesCargoAgrupados->firstWhere('cuenta', $item->cuenta);
                    if ($agrupado) {
                        return (object) array_merge($item->toArray(), $agrupado);
                    }
                }
                return $item;
            });

            // se agrupan las polizas devengado previamente recaudado por si hay más de un registro con la misma cuenta de pago
            $polizaDevengadoPreviamenteRecaudado = $polizaDevengadoPreviamenteRecaudado->groupBy('cuentaRelacionada')->map(function ($group) {
                $firstItem = $group->first()->toArray(); // Convertimos el primer elemento a un array
                return array_merge($firstItem, [
                    'total' => $group->sum('total'),
                ]);
            })->values();


            $totalRemanente = DB::select('EXEC ImporteTotalDevengadoPrevRecaudado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            if ($totalRemanente > 0) {

                $remanentes = [];
                $remanentes = $polizasInicialesIngresosPorClasificar->map(function ($ingreso) use ($polizaDevengadoPreviamenteRecaudado, $fecha) {
                    $devengado = $polizaDevengadoPreviamenteRecaudado->firstWhere('cuentaRelacionada', $ingreso->cuenta);

                    if ($devengado) {
                        return [
                            'area' => $devengado['area'],
                            'tipo_poliza' => 'IAUX',
                            'numero_poliza' =>  $this->numeroPolizaRemanente,
                            'fecha' => $devengado['fecha'],
                            'cuenta' => $ingreso->cuenta,
                            'concepto' => $ingreso->concepto,
                            'total' => $ingreso->total - $devengado['total'],
                            'mes' => $devengado['mes'],
                            'descripcion' => $devengado['descripcion'],
                            'evento' => $this->numeroEvento,
                            'tipo_interaccion' => $ingreso->tipo_interaccion,
                            'validado' => false,
                            'estatus_evento' => EstatusEvento::FINALIZADO->value,
                            'categoria' => 'INGRESOS POR CLASIFICAR REMANENTE DEVENGADO PREVIAMENTE RECAUDADO',
                            'created_at' => $fecha,
                            'updated_at' => $fecha,
                        ];
                    }

                    return null;  // Retornamos null para las entradas que no tienen coincidencias.
                })->filter()->values()->toArray();


                foreach ($remanentes as $remanente) {
                    // Iteramos sobre cada 'Contable - Abono' en ingresosPorClasificar
                    foreach ($polizasInicialesIngresosPorClasificar as $key => $abono) {
                        // Verificamos si el abono es del tipo 'Contable - Abono'
                        if ($abono->tipo_interaccion == 'Contable - Abono') {
                            // Actualizamos el total del abono para que sea igual al del remanente
                            $abonoArray = $abono->toArray();
                            $abonoArray['total'] = $remanente['total'];
                            $abonoArray['area'] = $remanente['area'];
                            $abonoArray['tipo_poliza'] = $remanente['tipo_poliza'];
                            $abonoArray['numero_poliza'] = $remanente['numero_poliza'];
                            $abonoArray['descripcion'] = $remanente['descripcion'];
                            $abonoArray['categoria'] = $remanente['categoria'];
                            $abonoArray['validado'] = $remanente['validado'];
                            $abonoArray['estatus_evento'] = $remanente['estatus_evento'];

                            // Añadimos el abono actualizado al array de remanentes
                            array_push($remanentes, $abonoArray);

                            // Eliminamos el abono de la colección original
                            unset($polizasInicialesIngresosPorClasificar[$key]);

                            // Salimos del bucle interno para evitar emparejar el mismo remanente con múltiples abonos
                            break;
                        }
                    }
                }

                foreach ($remanentes as $remanente) {
                    Poliza::create([
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $remanente['area'],
                        'tipo_poliza' => $remanente['tipo_poliza'],
                        'numero_poliza' =>  $remanente['numero_poliza'],
                        'fecha' => $remanente['fecha'],
                        'cuenta' => $remanente['cuenta'],
                        'concepto' => $remanente['concepto'],
                        'total' => $remanente['total'],
                        'mes' => $remanente['mes'],
                        'descripcion' => $remanente['descripcion'],
                        'evento' => $remanente['evento'],
                        'tipo_interaccion' => $remanente['tipo_interaccion'],
                        'validado' => $remanente['validado'],
                        'estatus_evento' => $remanente['estatus_evento'],
                        'categoria' => $remanente['categoria'],
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }
            } else {
                $this->numeroPolizaRemanente = 0;
            }
            $importeTotalEvento = DB::select('EXEC ImporteTotalDevengadoPrevRecaudado @evento = ?', [$this->numeroEvento]);
            if ($importeTotalEvento[0]->MontoDelEvento == 0) {
                Poliza::where('evento', '=', $this->numeroEvento)
                    ->whereIn('categoria', ['INGRESOS POR CLASIFICAR', 'INGRESOS DEVENGADO PREVIAMENTE RECAUDADO'])
                    ->update(['estatus_evento' => EstatusEvento::FINALIZADO->value]);
            }
            DB::commit();
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total, $this->numeroPolizaRemanente);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en devengado previamente recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}
