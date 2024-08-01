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

    public function render(){
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
            Column::make('mes', 'Mes'),
            Column::make('movimiento', 'Movimiento'),
            Column::make('ppto', 'PPTO Devengado')->component('columns.importe'),
            Column::make('importe', 'Importe')->component('columns.importe'),
            Column::make('disponibilidad', 'Disponibilidad'),
           // Column::make('remanente', 'Remanente'),
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
        $interaccionCuentaConcepto = InteraccionCuentaConcepto::where('cuenta_id', '=', $registro['cuentaId'])
            ->whereIn('concepto_id', [19, 20, 21, 35, 39])
            ->where('tipo_interaccion', '=', 'Presupuestal - Abono')
            ->first();

        $interaccionCuentaCuenta = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConcepto->id)
            ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2', '=', 'interaccion_cuenta_conceptos.id')
            ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')
            ->where('Descripcion_cuenta', 'LIKE', '%(Devengado)%')
            ->first();
        
        $solvencia = DB::select('EXEC DevengadoCuentaArea @area = ?, @cuenta = ?, @anio = ?, @mes = ?', array($registro['codigoAreaResponsable'], $interaccionCuentaCuenta->Codigo_cuenta, $anioActual, $registro['mes']));
        if ($solvencia[0]->TotalDevengado - $registro['importe'] < 0) { 
            $this->dispatch('mostrarMensaje', mensaje: 'Monto devengado insuficiente', tipo: 'error', tiempo: 3000);
            return;
        }

        //revisar
        $nuevoRegistro = [
            'id' => 0,
            'area' => $registro['codigoAreaResponsable'] . ' ' . $registro['descripcionAreaResponsable'],
            'partida' => $registro['codigoCuenta'] . ' ' . $registro['descripcionCuenta'],
            'mes' => $registro['mes'],
            'movimiento' => 'RECAUDADO',
            'ppto' => $solvencia[0]->TotalDevengado,
            'importe' => $registro['importe'],
            'disponibilidad' => $solvencia[0]->TotalDevengado - $registro['importe'],
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
}
