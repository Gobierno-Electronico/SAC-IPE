<?php

namespace App\Livewire\prestamos;

use App\Livewire\Tabla;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Clases\Column;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;
use Log;
use DB;
use Carbon\Carbon;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Http\Controllers\BitacoraController;
use App\Models\Poliza;

class PrestamosRecuperacionRecaudadoPrestamosRenovacionTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $totalDisponible = 0;
    public $numeroEvento;
    public $numeroPoliza;

    public function render()
    {
        return view('livewire.prestamos.prestamos-recuperacion-recaudado-prestamosRenovacion-table');
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
            Column::make('cuentaBanco', 'Cuenta de banco'),
            Column::make('mes', 'Mes'),
            Column::make('movimiento', 'Movimiento'),
            Column::make('pttoEjecutar', 'PPTO por ejecutar')->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
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
                        'cuentaBanco' => $registro['cuentaBancoId'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe'],
                        'pttoEjecutar' => $registro['pttoEjecutar']
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
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en recaudado préstamos renovación del capítulo 7000: ' . $th->getMessage());
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
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en recaudado préstamos renovación del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function recalcularDisponibilidad($id)
    {
        // dd($this->dataCompleta);
        $mesSeleccionado = "";
        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                $mesSeleccionado = $registro['mes'];
            }
        }

        $totalImportes = 0;
        foreach ($this->cacheData as $key => $movimiento) {
            if ($movimiento['id'] != $id && str_contains($movimiento['area'], $datosSeleccionado['codigoArea']) && $movimiento['mes'] == $mesSeleccionado && str_contains($movimiento['partida'], $registro['codigoCuenta'])) {
                if ($totalImportes == 0) {
                    $movimiento['disponibilidad'] = $movimiento['pttoEjecutar'] - $movimiento['importe'];
                    $totalImportes += $movimiento['importe'];
                } else {
                    $movimiento['disponibilidad'] = $movimiento['pttoEjecutar'] - $totalImportes - $movimiento['importe'];
                    $totalImportes += $movimiento['importe'];
                }
                $this->cacheData[$key] = $movimiento;
            }
        }
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try {
            // if($this->verificarPresupuesto($registro)){   
            $nuevoRegistro = [
                'id' => 0,
                'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                'cuentaBanco' => $registro['codigoCuentaBanco'] . ' ' . $registro['descripcionCuentaBanco'],
                'mes' => $registro['mes'],
                'movimiento' => 'OTORGAMIENTO RECAUDADO PRESTAMOS INICIALES',
                'pttoEjecutar' => $registro['pttoEjecutar'],
                'importe' => $registro['importe'],
                'disponibilidad' => $this->totalDisponible,
            ];
            array_push($this->cacheData, $nuevoRegistro);
            array_push($this->dataCompleta, $registro);
            $this->total = 0;
            foreach ($this->cacheData as $key => $registro) {
                $this->cacheData[$key]['id'] = $key + 1;
                $this->dataCompleta[$key]['id'] = $key + 1;
                $this->total += $registro['importe'];
            }
            // $this->dispatch('cambioTotal', total: $this->total);
            // }
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en recaudado préstamos renovación del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarPresupuesto($registro)
    {
        $solvencia = $registro['pttoEjecutar'];
        $this->totalDisponible = $solvencia - $registro['importe'];
        $totalImportes = 0;

        foreach ($this->cacheData as $movimiento) {
            if (str_contains($movimiento['area'], $registro['codigoAreaResponsable']) && $movimiento['mes'] == $registro['mes'] && str_contains($movimiento['partida'], $registro['codigoCuenta'])) {
                $totalImportes += $movimiento['importe'];
            }
        }

        if ($totalImportes > 0) {
            $this->totalDisponible = $solvencia - $totalImportes - $registro['importe'];
        }

        if ($this->totalDisponible < 0) {
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar insuficiente', tipo: 'warning', tiempo: 3000);
            return false;
        }
        return true;
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
            $numerosPolizas = Poliza::select('numero_poliza')
                ->where('tipo_poliza', '=', 'D')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            sort($numerosPolizas);
            $this->numeroPoliza = (int)end($numerosPolizas) + 1;

            $numerosEvento = Poliza::select('evento')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('evento')
                ->pluck('evento')
                ->toArray();
            sort($numerosEvento);
            $this->numeroEvento = (int)end($numerosEvento) + 1;

            $anioActual = Carbon::now()->year;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);

            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar un recaudado préstamos inicales del capítulo 7000: ', request());
            DB::beginTransaction();

            foreach ($this->dataCompleta as $movimiento) {
                $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('concepto_id', [10099])
                    ->where('tipo_interaccion', '=', 'Contable - Abono')->first();
                // dd($interaccionCuentaConceptoPrincipal);

                $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                    ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                    ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();


                $polizas = [
                    [
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
                        'estatus_evento' => true,
                        'categoria' => 'RECUPERACION RECAUDADO PRESTAMOS RENOVACION',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]
                ];

                foreach($interaccionCuentaCuentas as $dataCuenta){
                    $entraPolizas = false;
                    if($dataCuenta['tipo_interaccion'] == 'Contable - Cargo'){
                        if($movimiento['codigoCuentaBanco'] == $dataCuenta['Codigo_cuenta']){
                            $entraPolizas = true;
                        }
                    }else{
                        $entraPolizas = true;
                    }
                    if($entraPolizas){
                        array_push($polizas, [
                            'area' => $movimiento['codigoAreaResponsable'],
                            'tipo_poliza' => 'D',
                            'numero_poliza' =>  $this->numeroPoliza,
                            'fecha' => $movimiento['fechaAfectacion'],
                            'cuenta' => $dataCuenta['Codigo_cuenta'],
                            'concepto' => $dataCuenta['Descripcion_cuenta'],
                            'total' => abs($movimiento['importe']),
                            'mes' => $movimiento['mes'],
                            'descripcion' => $movimiento['observaciones'],
                            'evento' => $this->numeroEvento,
                            'tipo_interaccion' => $dataCuenta['tipo_interaccion'],
                            'validado' => false,
                            'estatus_evento' => true,
                            'categoria' => 'RECUPERACION RECAUDADO PRESTAMOS RENOVACION',
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ]);
                    }
                }
                Poliza::insert($polizas);
                DB::commit();
            }
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en recaudado préstamos con renovación del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}
