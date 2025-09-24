<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use App\Models\Poliza;
use Illuminate\Database\Eloquent\Builder;
use App\Clases\Column;
use App\Http\Controllers\BitacoraController;
use Carbon\Carbon;
use DB;
use Log;
use App\Models\Cuenta;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use App\Models\CodigoDepartamento;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use App\Enums\EstatusEvento;

class DepositosBancosTable extends Tabla
{
    public $cacheData = [];
    public $dataCompleta = [];
    public $perPage = 6;
    public $total = 0;
    public $numeroPoliza;
    public $numeroEvento;

    public function render()
    {
        return view('livewire.depositos-bancos-table');
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
            Column::make('descripcion', 'Descripción'),
            Column::make('mes', 'Mes'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('id', 'Acciones')->component('columns.accionesIngresos')
        ];
    }

    public function edit($id)
    {
        try {
            //code...
            foreach ($this->cacheData as $key => $registro) {
                if ($registro['id'] == $id) {
                    $datosRegistro = [
                        'codigoCuenta' => $registro['cuenta'],
                        'descripcion' => $registro['descripcion'],
                        'mes' => $registro['mes'],
                        'importe' => $registro['importe']
                    ];
    
                    unset($this->cacheData[$key]);
                    $this->cacheData = array_values($this->cacheData);
                    $this->dispatch('llenar-formulario', $datosRegistro);
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
            Log::error('Ocurrió un error al editar en depósitos en bancos: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al editar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function delete($id)
    {
        try {
            //code...
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
            Log::error('Ocurrió un error al eliminar en depósitos en bancos: '. $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al eliminar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function changeState($value) {}


    #[On('agregar-registro')]
    public function agregarRegistro($registro)
    { 
        try {
            $solvencia = DB::select('exec SolvenciaCuentasContables @cuenta = ?, @anio = ?', array('1.1.1.1.01.01', Carbon::now()->year));

            if ($solvencia[0]->Solvencia - ($this->total + $registro['importe']) < 0) {
                $this->dispatch('mostrarMensaje', mensaje: 'Presupuesto en Caja General insuficiente', tipo: 'error', tiempo: 3000);
                return;
            }
            $nuevoRegistro = [
                'id' => 0,
                'cuenta' => $registro['codigoCuenta'],
                'descripcion' => $registro['descripcionCuenta'],
                'mes' => $registro['mes'],
                'importe' => $registro['importe']
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
            Log::error('Ocurrió un error al agregarRegistro en depósitos en bancos: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al agregar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
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
            $idUsuarioRegistrante = Auth::id();
            $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) as numero_poliza')
                ->where('tipo_poliza', '=', 'I')
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
            if (!empty($numerosEvento)) {
                $this->numeroEvento = (int)end($numerosEvento) + 1;
            } else {
                $this->numeroEvento = 1;
            }

            $bitacora = new BitacoraController();
            $bitacora->bitacora('finalizarRegistros', 'registro o intentó registrar un depósito en bancos con evento: ' . $this->numeroEvento, request());

            DB::beginTransaction();

            $anioActual = Carbon::now()->year;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);
            foreach ($this->dataCompleta as $movimiento) {

                $responsable = CodigoDepartamento::find($movimiento['codigoArea']);
                $interaccionCuentaConceptoIzquierda = InteraccionCuentaConcepto::where('cuenta_id', '=', $movimiento['cuentaId'])->where('concepto_id', '=', 13)->first();
                $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoIzquierda->id)->first();
                $interaccionCuentaConceptoDerecha = InteraccionCuentaConcepto::where('id', '=', $interaccionCuentaCuenta->id_interaccion_concepto_cuenta_2)->first();
                $cuentaDerecha = Cuenta::find($interaccionCuentaConceptoDerecha->cuenta_id);

                $poliza = new Poliza([
                    'idUsuarioRegistrante' => $idUsuarioRegistrante,
                    'area' => $responsable->Codigo_completo,
                    'tipo_poliza' => 'I',
                    'numero_poliza' =>  $this->numeroPoliza,
                    'fecha' => $movimiento['fechaAfectacion'],
                    'cuenta' => $movimiento['codigoCuenta'],
                    'concepto' => $movimiento['descripcionCuenta'],
                    'total' => abs($movimiento['importe']),
                    'mes' => $movimiento['mes'],
                    'descripcion' => $movimiento['observaciones'],
                    'evento' => $this->numeroEvento,
                    'tipo_interaccion' => $interaccionCuentaConceptoIzquierda->tipo_interaccion,
                    'validado' => false,
                    'estatus_evento' => EstatusEvento::FINALIZADO->value,
                    'categoria' => 'INGRESOS DEPOSITOS EN BANCOS',
                    'created_at' => $fecha,
                    'updated_at' => $fecha
                ]);
                $polizaDerecha = new Poliza([
                    'idUsuarioRegistrante' => $idUsuarioRegistrante,
                    'area' => $responsable->Codigo_completo,
                    'tipo_poliza' => 'I',
                    'numero_poliza' =>  $this->numeroPoliza,
                    'fecha' => $movimiento['fechaAfectacion'],
                    'cuenta' => $cuentaDerecha->Codigo_cuenta,
                    'concepto' => $cuentaDerecha->Descripcion_cuenta,
                    'total' => abs($movimiento['importe']),
                    'mes' => $movimiento['mes'],
                    'descripcion' => $movimiento['observaciones'],
                    'evento' => $this->numeroEvento,
                    'tipo_interaccion' => $interaccionCuentaConceptoDerecha->tipo_interaccion,
                    'validado' => false,
                    'estatus_evento' => EstatusEvento::FINALIZADO->value,
                    'categoria' => 'INGRESOS DEPOSITOS EN BANCOS',
                    'created_at' => $fecha,
                    'updated_at' => $fecha
                ]);
                $poliza->save();
                $polizaDerecha->save();
                DB::commit();
            }
            $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Ocurrió un error al finalizarRegistro en depósitos en bancos: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }
}
