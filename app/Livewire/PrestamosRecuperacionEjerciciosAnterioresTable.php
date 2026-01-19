<?php

namespace App\Livewire;

use App\Livewire\Tabla;
use Illuminate\Support\Facades\Auth;
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
use App\Enums\EstatusEvento;

class PrestamosRecuperacionEjerciciosAnterioresTable extends Tabla
{
     public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $totalDisponible = 0;
    public $numeroEvento;
    public $numeroPoliza;
    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }
    
    public function render()
    {
        return view('livewire.prestamos-recuperacion-ejercicios-anteriores-table');
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
            Column::make('documentoFuente', 'Documento fuente'),
            Column::make('partida', 'Partida'),
            Column::make('cuentaCargo', 'Cuenta cargp'),
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
          /*   $this->recalcularDisponibilidad($id);
            foreach ($this->dataCompleta as $key => $registro) {
                if ($registro['id'] == $id) {
                    $datosRegistro = [
                        'area' => $registro['areaResponsableId'],
                        'cuenta' => $registro['cuentaId'],
                        'cuentaCargo' => $registro['cuentaCargoId'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe'],
                        'pttoEjecutar' => $registro['pttoEjecutar'],
                        'documentoFuente' => $registro['documentoFuente'],
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
            $this->total = $totalActualizado; */
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en recuperación préstamos ejercicios anteriores del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function delete($id)
    {
        try {
           /*  $this->recalcularDisponibilidad($id);
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
            $this->total = $totalActualizado; */
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en recuperación préstamos ejercicios anteriores del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function recalcularDisponibilidad($id)
    {
     /*    $mesSeleccionado = "";
        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                $mesSeleccionado = $registro['mes'];
                $datosSeleccionado = [
                    'codigoArea' => $registro['codigoAreaResponsable'],
                    'codigoCuenta' => $registro['codigoCuenta']
                ];
            }
        }

        $totalImportes = 0;
        foreach ($this->cacheData as $key => $movimiento) {
            if ($movimiento['id'] != $id && str_contains($movimiento['area'], $datosSeleccionado['codigoArea']) && $movimiento['mes'] == $mesSeleccionado && str_contains($movimiento['partida'], $datosSeleccionado['codigoCuenta'])) {
                if ($totalImportes == 0) {
                    $movimiento['disponibilidad'] = $movimiento['pttoEjecutar'] - $movimiento['importe'];
                    $totalImportes += $movimiento['importe'];
                } else {
                    $movimiento['disponibilidad'] = $movimiento['pttoEjecutar'] - $totalImportes - $movimiento['importe'];
                    $totalImportes += $movimiento['importe'];
                }
                $this->cacheData[$key] = $movimiento;
            }
        } */
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try {
            // if($this->verificarPresupuesto($registro)){   
           /*  $nuevoRegistro = [
                'id' => 0,
                'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                'cuentaCargo' => $registro['codigoCuentaCargo'] . ' ' . $registro['descripcionCuentaCargo'],
                'mes' => $registro['mes'],
                'movimiento' => 'RECUPERACION RECAUDADO PRESTAMOS RENOVACION',
                'pttoEjecutar' => $registro['pttoEjecutar'],
                'importe' => $registro['importe'],
                'disponibilidad' => $this->totalDisponible,
                'documentoFuente' => $registro['documentoFuente'],
            ];
            array_push($this->cacheData, $nuevoRegistro);
            array_push($this->dataCompleta, $registro);
            $this->total = 0;
            foreach ($this->cacheData as $key => $registro) {
                $this->cacheData[$key]['id'] = $key + 1;
                $this->dataCompleta[$key]['id'] = $key + 1;
                $this->total += $registro['importe'];
            } */
            // $this->dispatch('cambioTotal', total: $this->total);
            // }
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en recuperación préstamos ejercicios anteriores del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarPresupuesto($registro)
    {
        /* $solvencia = $registro['pttoEjecutar'];
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
        return true; */
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
            /* $idUsuarioRegistrante = Auth::id();
            $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) as numero_poliza')
                ->where('tipo_poliza', '=', 'D')
                ->whereYear('fecha', '=', Carbon::now()->year)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            sort($numerosPolizas);
            $this->numeroPoliza = (int)end($numerosPolizas) + 1;

            $numerosEvento = Poliza::selectRaw('CAST(evento AS INT) as evento')
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

                $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                    ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                    ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();


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
                        'categoria' => 'RECUPERACION RECAUDADO PRESTAMOS RENOVACION',
                        'documento_fuente' => $movimiento['documentoFuente'],
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]
                ];

                foreach($interaccionCuentaCuentas as $dataCuenta){
                    $entraPolizas = false;
                    if($dataCuenta['tipo_interaccion'] == 'Contable - Cargo'){
                        if($movimiento['codigoCuentaCargo'] == $dataCuenta['Codigo_cuenta']){
                            $entraPolizas = true;
                        }
                    }else{
                        $entraPolizas = true;
                    }
                    if($entraPolizas){
                        array_push($polizas, [
                            'idUsuarioRegistrante' => $idUsuarioRegistrante,
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
                            'estatus_evento' => EstatusEvento::ACTIVO->value,
                            'categoria' => 'RECUPERACION RECAUDADO PRESTAMOS RENOVACION',
                            'documento_fuente' => $movimiento['documentoFuente'],
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ]);
                    }
                }
                Poliza::insert($polizas);
                DB::commit();
            }
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total); */
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en recuperación préstamos ejercicios anteriores del capítulo 7000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}