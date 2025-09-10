<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use App\Clases\Column;
use App\Http\Controllers\BitacoraController;
use Carbon\Carbon;
use Log;
use DB;
use App\Livewire\Tabla;
use App\Models\Cuenta;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use App\Enums\EstatusEvento;

class DeudoresComprobacionAnticipoTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $totalDisponible = 0;
    public $totalDisponibleEvento = 0;
    public $numeroPoliza;
    public $numeroEvento;

    public function render()
    {
        return view('livewire.deudores-comprobacion-anticipo-table');
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
            Column::make('cuenta', 'Cuenta'),
            Column::make('cuentaContable', 'Cuenta contable'),
            Column::make('mes', 'Mes'),
            Column::make('tipoRegistro', 'Registro'),
            Column::make('movimiento', 'Movimiento'),
            Column::make('pttoEjercer', 'PPTO Por Ejercer')->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad evento')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try {
            if (bccomp((string)($this->total + $registro['importe']), (string)$registro['montoEvento'], 2) == 1) {
                $this->dispatch('mostrarMensaje', mensaje: 'Monto total del evento superado', tipo: 'error', tiempo: 3000);
                return;
            }

            if ($this->verificarPresupuesto($registro)) {
                $this->calcularDisponibilidad($registro);
                $nuevoRegistro = [
                    'id' => 0,
                    'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                    'cuenta' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                    'cuentaContable' => $registro['codigoCuentaContable'] . ' ' . $registro['descripcionCuentaContable'],
                    'mes' => $registro['mes'],
                    'tipoRegistro' => $registro['tipoRegistro'],
                    'movimiento' => 'DEUDORES COMPROBACIÓN ANTICIPO',
                    'pttoEjercer' => $registro['pttoEjercer'],
                    'importe' => $registro['importe'],
                    'disponibilidad' => $this->totalDispoibleEvento,
                    'evento' => $registro['evento'],
                    'montoEvento' => $registro['montoEvento']
                ];
                array_push($this->cacheData, $nuevoRegistro);
                array_push($this->dataCompleta, $registro);
                $this->total = 0;
                foreach ($this->cacheData as $key => $registro) {
                    $this->cacheData[$key]['id'] = $key + 1;
                    $this->dataCompleta[$key]['id'] = $key + 1;
                    $this->total += $registro['importe'];
                }
                $this->dispatch('cambioTotal', total: $this->total);
            }
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en deudores comprobación de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarPresupuesto($registro)
    {
        $solvencia = $registro['pttoEjercer'];
        $this->totalDisponible = $solvencia - $registro['importe'];
        $totalImportes = 0;
        foreach ($this->cacheData as $movimiento) {
            if (str_contains($movimiento['area'], $registro['codigoAreaResponsable']) && str_contains($movimiento['cuenta'], $registro['codigoCuenta']) && $movimiento['mes'] == $registro['mes']) {
                $totalImportes += $movimiento['importe'];
            }
        }

        if ($totalImportes > 0) {
            $this->totalDisponible = bcsub(bcsub($solvencia, $totalImportes, 2), $registro['importe'], 2);
        }

        if ($this->totalDisponible < 0) {
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejercer insuficiente', tipo: 'warning', tiempo: 3000);
            return false;
        }

        return true;
    }

    public function calcularDisponibilidad($registro)
    {
        $this->totalDispoibleEvento = $registro['montoEvento'] - $registro['importe'];
        $totalImportesEvento = 0;

        foreach ($this->cacheData as $movimiento) {
            if ($movimiento['evento'] == $registro['evento']) {
                $totalImportesEvento = $totalImportesEvento + $movimiento['importe'];
            }
        }

        if ($totalImportesEvento > 0) {
            $this->totalDispoibleEvento = bcsub(bcsub($registro['montoEvento'], $totalImportesEvento, 2), $registro['importe'], 2);
        }
    }

    public function recalcularDisponibilidad($id)
    {
        $datosSeleccionado = [];
        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                $datosSeleccionado = [
                    'evento' => $registro['evento'],
                ];
            }
        }

        $totalImportes = 0;
        foreach ($this->cacheData as $key => $movimiento) {
            if ($movimiento['id'] != $id && $movimiento['evento'] == $datosSeleccionado['evento']) {
                if ($totalImportes == 0) {
                    $movimiento['disponibilidad'] = bcsub($movimiento['montoEvento'], $movimiento['importe'], 2);
                    $totalImportes += $movimiento['importe'];
                } else {
                    $movimiento['disponibilidad'] = bcsub(bcsub($movimiento['montoEvento'], $totalImportes, 2), $movimiento['importe'], 2);
                    $totalImportes += $movimiento['importe'];
                }
                $this->cacheData[$key] = $movimiento;
            }
        }
    }


    public function edit($id)
    {
        try {
            $this->recalcularDisponibilidad($id);
            foreach ($this->dataCompleta as $key => $registro) {
                if ($registro['id'] == $id) {
                    $datosRegistro = [
                        'area' => $registro['areaResponsableId'],
                        'cuenta' => $registro['cuentaId'],
                        'cuentaContable' => $registro['cuentaContableId'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe'],
                        'pttoEjercer' => $registro['pttoEjercer'],
                        'selectorPagoRetenciones' => $registro['selectorPagoRetenciones'],
                        'tipoRegistro' => $registro['tipoRegistro'],
                        'selectorBanco' => $registro['selectorBanco'],
                        'cuentaBanco' => $registro['cuentaBancoId'],
                        'importeBanco' => $registro['importeBanco'],
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

            $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al editar en deudores comprobación de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function delete($id)
    {
        try {
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

            $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al eliminar en deudores comprobación de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function changeState($value) {}

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
                ->where('tipo_poliza', '=', 'D')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            sort($numerosPolizas);
            $this->numeroPoliza = (int)end($numerosPolizas) + 1;

            $this->numeroEvento = $this->dataCompleta[0]['evento'];

            $anioActual = Carbon::now()->year;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);

            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar un devengado del capítulo 2 y 3 con evento: ' . $this->numeroEvento, request());
            DB::beginTransaction();

            $montoRetenciones = 0;
            $polizas = [];
            foreach ($this->dataCompleta as $movimiento) {

                $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $movimiento['cuentaId'])->whereIn('concepto_id', [10109])
                    ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();

                $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                    ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                    ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();

                if ($movimiento['selectorPagoRetenciones'] == 'NO') {
                    $interaccionCuentaCuentasFiltradas = [];

                    foreach ($interaccionCuentaCuentas as $key => $cuenta) {
                        if ($cuenta['tipo_interaccion'] == 'Contable - Cargo') {
                            if (count($interaccionCuentaCuentas) > 11) {
                                $numeroInicialCuenta = explode('.', $cuenta['Codigo_cuenta']);
                                if ($numeroInicialCuenta[0] == 5) {
                                    $interaccionCuentaCuentasFiltradas[] = $cuenta;
                                    continue;
                                }

                                if ($movimiento['tipoRegistro'] == 'Almacen' && $numeroInicialCuenta[0] == 1) {
                                    $interaccionCuentaCuentasFiltradas[] = $cuenta;
                                    continue;
                                } else if ($movimiento['tipoRegistro'] == 'Gasto' && $numeroInicialCuenta[0] == 2) {
                                    $interaccionCuentaCuentasFiltradas[] = $cuenta;
                                    continue;
                                }
                            } else {
                                $interaccionCuentaCuentasFiltradas[] = $cuenta;
                            }
                        } else {
                            $interaccionCuentaCuentasFiltradas[] = $cuenta;
                        }
                    }
                    $interaccionCuentaCuentas = $interaccionCuentaCuentasFiltradas;


                    $polizas = [
                        [
                            'idUsuarioRegistrante' => $idUsuarioRegistrante,
                            'area' => $movimiento['codigoAreaResponsable'],
                            'tipo_poliza' => 'D',
                            'numero_poliza' =>  $this->numeroPoliza,
                            'fecha' => $movimiento['fechaAfectacion'],
                            'cuenta' => $movimiento['codigoCuenta'],
                            'concepto' => $movimiento['descripcionCuenta'],
                            'total' => abs($movimiento['importe']),
                            'mes' => $movimiento['mes'],
                            'descripcion' => $movimiento['observaciones'],
                            'evento' => $this->numeroEvento,
                            'tipo_interaccion' => $interaccionCuentaConceptoPrincipal->tipo_interaccion,
                            'validado' => false,
                            'estatus_evento' => EstatusEvento::ACTIVO->value,
                            'categoria' => 'DEUDORES COMPROBACION ANTICIPOS',
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ]
                    ];

                    foreach ($interaccionCuentaCuentas as $key => $dataCuenta) {
                        array_push($polizas, [
                            'idUsuarioRegistrante' => $idUsuarioRegistrante,
                            'area' => $movimiento['codigoAreaResponsable'],
                            'tipo_poliza' => 'D',
                            'numero_poliza' =>  $this->numeroPoliza,
                            'fecha' => $movimiento['fechaAfectacion'],
                            'cuenta' => $dataCuenta['Codigo_cuenta'],
                            'concepto' => $dataCuenta['Descripcion_cuenta'],
                            'total' => $movimiento['importe'],
                            'mes' => $movimiento['mes'],
                            'descripcion' => $movimiento['observaciones'],
                            'evento' => $this->numeroEvento,
                            'tipo_interaccion' => $dataCuenta['tipo_interaccion'],
                            'validado' => false,
                            'estatus_evento' => EstatusEvento::ACTIVO->value,
                            'categoria' => 'DEUDORES COMPROBACION ANTICIPOS',
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ]);
                    }
                    Poliza::insert($polizas);
                } else {
                    $interaccionCuentaCuentasFiltradas = [];

                    foreach ($interaccionCuentaCuentas as $cuenta) {
                        if ($cuenta['tipo_interaccion'] == 'Contable - Cargo') {
                            if (count($interaccionCuentaCuentas) > 11) {
                                $numeroInicialCuenta = explode('.', $cuenta['Codigo_cuenta']);
                                if ($numeroInicialCuenta[0] == 2) {
                                    $interaccionCuentaCuentasFiltradas[] = $cuenta;
                                    continue;
                                }

                                if ($movimiento['tipoRegistro'] == 'Almacen' && $numeroInicialCuenta[0] == 1) {
                                    $interaccionCuentaCuentasFiltradas[] = $cuenta;
                                    continue;
                                } else if ($movimiento['tipoRegistro'] == 'Gasto' && $numeroInicialCuenta[0] >= 5) {
                                    $interaccionCuentaCuentasFiltradas[] = $cuenta;
                                    continue;
                                }
                            } else {
                                $interaccionCuentaCuentasFiltradas[] = $cuenta;
                            }
                        } else {
                            $interaccionCuentaCuentasFiltradas[] = $cuenta;
                        }
                    }
                    $interaccionCuentaCuentas = $interaccionCuentaCuentasFiltradas;

                    if (str_contains($movimiento['codigoCuentaContable'], '2.1.1.7.01.')) {
                        $polizas = [
                            [
                                'idUsuarioRegistrante' => $idUsuarioRegistrante,
                                'area' => $movimiento['codigoAreaResponsable'],
                                'tipo_poliza' => 'D',
                                'numero_poliza' =>  $this->numeroPoliza,
                                'fecha' => $movimiento['fechaAfectacion'],
                                'cuenta' => $movimiento['codigoCuentaContable'],
                                'concepto' => $movimiento['descripcionCuentaContable'],
                                'total' => abs($movimiento['importe']),
                                'mes' => $movimiento['mes'],
                                'descripcion' => $movimiento['observaciones'],
                                'evento' => $this->numeroEvento,
                                'tipo_interaccion' => 'Contable - Abono',
                                'validado' => false,
                                'estatus_evento' => EstatusEvento::ACTIVO->value,
                                'categoria' => 'DEUDORES COMPROBACION ANTICIPOS',
                                'created_at' => $fecha,
                                'updated_at' => $fecha
                            ]
                        ];


                        foreach ($interaccionCuentaCuentas as $key => $dataCuenta) {
                            if (!str_contains($dataCuenta['Descripcion_cuenta'], 'Responsabilidad de Funcionarios y Empleados Ejercicio Actual')) {
                                if ($dataCuenta['tipo_interaccion'] == 'Presupuestal - Abono' && str_contains($dataCuenta['Descripcion_cuenta'], '(Ejercido)')) {
                                    continue;
                                } else {
                                    array_push($polizas, [
                                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                                        'area' => $movimiento['codigoAreaResponsable'],
                                        'tipo_poliza' => 'D',
                                        'numero_poliza' =>  $this->numeroPoliza,
                                        'fecha' => $movimiento['fechaAfectacion'],
                                        'cuenta' => $dataCuenta['Codigo_cuenta'],
                                        'concepto' => $dataCuenta['Descripcion_cuenta'],
                                        'total' => $movimiento['importe'],
                                        'mes' => $movimiento['mes'],
                                        'descripcion' => $movimiento['observaciones'],
                                        'evento' => $this->numeroEvento,
                                        'tipo_interaccion' => $dataCuenta['tipo_interaccion'],
                                        'validado' => false,
                                        'estatus_evento' => EstatusEvento::ACTIVO->value,
                                        'categoria' => 'DEUDORES COMPROBACION ANTICIPOS',
                                        'created_at' => $fecha,
                                        'updated_at' => $fecha
                                    ]);
                                }
                            }
                        }

                        Poliza::insert($polizas);
                    } else {

                        $polizas = [
                            [
                                'idUsuarioRegistrante' => $idUsuarioRegistrante,
                                'area' => $movimiento['codigoAreaResponsable'],
                                'tipo_poliza' => 'D',
                                'numero_poliza' =>  $this->numeroPoliza,
                                'fecha' => $movimiento['fechaAfectacion'],
                                'cuenta' => $movimiento['codigoCuenta'],
                                'concepto' => $movimiento['descripcionCuenta'],
                                'total' => abs($movimiento['importe']),
                                'mes' => $movimiento['mes'],
                                'descripcion' => $movimiento['observaciones'],
                                'evento' => $this->numeroEvento,
                                'tipo_interaccion' => $interaccionCuentaConceptoPrincipal->tipo_interaccion,
                                'validado' => false,
                                'estatus_evento' => EstatusEvento::ACTIVO->value,
                                'categoria' => 'DEUDORES COMPROBACION ANTICIPOS',
                                'created_at' => $fecha,
                                'updated_at' => $fecha
                            ]
                        ];

                        foreach ($interaccionCuentaCuentas as $key => $dataCuenta) {
                            array_push($polizas, [
                                'idUsuarioRegistrante' => $idUsuarioRegistrante,
                                'area' => $movimiento['codigoAreaResponsable'],
                                'tipo_poliza' => 'D',
                                'numero_poliza' =>  $this->numeroPoliza,
                                'fecha' => $movimiento['fechaAfectacion'],
                                'cuenta' => $dataCuenta['Codigo_cuenta'],
                                'concepto' => $dataCuenta['Descripcion_cuenta'],
                                'total' => $movimiento['importe'],
                                'mes' => $movimiento['mes'],
                                'descripcion' => $movimiento['observaciones'],
                                'evento' => $this->numeroEvento,
                                'tipo_interaccion' => $dataCuenta['tipo_interaccion'],
                                'validado' => false,
                                'estatus_evento' => EstatusEvento::ACTIVO->value,
                                'categoria' => 'DEUDORES COMPROBACION ANTICIPOS',
                                'created_at' => $fecha,
                                'updated_at' => $fecha
                            ]);
                        }

                        Poliza::insert($polizas);
                    }
                }
            }
            DB::commit();
            $importeTotalEvento = DB::select('EXEC ImporteTotalOtorgamientoAnticipo @evento = ?', [$this->numeroEvento]);
            
            if ($importeTotalEvento[0]->MontoDelEvento == 0) {
                Poliza::where('evento', '=', $this->numeroEvento)
                    ->whereIn('categoria', [
                        'DEUDORES OTORGAMIENTO ANTICIPOS',
                        'DEUDORES REINTEGRO ANTICIPOS'
                    ])
                    ->whereYear('fecha', '=', Carbon::now()->year)
                    ->update(['estatus_evento' => EstatusEvento::FINALIZADO->value]);

                $hayRetenciones = Poliza::where('evento', '=', $this->numeroEvento)
                    ->where('categoria', '=', 'DEUDORES COMPROBACION ANTICIPOS')
                    ->whereYear('fecha', '=', Carbon::now()->year)
                    ->where(function ($q) {
                        $q->where('concepto', 'LIKE', '%ISR%')
                            ->orWhere('concepto', 'LIKE', '%IVA%');
                    })
                    ->exists();
                if (!$hayRetenciones) {
                    Poliza::where('evento', '=', $this->numeroEvento)
                        ->where('categoria', 'DEUDORES COMPROBACION ANTICIPOS')
                        ->whereYear('fecha', '=', Carbon::now()->year)
                        ->update(['estatus_evento' => EstatusEvento::FINALIZADO->value]);
                }
            }
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en deudores comprobación de anticipo: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}
