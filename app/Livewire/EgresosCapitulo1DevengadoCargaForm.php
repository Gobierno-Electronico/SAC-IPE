<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\BitacoraController;
use App\Models\Cuenta;
use App\Models\CodigoDepartamento;
use App\Models\Poliza;
use App\Models\InteraccionCuentaCuenta;
use App\Models\InteraccionCuentaConcepto;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Log;
use DB;

use function PHPUnit\Framework\isNull;

class EgresosCapitulo1DevengadoCargaForm extends Component
{
    use WithFileUploads;
    public $fechaAfectacion = "";
    public $archivo;

    public $PTTOEjecutar = 0;
    public $consultarRegistro = false;
    public $numeroEvento;
    public $numeroPoliza;
    public $total;
    public $observaciones = '';

    public function render()
    {
        try{
            return view('livewire.egresos-capitulo1-devengadoCarga-form');
        }catch (\Throwable $th) {
            Log::error('Ocurrió un error al renderizar devengado del capítulo 1000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cargarDevengado()
    {
        try{
            $datosExcelAsociados = $this->leerArchivoExcel();
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('cargarDevengado', 'cargó o intentó cargar el devengado del capítulo 1000 de egresos', request());
            DB::beginTransaction();
            $cuentasFaltantesPlanCuentas = [];
            $cuentasEnLaGuiaFaltantes = [];
            $anioActual = Carbon::now()->year;
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);

            $numerosPolizas = Poliza::select('numero_poliza')
                ->where('tipo_poliza', '=', 'E')
                ->whereYear('fecha', '=', $anioActual)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();

            $numerosEvento = Poliza::select('evento')
                ->distinct()
                ->whereYear('fecha', '=', $anioActual)
                ->orderBy('evento')
                ->pluck('evento')
                ->toArray();

            $ultimoNumero = end($numerosPolizas);
            $this->numeroPoliza = ($ultimoNumero) ? $ultimoNumero + 1 : 1;
            $this->numeroEvento = end($numerosEvento) + 1;

            $polizas = [];
            foreach ($datosExcelAsociados as $dato) {
                
                if ($this->observaciones == '') {
                    $this->observaciones = $dato['CONCEPTO'];
                }

                $cuenta = Cuenta::where("Codigo_cuenta", $dato["CUENTA"])->first();
                if (!$cuenta) {
                    $codigosExistentesPlan = array_column($cuentasFaltantesPlanCuentas, 'Codigo_cuenta');
                    if (!in_array($dato["CUENTA"], $codigosExistentesPlan)) {
                        $cuentasFaltantesPlanCuentas[] = [
                            "Codigo_cuenta" => $dato["CUENTA"],
                            "Descripcion_cuenta" => $dato["DESCRIPCION"]
                        ];
                    }
                    continue;
                }

                if(str_contains($dato["DESCRIPCION"], "(Devengado)")){

                    $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $cuenta->id)
                        ->where('concepto_id', '=', 10102)
                        ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();
    
                    if (!$interaccionCuentaConceptoPrincipal) {
                        $codigosExistentes = array_column($cuentasEnLaGuiaFaltantes, 'Codigo_cuenta');
    
                        if(!in_array($dato['CUENTA'], $codigosExistentes)){
                            $cuentasEnLaGuiaFaltantes[] = [
                                "Codigo_cuenta" => $dato["CUENTA"],
                                "Descripcion_cuenta" => $dato["DESCRIPCION"]
                            ];
                            
                        }
                        continue;
                    }
    
                    $interaccionCuentaCuentas = InteraccionCuentaCuenta::where('id_interaccion_concepto_cuenta_1', '=', $interaccionCuentaConceptoPrincipal->id)
                        ->join('interaccion_cuenta_conceptos', 'interaccion_cuenta_conceptos.id', '=', 'interaccion_cuenta_cuentas.id_interaccion_concepto_cuenta_2')
                        ->join('cuentas', 'cuentas.id', '=', 'interaccion_cuenta_conceptos.cuenta_id')->get()->toArray();
    
                    $interaccionCuentaCuentasFiltradas = [];
                    foreach ($interaccionCuentaCuentas as $cuenta) {
                        if ($cuenta['tipo_interaccion'] != 'Contable - Abono') {
                            $interaccionCuentaCuentasFiltradas[] = $cuenta;
                        }
                    }

                    $interaccionCuentaCuentas = $interaccionCuentaCuentasFiltradas;

                    array_push($polizas, [
                            'area' => $dato['AREA EJECUTORA'],
                            'tipo_poliza' => 'E',
                            'numero_poliza' =>  $this->numeroPoliza,
                            'fecha' => $this->fechaAfectacion,
                            'cuenta' => $dato['CUENTA'],
                            'concepto' => $dato['DESCRIPCION'],
                            'total' => floatval(str_replace([',', '$', ' '], ['', '', ''], $dato['CARGO'])),
                            'mes' => $dato['MES'],
                            'descripcion' => $dato['CONCEPTO'],
                            'evento' => $this->numeroEvento,
                            'tipo_interaccion' => $interaccionCuentaConceptoPrincipal->tipo_interaccion,
                            'validado' => false,
                            'estatus_evento' => true,
                            'categoria' => 'EGRESOS DEVENGADO CAPITULO 1',
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                    ]);

                    foreach ($interaccionCuentaCuentas as $dataCuenta){
                        array_push($polizas, [
                            'area' => $dato['AREA EJECUTORA'],
                            'tipo_poliza' => 'E',
                            'numero_poliza' =>  $this->numeroPoliza,
                            'fecha' => $this->fechaAfectacion,
                            'cuenta' => $dataCuenta['Codigo_cuenta'],
                            'concepto' => $dataCuenta['Descripcion_cuenta'],
                            'total' => floatval(str_replace([',', '$', ' '], ['', '', ''], $dato['CARGO'])),
                            'mes' => $dato['MES'],
                            'descripcion' => $dato['CONCEPTO'],
                            'evento' => $this->numeroEvento,
                            'tipo_interaccion' => $dataCuenta['tipo_interaccion'],
                            'validado' => false,
                            'estatus_evento' => true,
                            'categoria' => 'EGRESOS DEVENGADO CAPITULO 1',
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ]);
                    }
                }else{
                    array_push($polizas, [
                        'area' => $dato['AREA EJECUTORA'],
                        'tipo_poliza' => 'E',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $this->fechaAfectacion,
                        'cuenta' => $dato['CUENTA'],
                        'concepto' => $dato['DESCRIPCION'],
                        'total' => floatval(str_replace([',', '$', ' '], ['', '', ''], $dato['ABONO'])),
                        'mes' => $dato['MES'],
                        'descripcion' => $dato['CONCEPTO'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => 'Contable - Abono',
                        'validado' => false,
                        'estatus_evento' => true,
                        'categoria' => 'EGRESOS DEVENGADO CAPITULO 1',
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }
            }

            if (!empty($cuentasFaltantesPlanCuentas)) {
                $mensajeError = "Cuentas Faltantes en el plan:<br>";
                foreach ($cuentasFaltantesPlanCuentas as $cuenta) {
                    $mensajeError .= "Código: {$cuenta['Codigo_cuenta']}, Descripción: {$cuenta['Descripcion_cuenta']}<br>";
                }

                $this->dispatch('mostrarMensaje', mensaje: $mensajeError, tipo: 'error', tiempo: 5000);
            }

            if (empty($cuentasEnLaGuiaFaltantes)) {
                collect($polizas)->chunk(120)->each(function ($chunk) {
                    Poliza::insert($chunk->toArray());
                }); // divide $polizas en partes pequeñas (chunks) de 120 elementos. Esto evita la sobrecarga de memoria al hacer inserciones en la base.

                DB::commit();
                $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, 1000);
            } else {
                $mensajeError = "Cuentas Faltantes en la guía contabilizadora:<br>";
                foreach ($cuentasEnLaGuiaFaltantes as $cuenta) {
                   $mensajeError .= "Código: {$cuenta['Codigo_cuenta']} - Descripción: {$cuenta['Descripcion_cuenta']}<br>";
                }

                $this->dispatch('mostrarMensaje', mensaje: $mensajeError, tipo: 'error', tiempo: 5000);
            }

        }catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ocurrió un error al cargar devengado del capítulo 1000: '. $e->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar el registro, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }


    public function leerArchivoExcel()
    {
        try{
            $this->validate([
                'archivo' => 'required|mimes:xlsx',
                'fechaAfectacion' => 'required'
            ], [
                'archivo.required' => "Debes seleccionar al menos un archivo.",
                'archivo.mimes' => "El archivo debe ser de tipo XLSX.",
                'fechaAfectacion.required' => "La fecha de afectación es requerida."
            ]);

            $path = $this->archivo->store('temp');
            $filePath = storage_path('app/' . $path);

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            $encabezadosEsperados = ['AREA EJECUTORA', 'CUENTA', 'DESCRIPCION', 'CONCEPTO', 'MES', 'CARGO', 'ABONO'];

            // Obtener encabezados del archivo y filtrar vacíos
            $encabezadosArchivo = array_filter(array_map('trim', $data[0]), fn($valor) => $valor !== "");

            // Reindexar array para evitar problemas de índices
            $encabezadosArchivo = array_values($encabezadosArchivo);

            $diferencias = array_diff_assoc($encabezadosArchivo, $encabezadosEsperados);

            if (!empty($diferencias)) {
                Storage::delete($path);
                $mensajeError = "Los siguientes encabezados no coinciden:\n";

                foreach ($diferencias as $indice => $valorIncorrecto) {
                    $mensajeError .= "- Esperado: '{$encabezadosEsperados[$indice]}' → Recibido: '{$valorIncorrecto}'\n";
                }

                $this->dispatch('mostrarMensaje', mensaje: $mensajeError, tipo: 'error', tiempo: 5000);
                return;
            }

            $indicesValoresNumericos = range(5, 6);
            $errores = [];

            foreach ($data as $filaIndex => $fila) {
                if ($filaIndex == 0) continue;

                foreach ($indicesValoresNumericos as $indice) {
                    $valor = $fila[$indice] ?? null;

                    if ($valor !== null && $valor !== '') {

                        $valor = preg_replace('/[^\d.-]/', '', $valor);

                        if (!is_numeric($valor) || $valor < 0 || !preg_match('/^\d+(\.\d{1,2})?$/', $valor)) {
                            $errores[] = "Fila " . ($filaIndex + 1) . ", Columna '{$encabezadosEsperados[$indice]}': Valor inválido '{$valor}' <br> - ";
                        }
                    }
                }
            }

            if (!empty($errores)) {
                Storage::delete($path);
                $mensajeError = "Errores en los valores numéricos:<br> - " . implode("\n", $errores);
                $this->dispatch('mostrarMensaje', mensaje: $mensajeError, tipo: 'error', tiempo: 5000);
                return;
            }

            $datosExcelAsociados = [];
            $encabezadosObligatorios = ['AREA EJECUTORA', 'CUENTA', 'DESCRIPCION', 'CONCEPTO', 'MES'];
            $numeroFila = 0;
            foreach (array_slice($data, 1) as $fila) { 
                $numeroFila++;
 
                $fila = array_values(array_filter($fila, function ($valor) {
                    return trim($valor) !== '';
                }));

                if (count($encabezadosArchivo) != count($fila)) {
                    Storage::delete($path);
                    $this->dispatch('mostrarMensaje', mensaje: "El número de encabezados no coincide con el número de datos de una fila", tipo: 'error', tiempo: 5000);
                    return;
                }

                $filaAsociativa = array_combine($encabezadosArchivo, $fila);

                foreach ($encabezadosObligatorios as $campo) {
                    if (empty(trim($filaAsociativa[$campo]))) {
                        Storage::delete($path);
                        $this->dispatch('mostrarMensaje', mensaje: "El campo obligatorio '$campo' está vacío en la fila '$numeroFila" , tipo: 'error', tiempo: 5000);
                        return;
                    }
                }

                $cargo = trim($filaAsociativa["CARGO"] ?? '');
                $abono = trim($filaAsociativa["ABONO"] ?? ''); 
            
                if ($cargo === '' && $abono === '') {
                    Storage::delete($path);
                    $this->dispatch('mostrarMensaje', mensaje: "Cada fila debe tener un valor en 'CARGO' o 'ABONO'", tipo: 'error', tiempo: 5000);
                    return;
                }
            
                $datosExcelAsociados[] = $filaAsociativa;
            }

            return $datosExcelAsociados;

        }catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'error', tiempo: 3000);
        } catch (\Exception $e) {
            Log::error("Error al procesar el archivo en carga de devengado del 1000: " . $e->getMessage() . ' ' . $e->getLine());
            Storage::delete($path);
            $this->dispatch('mostrarMensaje', mensaje: 'Hubo un error al procesar el archivo.', tipo: 'error', tiempo: 3000);
        }
    }
    
    #[On('consultar-registro')]
    public function consultarRegistros($numeroEvento, $numeroPoliza, $total)
    {
        $this->consultarRegistro = true;
        $this->numeroEvento = $numeroEvento;
        $this->numeroPoliza = $numeroPoliza;
        $this->total = $total;
    }
}