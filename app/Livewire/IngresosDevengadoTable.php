<?php

namespace App\Livewire;
use Livewire\Attributes\On;
use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use App\Clases\Column;
use Carbon\Carbon;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Http\Controllers\BitacoraController;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;

class IngresosDevengadoTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $numeroPoliza;
    public $numeroEvento;

    public function render(){
        return view('livewire.ingresos-devengado-table');
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
            Column::make('ejecutar', 'PPTO por ejecutar')->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad')->component('columns.importe'),
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
                    'mes' => $registro['mes'],
                    'importe' => $registro['importe'],
                    'ejecutar' => $registro['pttoEjecutar'],
                    'iva' => $registro['iva']
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

    public function changeState($value)
    {
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        $nuevoRegistro = [
            'id' => 0,
            'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
            'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
            'mes' => $registro['mes'],
            'movimiento' => 'DEVENGADO',
            'ejecutar' => $registro['pttoEjecutar'],
            'importe' => $registro['importe'] + $registro['iva'],
            'disponibilidad' => $registro['pttoEjecutar'] - $registro['importe'] - $registro['iva'],
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

    #[On('finalizar-registros')]
    public function finalizarRegistros()
    {
        if (empty($this->cacheData)) {
            $this->dispatch('mostrarMensaje', mensaje: 'Tabla sin registros', tipo: 'error', tiempo: 3000);
            return;
        }

        $numerosPolizas = Poliza::select('numero_poliza')
            ->where('tipo_poliza', '=', 'I')
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
        if (!empty($numerosEvento)) {
            $this->numeroEvento = (int)end($numerosEvento) + 1;
        } else {
            $this->numeroEvento = 1;
        }

        $anioActual = Carbon::now()->year;
        $fecha = Carbon::now('America/Mexico_City');
        $fecha->year($anioActual);

        foreach ($this->dataCompleta as $movimiento) {
            $movimiento['importe'] = doubleval($movimiento['importe']);
            $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $movimiento['cuentaId'])->whereIn('concepto_id', [15,16,17,18,38])
                ->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();
            $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();
            $importeMovimiento = $movimiento['importe'];
            if($interaccionCuentaConceptoPrincipal->tipo_interaccion == 'Presupuestal - Abono'){
                $importeMovimiento = $movimiento['importe'] + $movimiento['iva'];
            }

            $polizas = [
                [
                    'area' => $movimiento['codigoAreaResponsable'],
                    'tipo_poliza' => 'I',
                    'numero_poliza' =>  $this->numeroPoliza,
                    'fecha' => $movimiento['fechaRegistro'],
                    'cuenta' => $movimiento['codigoCuenta'],
                    'concepto' => $movimiento['descripcionCuenta'],
                    'total' => abs($importeMovimiento),
                    'mes' => $movimiento['mes'],
                    'descripcion' => $movimiento['observaciones'],
                    'evento' => $this->numeroEvento,
                    'tipo_interaccion' => $interaccionCuentaConceptoPrincipal->tipo_interaccion,
                    'validado' => false,
                    'categoria' => 'INGRESOS DEVENGADO',
                    'created_at' => $fecha,
                    'updated_at' => $fecha
                ]
            ];
            foreach ($interaccionCuentaCuentas as $key => $dataCuenta) {
                $importe = $movimiento['importe'];
                if(str_contains($dataCuenta['Descripcion_cuenta'], 'IVA')){
                    if($movimiento['iva'] > 0){
                        $importe = $movimiento['iva'];
                    }else{
                        //Saltamos la interacción con iva que no quieren que se le agregue el IVA, esto para no mostrarlo en la poliza
                        continue;
                    }
                }
                if($dataCuenta['tipo_interaccion'] == 'Contable - Cargo' || str_contains($dataCuenta['tipo_interaccion'], 'Presupuestal')){
                    $importe = $importe + $movimiento['iva'];
                }
                array_push($polizas, [
                    'area' => $movimiento['codigoAreaResponsable'],
                    'tipo_poliza' => 'I',
                    'numero_poliza' =>  $this->numeroPoliza,
                    'fecha' => $movimiento['fechaRegistro'],
                    'cuenta' => $dataCuenta['Codigo_cuenta'],
                    'concepto' => $dataCuenta['Descripcion_cuenta'],
                    'total' => $importe,
                    'mes' => $movimiento['mes'],
                    'descripcion' => $movimiento['observaciones'],
                    'evento' => $this->numeroEvento,
                    'tipo_interaccion' => $dataCuenta['tipo_interaccion'],
                    'validado' => false,
                    'categoria' => 'INGRESOS DEVENGADO',
                    'created_at' => $fecha,
                    'updated_at' => $fecha
                ]);
            }
            Poliza::insert($polizas);
        }
        $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total);


    }
}
