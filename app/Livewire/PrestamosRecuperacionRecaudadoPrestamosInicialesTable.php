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
use App\Models\Cuenta;
use App\Enums\EstatusEvento;
class PrestamosRecuperacionRecaudadoPrestamosInicialesTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $totalDisponible = 0;
    public $polizasFinales = [];



    public function render()
    {
        return view('livewire.prestamos-recuperacion-recaudado-prestamosIniciales-table');
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
            Column::make('mes', 'Mes'),
            Column::make('partida', 'Partida'),
            Column::make('cuentaBanco', 'Cuenta de banco'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('movimiento', 'Movimiento'),
            Column::make('pttoEjecutar', 'PPTO')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {

        try{
            if($this->verificarPresupuesto($registro)){    
                $nuevoRegistro = [
                    'id' => 0,
                    'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                    'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                    'cuentaBanco' => $registro['codigoCuentaBanco'] . ' ' . $registro['descripcionCuentaBanco'],
                    'mes' => $registro['mes'],
                    'movimiento' => 'RECUPERACION RECAUDADO PRESTAMOS INICIALES', 
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
               $this->dispatch('cambioTotal', total: $this->total);
            }
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en recaudado préstamos iniciales del capítulo 7000: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function verificarPresupuesto($registro)
    {
        $solvencia = $registro['pttoEjecutar'];
        $this->totalDisponible = $solvencia - $registro['importe'];
        $totalImportes = 0;

        foreach ($this->cacheData as $movimiento){
            if(str_contains($movimiento['area'], $registro['codigoAreaResponsable']) && $movimiento['mes'] == $registro['mes'] && str_contains($movimiento['partida'], $registro['codigoCuenta'])){
                $totalImportes += $movimiento['importe'];
            }
        }

        if($totalImportes > 0){
            $this->totalDisponible = $solvencia - $totalImportes - $registro['importe'];
        }

        if($this->totalDisponible < 0){
            $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto por ejecutar insuficiente', tipo: 'warning', tiempo: 3000);
            return false;
        }
        return true;
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
                        'cuentaBanco' => $registro['cuentaBancoId'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe'],
                        'pttoEjecutar' => $registro['pttoEjecutar']
                    ];
     
                    unset($this->dataCompleta[$key]);
                    $this->dataCompleta = array_values($this->dataCompleta );
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
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en recaudado préstamos iniciales del capítulo 7000: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function delete($id)
    {
        try{
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
                    $this->dataCompleta = array_values($this->dataCompleta );
                    break;
                }
            }
                
            $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
            $this->total = $totalActualizado;
        }catch(\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en recaudado préstamos iniciales del capítulo 7000: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function recalcularDisponibilidad($id)
    {   
        $mesSeleccionado = "";
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
        foreach($this->cacheData as $key => $movimiento)
        {
            if($movimiento['id'] != $id && str_contains($movimiento['area'], $datosSeleccionado['codigoArea']) && str_contains($movimiento['partida'], $datosSeleccionado['codigoCuenta']) && $movimiento['mes'] == $mesSeleccionado)
            {
                if($totalImportes == 0){
                    $movimiento['disponibilidad'] = $movimiento['pttoEjecutar'] - $movimiento['importe'];
                    $totalImportes += $movimiento['importe'];
                }else{
                    $movimiento['disponibilidad'] = $movimiento['pttoEjecutar'] - $totalImportes - $movimiento['importe'];
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

        try{
            $idUsuarioRegistrante = Auth::id();
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
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar un recaudado préstamos inicales del capítulo 7000: '.$this->numeroEvento, request());
            DB::beginTransaction();
            // dd($this->dataCompleta);
            $importeTotalCortoPlazo = 0;
            $importeTotalMedioPlazo = 0;
            $importeTotal = 0;
            foreach ($this->dataCompleta as $movimiento)
            {
                
                $plazo = explode(')', explode('(', $movimiento['descripcionCuenta'])[1])[0];


                if($plazo == 'Corto plazo'){
                    $importeTotalCortoPlazo = $importeTotalCortoPlazo + $movimiento['importe'];
                     
                }
                elseif($plazo == 'Medio plazo'){
                    $importeTotalMedioPlazo = $importeTotalMedioPlazo + $movimiento['importe'];
                }
                $movimiento['importe'] = doubleval($movimiento['importe']);

                $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('concepto_id', [10096])
                ->where('tipo_interaccion', '=', 'Presupuestal - Abono')->first();

                $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                    ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                    ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();

                $interaccionCuentaCuentasFiltradas = [];
                foreach ($interaccionCuentaCuentas as $cuenta) {

                    if ($cuenta['tipo_interaccion'] == 'Contable - Cargo') {
                        if ($cuenta['Codigo_cuenta'] == $movimiento['codigoCuentaBanco']) {
                            $interaccionCuentaCuentasFiltradas[] = $cuenta;
                            continue;
                        }
                    } 
                    
                    else if ($cuenta['tipo_interaccion'] == 'Contable - Cargo') {
                        if ($cuenta['Codigo_cuenta'] != $movimiento['codigoCuentaBanco']) {
                            continue;
                        }
                    } else if($cuenta['tipo_interaccion'] == 'Contable - Abono'){
                                continue;
                            
                        } else {
                        if ($cuenta['tipo_interaccion'] != 'Contable - Cargo') {
                            $interaccionCuentaCuentasFiltradas[] = $cuenta;
                        }
                    }
                }


                $importeTotal = $importeTotal + $movimiento['importe'];
                $interaccionCuentaCuentas = $interaccionCuentaCuentasFiltradas;
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
                        'categoria' => 'RECUPERACION RECAUDADO PRESTAMOS INICIALES',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]
                ];
                
                foreach ($interaccionCuentaCuentas as $key => $dataCuenta) {
                    $total = $movimiento['importe'];
                    if($dataCuenta['tipo_interaccion'] == 'Contable - Abono'){
                        $total = $movimiento['importe'];
                    }
                    array_push($polizas, [
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $movimiento['codigoAreaResponsable'],
                        'tipo_poliza' => 'D',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $movimiento['fechaAfectacion'],
                        'cuenta' => $dataCuenta['Codigo_cuenta'],
                        'concepto' => $dataCuenta['Descripcion_cuenta'],
                        'total' => $total,
                        'mes' => $movimiento['mes'],
                        'descripcion' => $movimiento['observaciones'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $dataCuenta['tipo_interaccion'],
                        'validado' => false,
                        'estatus_evento' => EstatusEvento::ACTIVO->value,
                        'categoria' => 'RECUPERACION RECAUDADO PRESTAMOS INICIALES',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }


                $this->polizasFinales = array_merge($this->polizasFinales, $polizas);
                


            }

            $cuentaPrincipal = Cuenta::where('Codigo_cuenta', '=', '8.1.5.4.1.7.1.02.01')->first();
            array_push($this->polizasFinales, [
                'idUsuarioRegistrante' => $idUsuarioRegistrante,
                'area' => $movimiento['codigoAreaResponsable'],
                'tipo_poliza' => 'D',
                'numero_poliza' =>  $this->numeroPoliza,
                'fecha' => $movimiento['fechaAfectacion'],
                'cuenta' => $cuentaPrincipal['Codigo_cuenta'],
                'concepto' => $cuentaPrincipal['Descripcion_cuenta'],
                'total' => $importeTotal,
                'mes' => $movimiento['mes'],
                'descripcion' => $movimiento['observaciones'],
                'evento' => $this->numeroEvento,
                'tipo_interaccion' => 'Presupuestal - Abono',
                'validado' => false,
                'estatus_evento' => EstatusEvento::ACTIVO->value,
                'categoria' => 'RECUPERACION RECAUDADO PRESTAMOS INICIALES',
                'created_at' => $fecha,
                'updated_at' => $fecha
            ]);



            Poliza::insert($this->polizasFinales);
            DB::commit();


            $this->dispatch('consultar-registro',$this->numeroEvento, $this->numeroPoliza, $this->total);
        }catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en recuperacion recaudado préstamos iniciales del capítulo 7000: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }


    public function changeState($value)
    {

    }


}