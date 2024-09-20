<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use App\Clases\Column;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Http\Controllers\BitacoraController;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Log;
use DB;

class IngresosRecaudadoTable extends Tabla
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
        return view('livewire.ingresos-recaudado-table');
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
            Column::make('cuentaPago', 'Cuenta de pago'),
            Column::make('mes', 'Mes'),
            Column::make('movimiento', 'Movimiento'),
            Column::make('ppto', 'PPTO Devengado')->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    public function edit($id)
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
            // Recalculamos los totales solo después de eliminar el registro
            $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al editar en recaudado: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
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
            // Recalculamos los totales solo después de eliminar el registro
            $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
            $this->total = $totalActualizado;
            $this->dispatch('cambioTotal', total: $totalActualizado);
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al eliminar en recaudado: '. $th->getMessage());
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
                    'codigoCuenta' => $registro['codigoCuenta'],
                    'mes' => $registro['mes'],
                    'evento' => $registro['evento']
                ];
            }
        }

        $totalImportes = 0;
        foreach($this->cacheData as $key => $movimiento) {
            if($movimiento['id'] != $id && str_contains($movimiento['area'], $datosSeleccionado['codigoArea']) && str_contains($movimiento['partida'], $datosSeleccionado['codigoCuenta']) && $movimiento['mes'] == $datosSeleccionado['mes'] && $movimiento['evento'] == $datosSeleccionado['evento']) {
                if($totalImportes == 0){
                    $movimiento['disponibilidad'] = $movimiento['ppto'] - $movimiento['importe'];
                    $totalImportes += $movimiento['importe'];
                }else{
                    $movimiento['disponibilidad'] = $movimiento['ppto'] - $totalImportes - $movimiento['importe'];
                    $totalImportes += $movimiento['importe'];
                }
                $this->cacheData[$key] = $movimiento;
            }
        }
    }

    public function changeState($value)
    {
    }

    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    {
        try {
            //code...
            if ($this->total + $registro['importe'] > $registro['montoEvento']) {
                $this->dispatch('mostrarMensaje', mensaje: 'Monto total del evento superado', tipo: 'error', tiempo: 3000);
                return;
            }
            $anioActual = Carbon::now()->year;
            $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $registro['cuentaId'])
                ->whereIn('concepto_id', [19, 20, 21, 35, 39])
                ->where('tipo_interaccion', '=', 'Presupuestal - Abono')
                ->first();
    
            $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
                ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
                ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
                ->where('Descripcion_cuenta', 'LIKE', '%(Devengado)%')
                ->first();
    
            $solvencia = DB::select('EXEC DevengadoCuentaArea @area = ?, @cuenta = ?, @anio = ?, @mes = ?, @evento = ?', array($registro['codigoAreaResponsable'], $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $registro['mes'], $registro['evento']));
            
            $totalDisponible = $solvencia[0]->TotalDevengado - $registro['importe'];
            $totalImportes = 0;
            foreach ($this->cacheData as $movimiento) {
                if(str_contains($movimiento['area'], $registro['codigoAreaResponsable']) && str_contains($movimiento['partida'], $registro['codigoCuenta']) && $movimiento['mes'] == $registro['mes'] && $movimiento['evento'] == $registro['evento']) {
                    $totalImportes += $movimiento['importe'];
                }
            }
    
            if($totalImportes > 0){
                $totalDisponible = $solvencia[0]->TotalDevengado - $totalImportes - $registro['importe'];
            }
    
            if($totalDisponible < 0){
                $this->dispatch('mostrarMensaje', mensaje: 'Monto devengado insuficiente', tipo: 'error', tiempo: 3000);
                return;
            }
    
            $nuevoRegistro = [
                'id' => 0,
                'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
                'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
                'cuentaPago' => $registro['codigoCuentaPago'] . ' ' . $registro['descripcionCuentaPago'],
                'mes' => $registro['mes'],
                'evento' => $registro['evento'],
                'movimiento' => 'RECAUDADO',
                'ppto' => $solvencia[0]->TotalDevengado,
                'importe' => $registro['importe'],
                'disponibilidad' => $totalDisponible,
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
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al agregar registro en recaudado: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    #[On('finalizar-registros')]
    public function finalizarRegistros()
    {
        if (empty($this->cacheData)) {
            $this->dispatch('mostrarMensaje', mensaje: 'Tabla sin registros', tipo: 'error', tiempo: 3000);
            return;
        }

        try {

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
            $polizasInicialesIngresosDevengado = Poliza::where('tipo_poliza', '=', 'I')->where('categoria', '=', 'INGRESOS DEVENGADO')
                ->where('evento', '=', $this->numeroEvento)->get();
            $anioActual = Carbon::now()->year;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);
            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar un ingreso recaudado con evento: '.$this->numeroEvento, request());

            DB::beginTransaction();
    

            foreach ($this->dataCompleta as $movimiento) {
                $movimiento['importe'] = doubleval($movimiento['importe']);
                $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $movimiento['cuentaId'])
                    ->whereIn('concepto_id', [19, 20, 21, 35, 39])
                    ->where('tipo_interaccion', '=', 'Presupuestal - Abono')
                    ->first();
                $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                    ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                    ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();

                // Inicializar un nuevo arreglo para almacenar los resultados filtrados
                $interaccionCuentaCuentasFiltradas = [];

                // Recorrer las cuentas de interaccionCuentaCuentas
                foreach ($interaccionCuentaCuentas as $cuenta) {
                    if ($cuenta['tipo_interaccion'] == 'Contable - Cargo') {
                        if ($cuenta['Codigo_cuenta'] == $movimiento['codigoCuentaPago']) {
                            $interaccionCuentaCuentasFiltradas[] = $cuenta; // Agregar a la lista filtrada
                            continue; // Salir del loop interno cuando se encuentra una coincidencia
                        }
                    } else {
                        // Si no es 'Contable - Cargo', mantener el registro
                        $interaccionCuentaCuentasFiltradas[] = $cuenta;
                    }
                }

                // Reemplazar el arreglo original con el filtrado
                $interaccionCuentaCuentas = $interaccionCuentaCuentasFiltradas;
                $polizas = [
                    [
                        'area' => $movimiento['codigoAreaResponsable'],
                        'tipo_poliza' => 'I',
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
                        'estatus_evento' => true,
                        'categoria' => 'INGRESOS RECAUDADO',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]
                ];
                foreach ($interaccionCuentaCuentas as $key => $dataCuenta) {
                    array_push($polizas, [
                        'area' => $movimiento['codigoAreaResponsable'],
                        'tipo_poliza' => 'I',
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
                        'estatus_evento' => true,
                        'categoria' => 'INGRESOS RECAUDADO',
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

            $polizaRecaudadoRecaudado = Poliza::where('tipo_poliza', '=', 'I')
                ->where(function($query){
                    $query->where('categoria', '=', 'INGRESOS RECAUDADO')
                    ->orwhere('categoria', '=', 'INGRESOS COBRO ESPECIE');
                })
                ->where('evento', '=', $this->numeroEvento)->where('concepto', 'LIKE', '%(Recaudado)%')->get();
            $totalRemanente = DB::select('EXEC ImporteTotalRecaudado @evento = ?', array($this->numeroEvento))[0]->MontoDelEvento;
            if ($totalRemanente > 0) {
                // Se suma el total de los movimientos agrupados por conceptos
                foreach ($polizasInicialesIngresosDevengado as $polizaImporte) {
                    $clave = $polizaImporte->cuenta . '-' . $polizaImporte->concepto;
                    if (isset($resultado[$clave])) {
                        $resultado[$clave]['total'] += $polizaImporte['total'];
                    } else {
                        // Si la clave no existe, agregar el nuevo depósito al resultado
                        $resultado[$clave] = [
                            'area' => $polizaImporte->area,
                            'tipo_poliza' => 'IAUX',
                            'numero_poliza' =>  $this->numeroPolizaRemanente,
                            'fecha' => $movimiento['fechaAfectacion'],
                            'cuenta' => $polizaImporte->cuenta,
                            'concepto' => $polizaImporte->concepto,
                            'total' => $polizaImporte['total'],
                            'mes' => $polizaImporte->mes,
                            'descripcion' => $polizaImporte->descripcion,
                            'evento' => $this->numeroEvento,
                            'tipo_interaccion' => $polizaImporte->tipo_interaccion,
                            'validado' => false,
                            'estatus_evento' => false,
                            'categoria' => 'INGRESOS DEVENGADO REMANENTE RECAUDADO',
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ];
                    }
                }

                foreach ($resultado as $polizaInicial) { //devengado
                    $total = $polizaInicial['total'];
                    foreach ($polizaRecaudadoRecaudado as $polizaRecaudado) {    //recaudado
                        $conceptoGeneral = explode('(', $polizaRecaudado->concepto);

                        if (str_contains($polizaInicial['concepto'], rtrim($conceptoGeneral[0])) !== false && $conceptoGeneral[1] == 'Recaudado)') {
                            $total = $total - $polizaRecaudado['total'];
                            
                            if(number_format($total, 2) < 0){
                                DB::rollBack();
                                $this->dispatch('mostrarMensaje', mensaje: 'Se excedió el monto $' .number_format($polizaInicial['total'], 2). ' de la cuenta de ' . $polizaInicial['concepto']. ' con IVA', tipo: 'error', tiempo: 3000);
                                return;
                            }
                        }
                    }
                    if(number_format($total, 2) > 0){
                        // se creal la poliza con los importes sumados
                        Poliza::create([
                            'area' => $polizaInicial['area'],
                            'tipo_poliza' => 'IAUX',
                            'numero_poliza' =>  $this->numeroPolizaRemanente,
                            'fecha' => $movimiento['fechaAfectacion'],
                            'cuenta' => $polizaInicial['cuenta'],
                            'concepto' => $polizaInicial['concepto'],
                            'total' => $total,
                            'mes' => $polizaInicial['mes'],
                            'descripcion' => $polizaInicial['descripcion'],
                            'evento' => $this->numeroEvento,
                            'tipo_interaccion' => $polizaInicial['tipo_interaccion'],
                            'validado' => false,
                            'estatus_evento' => false,
                            'categoria' => 'INGRESOS DEVENGADO REMANENTE RECAUDADO',
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ]);
                    }
                }
            } else {
                $this->numeroPolizaRemanente = 0;
            }
            $importeTotalEvento = DB::select('EXEC ImporteTotalRecaudado @evento = ?', [$this->numeroEvento]);
            if ($importeTotalEvento[0]->MontoDelEvento == 0) {
                Poliza::where('evento', '=', $this->numeroEvento)
                    ->whereIn('categoria', ['INGRESOS DEVENGADO', 'INGRESOS RECAUDADO', 'INGRESOS COBRO ESPECIE'])
                    ->update(['estatus_evento' => false]);
            }
            DB::commit();
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total, $this->numeroPolizaRemanente);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en Recaudado: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}
