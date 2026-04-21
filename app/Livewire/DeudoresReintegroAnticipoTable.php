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
use App\Models\Cuenta;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use App\Enums\EstatusEvento;

class DeudoresReintegroAnticipoTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $numeroPoliza;
    public $numeroEvento;
    public $totalDisponible = 0;
    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
    }

    public function render()
    {
        return view('livewire.deudores-reintegro-anticipo-table');
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
            Column::make('cuenta', 'Cuenta'),
            Column::make('documentoFuente', 'Documento fuente'),
            Column::make('cuentaCargo', 'Cuenta Cargo'),
            Column::make('mes', 'Mes'),
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
                        'cuenta' => $registro['idCuenta'],
                        'cuentaCargo' => $registro['idCuentaCargo'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe'],
                        'solvencia' => $registro['ppto'],
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
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al editar en deudores reintegro table: ' . $th->getMessage());
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
            Log::error('Ocurrió un error al eliminar en deudores reintegro table: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al eliminar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function recalcularDisponibilidad($id)
    {
        $datosSeleccionado = [];
        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                $datosSeleccionado = [
                    'codigoCuenta' => $registro['codigoCuenta'],
                    'mes' => $registro['mes']
                ];
            }
        }

        $totalImportes = 0;
        foreach ($this->cacheData as $key => $movimiento) {
            if ($movimiento['id'] != $id && str_contains($movimiento['cuenta'], $datosSeleccionado['codigoCuenta'])) {
                if ($totalImportes == 0) {
                    $movimiento['disponibilidad'] = bcsub($movimiento['solvencia'], $movimiento['importe'], 2);
                    $totalImportes += $movimiento['importe'];
                } else {
                    $movimiento['disponibilidad'] = bcsub(bcsub($movimiento['solvencia'], $totalImportes, 2), $movimiento['importe'], 2);
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
            if ($this->verificarPresupuesto($registro)) {
                $nuevoRegistro = [
                    'id' => 0,
                    'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                    'cuenta' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                    'cuentaCargo' => $registro['codigoCuentaCargo'] . ' ' . $registro['descripcionCuentaCargo'],
                    'mes' => $registro['mes'],
                    'movimiento' => 'DEUDORES REINTEGRO ANTICIPO',
                    'importe' => $registro['importe'],
                    'solvencia' => $registro['ppto'],
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
                }
                $this->dispatch('cambioTotal', total: $this->total);
            }
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en deudores reintegro table: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarPresupuesto($registro)
    {
        $solvencia = $registro['ppto'];
        $this->totalDisponible = $solvencia - $registro['importe'];
        $totalImportes = 0;

        foreach ($this->cacheData as $movimiento) {
            if (str_contains($movimiento['cuenta'], $registro['codigoCuenta'])) {
                $totalImportes += $movimiento['importe'];
            }
        }

        if ($totalImportes > 0) {
            $this->totalDisponible = bcsub(bcsub($solvencia, $totalImportes, 2), $registro['importe'], 2);
        }

        if ($this->totalDisponible < 0) {
            $this->dispatch('mostrarMensaje', mensaje: 'Solvencia insuficiente', tipo: 'warning', tiempo: 3000);
            return false;
        }
        return true;
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
                ->where('tipo_poliza', '=', 'D')
                ->whereYear('fecha', '=', (string) $this->anio)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();
            sort($numerosPolizas);
            $this->numeroPoliza = (int)end($numerosPolizas) + 1;

            $this->numeroEvento = $this->dataCompleta[0]['evento'];

            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar un reintegro anticipo con evento: ' . $this->numeroEvento, request());
            DB::beginTransaction();

            $anioActual = $this->anio;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);

            $polizas = [];
            foreach ($this->dataCompleta as $movimiento) {
                $movimiento['importe'] = doubleval($movimiento['importe']);
                $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $movimiento['idCuenta'])
                    ->whereIn('concepto_id', [10107])
                    ->where('tipo_interaccion', '=', 'Contable - Abono')
                    ->first();

                $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                    ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                    ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get();

                $interaccionCuentaCuentasFiltradas = [];
                foreach ($interaccionCuentaCuentas as $cuenta) {
                    if ($cuenta['tipo_interaccion'] == 'Contable - Cargo') {
                        if ($cuenta['Codigo_cuenta'] == $movimiento['codigoCuentaCargo']) {
                            $interaccionCuentaCuentasFiltradas[] = $cuenta;
                            continue;
                        }
                    } else {
                        $interaccionCuentaCuentasFiltradas[] = $cuenta;
                    }
                }

                $interaccionCuentaCuentas = $interaccionCuentaCuentasFiltradas;

                array_push($polizas, [
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
                    'categoria' => 'DEUDORES REINTEGRO ANTICIPOS',
                    'documento_fuente' => $movimiento['documentoFuente'],
                    'created_at' => $fecha,
                    'updated_at' => $fecha
                ]);


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
                        'categoria' => 'DEUDORES REINTEGRO ANTICIPOS',
                        'documento_fuente' => $movimiento['documentoFuente'],
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }
            }

            Poliza::insert($polizas);
            DB::commit();

            $importeTotalEvento = DB::select('EXEC ImporteTotalOtorgamientoAnticipo @evento = ?, @anio = ?', [$this->numeroEvento, $this->anio]);
            if ($importeTotalEvento[0]->MontoDelEvento == 0) {
                Poliza::where('evento', '=', $this->numeroEvento)
                    ->whereIn('categoria', [
                        'DEUDORES OTORGAMIENTO ANTICIPOS',
                        'DEUDORES REINTEGRO ANTICIPOS'
                    ])
                    ->whereYear('fecha', '=', (string) $this->anio)
                    ->update(['estatus_evento' => EstatusEvento::FINALIZADO->value]);

                $hayRetenciones = Poliza::where('evento', '=', $this->numeroEvento)
                    ->where('categoria', '=', 'DEUDORES COMPROBACION ANTICIPOS')
                    ->whereYear('fecha', '=', (string) $this->anio)
                    ->where(function ($q) {
                        $q->where('concepto', 'LIKE', '%ISR%')
                            ->orWhere('concepto', 'LIKE', '%IVA%');
                    })
                    ->exists();
                if (!$hayRetenciones) {
                    Poliza::where('evento', '=', $this->numeroEvento)
                        ->where('categoria', 'DEUDORES COMPROBACION ANTICIPOS')
                        ->whereYear('fecha', '=', (string) $this->anio)
                        ->update(['estatus_evento' => EstatusEvento::FINALIZADO->value]);
                }
            }

            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en deudores reintegro table: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}
