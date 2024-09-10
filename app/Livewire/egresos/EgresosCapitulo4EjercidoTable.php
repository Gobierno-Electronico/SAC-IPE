<?php

namespace App\Livewire\egresos;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Clases\Column;
use Livewire\Attributes\On;
use App\Livewire\Tabla;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\BitacoraController;
use App\Models\Poliza;
use Carbon\Carbon;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use Log;
use DB;
class EgresosCapitulo4EjercidoTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $totalDisponible = 0;
    public $numeroEvento;
    
    public function render()
    {
        return view('livewire.egresos.egresos-capitulo4-ejercido-table');
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
            Column::make('mes', 'Mes'),
            Column::make('movimiento', 'Movimiento'),
            Column::make('pptoDevengado', 'PPTO Devengado')->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try{
            if ($this->total + $registro['importe'] > $registro['montoEvento']) {
                $this->dispatch('mostrarMensaje', mensaje: 'Monto total del evento superado', tipo: 'error', tiempo: 3000);
                return;
            }

            if($this->verificarPresupuesto($registro)){
                $nuevoRegistro = [
                    'id' => 0,
                    'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                    'partida' => $registro['codigoPartida'] . ' ' . $registro['descripcionPartida'],
                    'cuentaContable' => $registro['codigoCuentaContable'] . ' ' . $registro['descripcionCuentaContable'],
                    'mes' => $registro['mes'],
                    'movimiento' => 'EJERCIDO', 
                    'pttoComprometido' => $registro['pttoDevengado'],
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
                $this->dispatch('cambioTotal', total: $this->total);
            }
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en ejercido del capítulo 4: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarPresupuesto($registro)
    {
        $solvencia = $registro['pttoDevengado'];
        $this->totalDisponible = $solvencia - $registro['importe'];
        $totalImportes = 0;

        foreach ($this->cacheData as $movimiento){
            if(str_contains($movimiento['area'], $registro['codigoAreaResponsable']) && str_contains($movimiento['partida'], $registro['codigoPartida']) && $movimiento['mes'] == $registro['mes']){
                $totalImportes += $movimiento['importe'];
            }
        }

        if($totalImportes > 0){
            $this->totalDisponible = $solvencia - $totalImportes - $registro['importe'];
        }

        if($this->totalDisponible < 0){
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto devengado insuficiente', tipo: 'warning', tiempo: 3000);
            return false;
        }
        return true;
    }

    public function edit($id)
    {
        {
            try {
                //code...
                $this->recalcularDisponibilidad($id);
                foreach ($this->dataCompleta as $key => $registro) {
                    if ($registro['id'] == $id) {
                        $datosRegistro = [
                            'area' => $registro['areaResponsableId'],
                            'cuenta' => $registro['cuentaId'],
                            'cuentaPago' => $registro['cuentaPagoId'],
                            'mes' => $registro['mes'],
                            'importe' => $registro['importe']
                        ];
                        unset($this->dataCompleta[$key]);
                        $this->dispatch('llenar-formulario', $datosRegistro);
                        break;
                    }
                }
        
                foreach ($this->cacheData as $key => $registro) {
                    if ($registro['id'] == $id) {
                        unset($this->cacheData[$key]);
                        break;
                    }
                }
                // Recalculamos los totales solo después de eliminar el registro
                $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
                $this->total = $totalActualizado;
                $this->dispatch('cambioTotal', total: $totalActualizado);
            } catch (\Throwable $th) {
                Log::error('Ocurrió un error al editar en ejercido: '. $th->getMessage());
                $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
            }
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
                    break;
                }
            }
    
            foreach ($this->dataCompleta as $key => $registro) {
                if ($registro['id'] == $id) {
                    unset($this->dataCompleta[$key]);
                    break;
                }
            }
            // Recalculamos los totales solo después de eliminar el registro
            $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al eliminar en ejercido: '. $th->getMessage());
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
                    'codigoCuentaPartida' => $registro['codigoPartida'],
                    'mes' => $registro['mes']
                ];
            }
        }

        $totalImportes = 0;
        foreach($this->cacheData as $key => $movimiento) {
            if($movimiento['id'] != $id && str_contains($movimiento['area'], $datosSeleccionado['codigoArea']) && str_contains($movimiento['partida'], $datosSeleccionado['codigoCuentaPartida']) && $movimiento['mes'] == $datosSeleccionado['mes']) {
                if($totalImportes == 0){
                    $movimiento['disponibilidad'] = $movimiento['pttoDevengado'] - $movimiento['importe'];
                    $totalImportes += $movimiento['importe'];
                }else{
                    $movimiento['disponibilidad'] = $movimiento['pttoDevengado'] - $totalImportes - $movimiento['importe'];
                    $totalImportes += $movimiento['importe'];
                }
                $this->cacheData[$key] = $movimiento;
            }
        }
    }


    public function changeState($value)
    {

    }
}