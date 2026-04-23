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
use Illuminate\Support\Facades\Auth;
use Log;
use DB;
use Illuminate\Support\Facades\Storage;
use App\Enums\EstatusEvento;

use function PHPUnit\Framework\isNull;

class EgresosCapitulo1ComprometidoForm extends Component
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
    
    public $documentoFuente = "";
    public int $anio;

    public function mount()
    {
        $this->anio = (int) session('anioSeleccionado', now()->year);
        $this->fechaAfectacion = "{$this->anio}-01-01";
    }
    
    public function render()
    {
        try {
            return view('livewire.egresos-capitulo1-comprometido-form');
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al renderizar comprometido del capítulo 1000: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar las cuentas, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        }
    }

    public function cargarComprometido()
    {
        set_time_limit(3000);
        ini_set('max_execution_time', 3000);
        try {
            $this->dispatch('mostrarCargando');
            $this->validate([
                'archivo' => 'required|mimes:xlsx',
                'fechaAfectacion' => 'required',
                'documentoFuente' => 'required'
            ], [
                'archivo.required' => "Debes seleccionar al menos un archivo.",
                'archivo.mimes' => "El archivo debe ser de tipo XLSX.",
                'fechaAfectacion.required' => "La fecha de afectación es requerida.",
                'documentoFuente.required' => "El documento fuente es requerido."
            ]);


            $path = $this->archivo->store('temp');
            $filePath = storage_path('app/' . $path); // Ruta donde guardaste el archivo

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            $encabezadosEsperados = ['Area Ejecutora', 'CUENTA', 'DESCRIPCION', 'CONCEPTO', 'TOTAL', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];

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


            $indicesMeses = range(5, 16); // Desde "ENERO" (índice 5) hasta "DICIEMBRE" (índice 16)
            $erroresMeses = [];

            foreach ($data as $filaIndex => $fila) {
                if ($filaIndex == 0) continue;

                foreach ($indicesMeses as $indice) {
                    $valor = $fila[$indice] ?? null;

                    if ($valor !== null && $valor !== '') {

                        $valor = preg_replace('/[^\d.-]/', '', $valor);

                        if (!is_numeric($valor) || $valor < 0 || !preg_match('/^\d+(\.\d{1,2})?$/', $valor)) {
                            $erroresMeses[] = "Fila " . ($filaIndex + 1) . ", Columna '{$encabezadosEsperados[$indice]}': Valor inválido '{$valor}' <br> - ";
                        }
                    }
                }
            }

            if (!empty($erroresMeses)) {
                Storage::delete($path);
                $mensajeError = "Errores en los valores de los meses:<br> - " . implode("\n", $erroresMeses);
                $this->dispatch('mostrarMensaje', mensaje: $mensajeError, tipo: 'error', tiempo: 5000);
                return;
            }

            $idUsuarioRegistrante = Auth::id();
            $usuariosController = new BitacoraController();
            $usuariosController->bitacora('cargarComprometido', 'cargó o intentó cargar el comprometido del capítulo 1000 de egresos', request());
            DB::beginTransaction();
            $cuentasFaltantesPlanCuentas = [];
            $cuentasEnLaGuiaFaltantes = [];
            $anioActual = (string) $this->anio;
            $meses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
            $fecha = Carbon::now('America/Mexico_City');
            $fecha->year($anioActual);

            $datosExcelAsociados = [];

            foreach (array_slice($data, 1) as $fila) {

                $fila = array_values(array_filter($fila, function ($valor) {
                    return trim($valor) !== '';
                }));

                if (count($encabezadosArchivo) != count($fila)) {
                    Storage::delete($path);
                    $this->dispatch('mostrarMensaje', mensaje: "El número de encabezados no coincide con el número de datos de una fila", tipo: 'error', tiempo: 5000);
                    return;
                }

                $filaAsociativa = array_combine($encabezadosArchivo, $fila);
                $datosExcelAsociados[] = $filaAsociativa;
            }

            $numerosPolizas = Poliza::selectRaw('CAST(numero_poliza AS INT) as numero_poliza')
                ->where('tipo_poliza', '=', 'E')
                ->whereYear('fecha', '=', $anioActual)
                ->distinct()
                ->orderBy('numero_poliza')
                ->pluck('numero_poliza')
                ->toArray();

            $numerosEvento = Poliza::selectRaw('CAST(evento AS INT) as evento')
                ->whereYear('fecha', $anioActual)
                ->distinct()
                ->orderBy('evento')
                ->pluck('evento')
                ->toArray();

            $ultimoNumero = end($numerosPolizas);
            $this->numeroPoliza = ($ultimoNumero) ? $ultimoNumero + 1 : 1;
            $this->numeroEvento = end($numerosEvento) + 1;
            $polizas = [];

            $cuentas = Cuenta::whereIn("Codigo_cuenta", array_column($datosExcelAsociados, "CUENTA"))->get()->keyBy("Codigo_cuenta"); //extrae toas las cuentas antes de un ciclo foreach y después dentro del ciclo se obtienen en memoria y no desde la base, esti ayuda a mejorar el procedimiento porque evita hacer miles de consultas dentro del ciclo.

            foreach ($datosExcelAsociados as $dato) {

                if ($this->observaciones == '') {
                    $this->observaciones = $dato['CONCEPTO'];
                }
                $cuenta = $cuentas[$dato["CUENTA"]] ?? null; // se extrae la cuenta del array que anteriormente se guardó en memoria, esto evita hacer consultas dentro de un ciclo.

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

                $interaccionCuentaConceptoPrincipal = InteraccionCuentaConcepto::where('cuenta_id', '=', $cuenta->id)
                    ->where('concepto_id', '=', 10103)
                    ->where('tipo_interaccion', '=', 'Presupuestal - Cargo')->first();

                if (!$interaccionCuentaConceptoPrincipal) {
                    $codigosExistentes = array_column($cuentasEnLaGuiaFaltantes, 'Codigo_cuenta');

                    if (!in_array($dato['CUENTA'], $codigosExistentes)) {
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

                foreach ($meses as $mes) {
                    $dato[$mes] = str_replace(',', '', $dato[$mes]);
                    $this->total = $this->total + $dato[$mes];
                    array_push($polizas, [
                        'idUsuarioRegistrante' => $idUsuarioRegistrante,
                        'area' => $dato['Area Ejecutora'],
                        'tipo_poliza' => 'E',
                        'numero_poliza' =>  $this->numeroPoliza,
                        'fecha' => $this->fechaAfectacion,
                        'cuenta' => $dato['CUENTA'],
                        'concepto' => $dato['DESCRIPCION'],
                        'total' => $dato[$mes],
                        'mes' => $mes,
                        'descripcion' => $dato['CONCEPTO'],
                        'evento' => $this->numeroEvento,
                        'tipo_interaccion' => $interaccionCuentaConceptoPrincipal->tipo_interaccion,
                        'validado' => false,
                        'estatus_evento' => EstatusEvento::ACTIVO->value,
                        'categoria' => 'EGRESOS COMPROMETIDO CAPITULO 1',
                        'documento_fuente' => $this->documentoFuente,
                        'created_at' => $fecha,
                        'updated_at' => $fecha
                    ]);
                }

                foreach ($interaccionCuentaCuentas as $polizaPorEjercer) {
                    foreach ($meses as $mes) {
                        $dato[$mes] = str_replace(',', '', $dato[$mes]);
                        $this->total = $this->total + $dato[$mes];
                        array_push($polizas, [
                            'idUsuarioRegistrante' => $idUsuarioRegistrante,
                            'area' => $dato['Area Ejecutora'],
                            'tipo_poliza' => 'E',
                            'numero_poliza' =>  $this->numeroPoliza,
                            'fecha' => $this->fechaAfectacion,
                            'cuenta' => $polizaPorEjercer['Codigo_cuenta'],
                            'concepto' => $polizaPorEjercer['Descripcion_cuenta'],
                            'total' => $dato[$mes],
                            'mes' => $mes,
                            'descripcion' => $dato['CONCEPTO'],
                            'evento' => $this->numeroEvento,
                            'tipo_interaccion' => $polizaPorEjercer['tipo_interaccion'],
                            'validado' => false,
                            'estatus_evento' => EstatusEvento::ACTIVO->value,
                            'categoria' => 'EGRESOS COMPROMETIDO CAPITULO 1',
                            'documento_fuente' => $this->documentoFuente,
                            'created_at' => $fecha,
                            'updated_at' => $fecha
                        ]);
                    }
                }
            }

            if (!empty($cuentasFaltantesPlanCuentas)) {
                Storage::delete($path);
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
                Storage::delete($path);
                $this->dispatch('esconderCargando');
                $this->dispatch('consultar-registro', $this->numeroEvento, $this->numeroPoliza, $this->total);
            } else {
                Storage::delete($path);
                $mensajeError = "Cuentas Faltantes en la guía contabilizadora:<br>";
                foreach ($cuentasEnLaGuiaFaltantes as $cuenta) {
                    $mensajeError .= "Código: {$cuenta['Codigo_cuenta']} - Descripción: {$cuenta['Descripcion_cuenta']}<br>";
                }

                $this->dispatch('mostrarMensaje', mensaje: $mensajeError, tipo: 'error', tiempo: 5000);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'error', tiempo: 3000);
        } catch (\Exception $e) {
            Log::error("Error al procesar el archivo en carga de comprometido del 1000: " . $e->getMessage() . ' ' . $e->getLine());
            Storage::delete($path); // Asegurar que el archivo se borre en caso de error
            $this->dispatch('mostrarMensaje', mensaje: 'Hubo un error al procesar el archivo.', tipo: 'error', tiempo: 3000);
        } finally {
            $this->dispatch('esconderCargando');
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
