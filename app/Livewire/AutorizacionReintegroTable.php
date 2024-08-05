<?php

namespace App\Livewire;
use Livewire\Attributes\On;
use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use App\Clases\Column;
use Log;
use App\Http\Controllers\BitacoraController;
use Illuminate\Pagination\LengthAwarePaginator;

class AutorizacionReintegroTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $numeroPoliza;
    public $numeroEvento;

    public function render(){
        return view('livewire.autorizacion-reintegro-table');
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
            Column::make('cuentaCargo', 'Cuenta de cargo'),
            Column::make('mes', 'Mes'),
            Column::make('movimiento', 'Movimiento'),
            // Column::make('', 'PPTO devengado')->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            // Column::make('', 'Nuevo importe')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    public function edit($id)
    {
        foreach ($this->dataCompleta as $key => $registro) {
            if ($registro['id'] == $id) {
                $datosRegistro = [
                    'area' => $registro['areaResponsableId'],
                    'cuenta' => $registro['cuentaId'],
                    'cuentaCargoId' => $registro['cuentaCargoId'],
                    'mes' => $registro['mes'],
                    'importe' => $registro['importe'],
                    // 'devengado' => $registro['pttoDevengado'],
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

        $this->dispatch('cambioTotal', total: $totalActualizado);
    }

    public function delete($id){
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

        $this->dispatch('cambioTotal', total: $totalActualizado);
    }


    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        $nuevoRegistro = [
            'id' => 0,
            'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
            'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
            'cuentaCargo' => $registro['codigoCuentaCargo']. ' ' . $registro['descripcionCuentaCargo'],
            'mes' => $registro['mes'],
            'movimiento' => 'Autorización de reintegro',
            'devengado' => '',//$registro['pttoDevengado'],
            'importe' => $registro['importe'],
            'nuevoImporte' => '', //$registro['pttoDevengado'] - $registro['importe'],
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
    }

    public function changeState($value)
    {
    }
}
