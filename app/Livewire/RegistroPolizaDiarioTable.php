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
use App\Models\CodigoDepartamento;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class RegistroPolizaDiarioTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $totalCargo = 0;
    public $totalAbono = 0;
    public $numeroPoliza;
    public $numeroEvento;

    public $totalDisponible = 0;

    public function render()
    {
        return view('livewire.registro-poliza-diario-table');
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
            Column::make('tipoInteraccion', 'Tipo de interacción'),
            Column::make('importeCargo', 'Importe cargo')->component('columns.importe'),
            Column::make('importeAbono', 'Importe abono')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad')->component('columns.importe'),
            Column::make('mes', 'Mes'),
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
                        'tipoInteraccion' => $registro['tipoInteraccion'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe'],
                        'solvencia' => $registro['solvencia']
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
            $totalCargoActualizado = array_sum(array_column(
                array_filter($this->cacheData, fn($item) => $item['tipoInteraccion'] == 'Contable - Cargo'),
                'importe'
            ));

            $totalAbonoActualizado = array_sum(array_column(
                array_filter($this->cacheData, fn($item) => $item['tipoInteraccion'] == 'Contable - Abono'),
                'importe'
            ));

            $this->totalCargo = $totalCargoActualizado;
            $this->totalAbono = $totalAbonoActualizado;
            $this->dispatch('cambioTotal', totalCargo: $totalCargoActualizado, totalAbono: $totalAbonoActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al editar en poliza diario: ' . $th->getMessage());
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
            // Recalculamos los totales solo después de eliminar el registro
            $totalCargoActualizado = array_sum(array_column(
                array_filter($this->cacheData, fn($item) => $item['tipoInteraccion'] == 'Contable - Cargo'),
                'importe'
            ));

            $totalAbonoActualizado = array_sum(array_column(
                array_filter($this->cacheData, fn($item) => $item['tipoInteraccion'] == 'Contable - Abono'),
                'importe'
            ));

            $this->totalCargo = $totalCargoActualizado;
            $this->totalAbono = $totalAbonoActualizado;
            $this->dispatch('cambioTotal', totalCargo: $totalCargoActualizado, totalAbono: $totalAbonoActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al eliminar en poliza diario: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al eliminar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function changeState($value) {}

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try {
            $importeCargo = 0;
            $importeAbono = 0;
            if($registro['tipoInteraccion'] == 'Contable - Cargo'){
                $importeCargo = $registro['importe'];
            }else{
                $importeAbono = $registro['importe'];
            }
            if($this->verificarPresupuesto($registro)){
                $nuevoRegistro = [
                    'id' => 0,
                    'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                    'cuenta' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                    'tipoInteraccion' => $registro['tipoInteraccion'],
                    'mes' => $registro['mes'],
                    'movimiento' => 'DIVERSOS CONCEPTOS',
                    'importe' => $registro['importe'],
                    'importeCargo' => $importeCargo,
                    'solvencia' => $registro['solvencia'],
                    'importeAbono' => $importeAbono,
                    'disponibilidad' => $this->totalDisponible
                ];
    
                array_push($this->cacheData, $nuevoRegistro);
                array_push($this->dataCompleta, $registro);
                $this->totalCargo = 0;
                $this->totalAbono = 0;
                foreach ($this->cacheData as $key => $registro) {
                    $this->cacheData[$key]['id'] = $key + 1; // El ID comienza en 1
                    $this->dataCompleta[$key]['id'] = $key + 1;
                    if ($registro['tipoInteraccion'] == 'Contable - Cargo') {
                        $this->totalCargo += $registro['importe'];
                    } else {
                        $this->totalAbono += $registro['importe'];
                    }
                }
                $this->dispatch('cambioTotal', totalCargo: $this->totalCargo, totalAbono: $this->totalAbono);
            }
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en poliza diario: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarPresupuesto($registro)
    {
        $solvencia = $registro['solvencia'];
        if($registro['tipoInteraccion'] == 'Contable - Abono'){
            $this->totalDisponible = $solvencia + $registro['importe'];
            return true;
        }

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

    public function recalcularDisponibilidad($id)
    {
        $datosSeleccionado = [];
        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                $datosSeleccionado = [
                    'codigoCuenta' => $registro['codigoCuenta']
                ];
            }
        }

        $totalImportes = 0;
        foreach ($this->cacheData as $key => $movimiento) {
            if ($movimiento['id'] != $id && str_contains($movimiento['cuenta'], $datosSeleccionado['codigoCuenta'])) {
                if ($totalImportes == 0) {
                    $movimiento['disponibilidad'] = $movimiento['solvencia'] - $movimiento['importe'];
                    $totalImportes += $movimiento['importe'];
                } else {
                    $movimiento['disponibilidad'] = bcsub(bcsub($movimiento['solvencia'], $totalImportes, 2), $movimiento['importe'], 2);
                    $totalImportes += $movimiento['importe'];
                }
                $this->cacheData[$key] = $movimiento;
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
        if ($this->totalCargo != $this->totalAbono) {
            $this->dispatch('mostrarMensaje', mensaje: 'Los totales Cargo y Abono deben estar balanceados', tipo: 'error', tiempo: 3000);
            return;
        }

        try {
            $idUsuarioRegistrante = Auth::id();
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

            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar una poliza diario con evento: ' . $this->numeroEvento, request());

            DB::beginTransaction();

            $anioActual = Carbon::now()->year;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);

            $polizas = [];
            foreach ($this->dataCompleta as $movimiento) {
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
                    'tipo_interaccion' => $movimiento['tipoInteraccion'],
                    'validado' => false,
                    'estatus_evento' => true,
                    'categoria' => 'DIARIO DIVERSOS CONCEPTOS',
                    'created_at' => $fecha,
                    'updated_at' => $fecha
                ]);
            }
            Poliza::insert($polizas);
            DB::commit();
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->totalCargo, $this->totalAbono);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en Poliza Diario: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}
