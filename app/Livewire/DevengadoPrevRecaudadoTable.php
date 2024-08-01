<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use DB;
use App\Clases\Column;
use App\Http\Controllers\BitacoraController;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Cuenta;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;

class DevengadoPrevRecaudadoTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $numeroPoliza;
    public $numeroEvento;
    public $numeroPolizaRemanente;

    public function render()
    {
        return view('livewire.devengado-prev-recaudado-table');
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

    public function delete($id)
    {
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
        if ($this->total + $registro['importe'] > $registro['montoEvento']) {
            $this->dispatch('mostrarMensaje', mensaje: 'Monto total del evento superado', tipo: 'error', tiempo: 3000);
            return;
        }
        $anioActual = Carbon::now()->year;
        $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $registro['cuentaId'])->where('concepto_id', '=', 14)->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();
        $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
            ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->where('Descripcion_cuenta', 'LIKE', '%(Por ejecutar)%')->first();


        $solvencia = DB::select('EXEC SolvenciaCuentaArea @area = ?, @cuenta = ?, @anio = ?, @mes = ?', array($registro['codigoAreaResponsable'], $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $registro['mes']));

        if ($solvencia[0]->Solvencia - $registro['importe'] < 0) {
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar insuficiente', tipo: 'error', tiempo: 3000);
            return;
            
        }

        $nuevoRegistro = [
            'id' => 0,
            'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
            'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
            'mes' => $registro['mes'],
            'movimiento' => 'DEVENGADO',
            'ejecutar' => $solvencia[0]->Solvencia,
            'importe' => $registro['importe'] + $registro['iva'],
            'disponibilidad' => $solvencia[0]->Solvencia - $registro['importe']
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

        $this->numeroEvento = $this->dataCompleta[0]['evento'];
        $polizasInicialesIngresosPorClasificar = Poliza::where('tipo_poliza', '=', 'I')->where('categoria', '=', 'INGRESOS POR CLASIFICAR')
            ->where('evento', '=', $this->numeroEvento)->get();
        $anioActual = Carbon::now()->year;
        $fecha = Carbon::now('America/Mexico_City');
        $fecha->year($anioActual);


        foreach ($this->dataCompleta as $movimiento) {
            $movimiento['importe'] = doubleval($movimiento['importe']);
            $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $movimiento['cuentaId'])->where('concepto_id', '=', 14)
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
                    'categoria' => 'INGRESOS DEVENGADO PREVIAMENTE RECAUDADO',
                    'created_at' => $fecha,
                    'updated_at' => $fecha
                ]
            ];
            foreach ($interaccionCuentaCuentas as $key => $dataCuenta) {
                $importe = $movimiento['importe'];
                if(str_contains($dataCuenta['Descripcion_cuenta'], 'IVA')){
                    $importe = $movimiento['iva'];
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
                    'categoria' => 'INGRESOS DEVENGADO PREVIAMENTE RECAUDADO',
                    'created_at' => $fecha,
                    'updated_at' => $fecha
                ]);
            }
            Poliza::insert($polizas);
        }


        $numerosPolizas = Poliza::select('numero_poliza')
            ->where('tipo_poliza', '=', 'IAUX')
            ->whereYear('fecha', '=', Carbon::now()->year)
            ->distinct()
            ->orderBy('numero_poliza')
            ->pluck('numero_poliza')
            ->toArray();
        sort($numerosPolizas);
        $this->numeroPolizaRemanente = (int)end($numerosPolizas) + 1;
        $totalRemanente = DB::select('EXEC ImporteTotalDevengadoPrevRecaudado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
        if ($totalRemanente > 0) {
            foreach ($polizasInicialesIngresosPorClasificar as $polizaInicial) {
                Poliza::create([
                    'area' => $polizaInicial->area,
                    'tipo_poliza' => 'IAUX',
                    'numero_poliza' =>  $this->numeroPolizaRemanente,
                    'fecha' => $movimiento['fechaRegistro'],
                    'cuenta' => $polizaInicial->cuenta,
                    'concepto' => $polizaInicial->concepto,
                    'total' => $totalRemanente,
                    'mes' => $polizaInicial->mes,
                    'descripcion' => $polizaInicial->descripcion,
                    'evento' => $this->numeroEvento,
                    'tipo_interaccion' => $polizaInicial->tipo_interaccion,
                    'validado' => false,
                    'categoria' => 'INGRESOS POR CLASIFICAR REMANENTE',
                    'created_at' => $fecha,
                    'updated_at' => $fecha
                ]);
            }
        } else {
            $this->numeroPolizaRemanente = 0;
        }
        $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total, $this->numeroPolizaRemanente);
    }
}
