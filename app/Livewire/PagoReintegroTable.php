<?php

namespace App\Livewire;
use Livewire\Attributes\On;
use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use App\Clases\Column;
use App\Http\Controllers\BitacoraController;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use Carbon\Carbon;
use DB;

class PagoReintegroTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $numeroPoliza;
    public $numeroEvento;

    public function render(){
        return view('livewire.pago-reintegro-table');
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
            Column::make('ppto', 'PPTO devengado')->component('columns.importe'),
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

        $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
        $this->total = $totalActualizado;
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

        $totalActualizado = array_sum(array_column($this->cacheData, 'importe'));
        $this->total = $totalActualizado;
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
        $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $registro['cuentaId'])
        ->whereIn('interaccion_cuenta_conceptos.concepto_id', [31, 32])
        ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')
        ->first();

        $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
        ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
        ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
        ->where('Descripcion_cuenta', 'LIKE', '%(Devengado)%') //VERIFICAR SOBRE CUAL SE SACARA LA SOLVENCIA
        ->first();

        $solvencia = DB::select('EXEC SolvenciaPagoReintegroCuentaArea @area = ?, @cuenta = ?, @anio = ?, @mes = ?,  @evento = ?', array($registro['codigoAreaResponsable'], $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $registro['mes'], $registro['evento']));
        if ($solvencia[0]->Total - $registro['importe'] < 0) {
            $this->dispatch('mostrarMensaje', mensaje: 'Monto devengado insuficiente', tipo: 'error', tiempo: 3000);
            return;
        }

        $nuevoRegistro = [
            'id' => 0,
            'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
            'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
            'mes' => $registro['mes'],
            'movimiento' => 'PAGO DE REINTEGRO',
            'ppto' => 'ppto',//$solvencia[0]->Total,
            'importe' => $registro['importe'],
            'disponibilidad' => 'disponibilidad'//$solvencia[0]->Total - $registro['importe'],
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

    #[On('finalizar-registros')]
    public function finalizarRegistros()
    {
        if (empty($this->cacheData)) {
            $this->dispatch('mostrarMensaje', mensaje: 'Tabla sin registros', tipo: 'error', tiempo: 3000);
            return;
        }

        try{
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
            $anioActual = Carbon::now()->year;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);

            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar un pago de devolución con evento: '.$this->numeroEvento, request());

            DB::beginTransaction();

            foreach ($this->dataCompleta as $movimiento) {
                $movimiento['importe'] = doubleval($movimiento['importe']);
                $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $movimiento['cuentaId'])
                    ->whereIn('concepto_id', [31, 32])
                    ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')
                    ->first();
                $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                    ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                    ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();
                
                $polizas = [
                    [
                        'area' => $movimiento['codigoAreaResponsable'],
                        'tipo_poliza' => 'I',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $movimiento['fechaRegistro'],
                        'cuenta' => $movimiento['codigoCuenta'],
                        'concepto' => $movimiento['descripcionCuenta'],
                        'total' => abs($movimiento['importe']),
                        'mes' => $movimiento['mes'],
                        'descripcion' => $movimiento['observaciones'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $interaccionCuentaConceptoPrincipal->tipo_interaccion,
                        'validado' => false,
                        'categoria' => 'INGRESOS PAGO REINTEGRO',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]
                ];

                foreach ($interaccionCuentaCuentas as $key => $dataCuenta) {
                    array_push($polizas, [
                        'area' => $movimiento['codigoAreaResponsable'],
                        'tipo_poliza' => 'I',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $movimiento['fechaRegistro'],
                        'cuenta' => $dataCuenta['Codigo_cuenta'],
                        'concepto' => $dataCuenta['Descripcion_cuenta'],
                        'total' => $movimiento['importe'],
                        'mes' => $movimiento['mes'],
                        'descripcion' => $movimiento['observaciones'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $dataCuenta['tipo_interaccion'],
                        'validado' => false,
                        'categoria' => 'INGRESOS PAGO REINTEGRO',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }
                Poliza::insert($polizas);
                DB::commit();
            }
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total);
        }catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en pago de devolución: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}
