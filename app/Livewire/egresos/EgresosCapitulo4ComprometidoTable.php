<?php

namespace App\Livewire\egresos;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Clases\Column;
use Livewire\Attributes\On;
use App\Livewire\Tabla;
use Illuminate\Database\Eloquent\Builder;
use Log;

class EgresosCapitulo4ComprometidoTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    
    public function render()
    {
        return view('livewire.egresos.egresos-capitulo4-comprometido-table');
    }

    public function query(): Builder
    {

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
            Column::make('pttoEjecutar', 'PPTO por ejecutar'),//->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad'),//->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try{

            $nuevoRegistro = [
                'id' => 0,
                'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                'mes' => $registro['mes'],
                'movimiento' => 'COMPROMETIDO CAPITULO 4', 
                'pttoEjecutar' => $registro['pttoEjecutar'],
                'importe' => $registro['importe'],
                'disponibilidad' => ''//$totalDisponible,
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
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en comprometido del capítulo 4: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarPresupuesto($registro)
    {

    }

    public function edit($id)
    {
        try{
            foreach ($this->dataCompleta as $key => $registro) {
                if ($registro['id'] == $id) {
                    $datosRegistro = [
                        'area' => $registro['areaResponsableId'],
                        'cuenta' => $registro['cuentaId'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe'],
                        'pttoEjecutar' => $registro['pttoEjecutar']
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
    
            $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al editar en comprometido del capítulo 4: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function delete($id)
    {
        try{
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
    
            $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al eliminar en comprometido del capítulo 4: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
        
    }

    public function recaulcularDisponibilidad($id)
    {
        
    }


    public function changeState($value)
    {
    }
}