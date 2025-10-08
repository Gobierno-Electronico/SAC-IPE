<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\BitacoraController;
use App\Models\Cuenta;
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

class MatrizCargaForm extends Component
{

    use WithFileUploads;
    public $tipoMatriz = "";
    public $archivo;
    public $path = "";

    public function render()
    {
        try {
            return view('livewire.matriz-carga-form');
        } catch (\Throwable $th) {
            Log::error('Ocurrió un error al renderizar la carga de la matriz: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar matriz', tipo: 'error', tiempo: 3000);
        }
    }

    public function leerExcel()
    {
        $path = $this->path;

        try {
            $this->validate([
                'archivo' => 'required|mimes:xlsx',
                'tipoMatriz' => 'required'
            ], [
                'archivo.required' => "Debes seleccionar al menos un archivo.",
                'archivo.mimes' => "El archivo debe ser de tipo XLSX.",
                'tipoMatriz.required' => "El tipo de matriz es requerido."
            ]);

            $path = $this->archivo->store('temp');
            $filePath = storage_path('app/' . $path);

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if ($this->tipoMatriz == 'INGRESOS DEVENGADO-RECAUDADO SIMULTANEO') {
                $encabezadosEsperados = [
                    'CÓDIGO CLASIFICADOR',
                    'CONCEPTO',
                    'MEDIO DE RECAUDACIÓN',
                    'CARACTERÍSTICAS',
                    'CÓDIGO CARGO',
                    'CUENTA CARGO',
                    'CÓDIGO ABONO',
                    'CUENTA ABONO'
                ];
            } else if ($this->tipoMatriz == 'GASTOS DEVENGADO') {
                $encabezadosEsperados = [
                    'CÓDIGO CLASIFICADOR',
                    'CONCEPTO',
                    'TIPO DE GASTO',
                    'CARACTERÍSTICAS',
                    'CÓDIGO CARGO',
                    'CUENTA CARGO',
                    'CÓDIGO ABONO',
                    'CUENTA ABONO'
                ];
            } else if ($this->tipoMatriz == 'GASTOS PAGADO') {
                $encabezadosEsperados = [
                    'CÓDIGO CLASIFICADOR',
                    'CONCEPTO',
                    'TIPO DE GASTO',
                    'CARACTERÍSTICAS',
                    'MEDIO DE PAGO',
                    'CÓDIGO CARGO',
                    'CUENTA CARGO',
                    'CÓDIGO ABONO',
                    'CUENTA ABONO'
                ];
            } else {
                $encabezadosEsperados = [
                    'CÓDIGO CLASIFICADOR',
                    'CONCEPTO',
                    'CARACTERÍSTICAS',
                    'CÓDIGO CARGO',
                    'CUENTA CARGO',
                    'CÓDIGO ABONO',
                    'CUENTA ABONO'
                ];
            }


            // Limpiar encabezados (elimina nulos, vacíos y columnas fantasma)
            $encabezadosArchivo = array_filter(
                array_map('trim', $data[0]),
                fn($valor) => $valor !== "" && $valor !== null
            );


            // Reindexar para evitar huecos de índice
            $encabezadosArchivo = array_values($encabezadosArchivo);

            // --- Validar encabezados ---
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

            // --- Procesar filas ---
            $datosExcelAsociados = [];
            foreach (array_slice($data, 1) as $fila) {
                // Limpiar espacios vacíos y nulos
                $fila = array_values(array_filter($fila, function ($valor) {
                    return trim((string)$valor) !== '' && $valor !== null;
                }));

                // Recalcular número de encabezados reales
                $numEncabezados = count($encabezadosArchivo);

                // Verificar que la fila tenga al menos los encabezados obligatorios
                if (count($fila) < count($encabezadosArchivo)) {
                    Storage::delete($path);
                    $this->dispatch('mostrarMensaje', mensaje: "El número de columnas de la fila no coincide con los encabezados.", tipo: 'error', tiempo: 5000);
                    return;
                }

                // Si la fila tiene más columnas de las esperadas (algo anormal)
                if (count($fila) > $numEncabezados) {
                    $fila = array_slice($fila, 0, $numEncabezados); // recorta extras
                }
                // Combinar encabezados con datos
                $filaAsociativa = array_combine($encabezadosArchivo, $fila);
                $datosExcelAsociados[] = $filaAsociativa;
            }
            return $datosExcelAsociados;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'error', tiempo: 3000);
        } catch (\Exception $e) {
            Log::error("Error al procesar el archivo en carga de matriz de conversión: " . $e->getMessage() . ' ' . $e->getLine());
            Storage::delete($path);
            $this->dispatch('mostrarMensaje', mensaje: 'Hubo un error al procesar el archivo.', tipo: 'error', tiempo: 3000);
        }
    }



    public function cargarMatriz()
    {
        $datosExcel = $this->leerExcel();
        $usuariosController = new BitacoraController();
        $usuariosController->bitacora('cargarMatriz', 'cargó o intentó cargar el la matriz de conversión', request());
        $cuentasFaltantesPlanCuentas = [];

        try {

            $hayMatrizRegistrada = DB::table("matrices_de_conversion")
                ->where("categoria_matriz", $this->tipoMatriz)
                ->exists();

            if ($hayMatrizRegistrada) {
                $this->dispatch('mostrarMensaje', mensaje: 'Ya existe una matriz de este tipo registrada', tipo: 'error', tiempo: 3000);
                $this->dispatch('esconderCargando');
                return;
            }

            DB::beginTransaction();
            foreach ($datosExcel as $dato) {
                $cuentaCargo = Cuenta::where("Codigo_cuenta", $dato["CÓDIGO CARGO"])->first();
                $cuentaAbono = Cuenta::where("Codigo_cuenta", $dato["CÓDIGO ABONO"])->first();
                if (!$cuentaCargo) {
                    $cuentasFaltantesPlanCuentas[] = [
                        "Codigo_cuenta" => $dato["CÓDIGO CARGO"],
                        "Descripcion_cuenta" => $dato["CUENTA CARGO"]
                    ];
                }
                if (!$cuentaAbono) {
                    $cuentasFaltantesPlanCuentas[] = [
                        "Codigo_cuenta" => $dato["CÓDIGO ABONO"],
                        "Descripcion_cuenta" => $dato["CUENTA ABONO"]
                    ];
                }

                if (!empty($cuentasFaltantesPlanCuentas)) {
                    continue;
                }

                DB::insert(
                    'INSERT INTO matrices_de_conversion (
                        codigo_clasificador, 
                        concepto,
                        medio_recaudacion, 
                        tipo_gasto,
                        caracteristicas, 
                        medio_pago,
                        codigo_cargo, 
                        cuenta_cargo, 
                        codigo_abono, 
                        cuenta_abono, 
                        categoria_matriz
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $dato['CÓDIGO CLASIFICADOR'],
                        $dato['CONCEPTO'],
                        $dato['MEDIO DE RECAUDACIÓN'] ?? null,
                        $dato['TIPO DE GASTO'] ?? null,
                        $dato['CARACTERÍSTICAS'],
                        $dato['MEDIO DE PAGO'] ?? null,
                        $dato['CÓDIGO CARGO'],
                        $dato['CUENTA CARGO'],
                        $dato['CÓDIGO ABONO'],
                        $dato['CUENTA ABONO'],
                        $this->tipoMatriz
                    ]
                );
            }

            if (!empty($cuentasFaltantesPlanCuentas)) {
                $mensajeError = "Cuentas Faltantes en el plan:<br>";
                foreach ($cuentasFaltantesPlanCuentas as $cuenta) {
                    $mensajeError .= "Código: {$cuenta['Codigo_cuenta']}, Descripción: {$cuenta['Descripcion_cuenta']}<br>";
                }

                $this->dispatch('mostrarMensaje', mensaje: $mensajeError, tipo: 'error', tiempo: 5000);
            } else {
                DB::commit();
                $this->dispatch('mostrarMensaje', mensaje: "Matriz de conversión cargada correctamente, dirigase al apartado de consulta", tipo: 'success', tiempo: 5000);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Ocurrió un error al cargar la matriz de conversión: ' . $e->getMessage() . ' ' . $e->getLine());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al realizar la carga de la matriz, contacte al área de Gobierno Electrónico', tipo: 'error', tiempo: 3000);
        } finally {
            $this->dispatch('esconderCargando');
        }
    }
}
