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

    public function render(){
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
        try{
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

        }catch (\Throwable $th) {
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

        if($totalImportesEvento > 0){
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
        try{
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

    public function changeState($value)
    {
    }

    #[On('finalizar-registros')]
    public function finalizarRegistros()
    {
        
    }
}
