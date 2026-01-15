<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
// Importamos el ReadFilter para la lectura eficiente de archivos grandes
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter; 
use App\Http\Controllers\BitacoraController;
use App\Models\Poliza; // Modelo principal para la actualización
use Log;
use DB;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Clase auxiliar para leer el archivo Excel por lotes (chunks).
 * Se utiliza para decirle a PHPSpreadsheet qué celdas leer en cada iteración,
 * reduciendo el consumo de memoria.
 */
class ChunkReadFilter implements IReadFilter
{
    private $startRow = 0;
    private $endRow = 0;

    public function setRows(int $startRow, int $endRow): void
    {
        $this->startRow = $startRow;
        $this->endRow = $endRow;
    }

    public function readCell($column, $row, $worksheetName = ''): bool
    {
        // Solo leer los encabezados (fila 1) y las filas en el rango de nuestro lote
        if ($row == 1 || ($row >= $this->startRow && $row <= $this->endRow)) {
            return true;
        }
        return false;
    }
}


class FuenteCargaForm extends Component 
{
    use WithFileUploads;

    public $archivo;
    public $path = "";
    
    // Definición de encabezados esperados
    private $encabezadosEsperados = [
        'evento',
        'tipo poliza',
        'numero poliza',
        'documento fuente'
    ];

    // Constante para definir el tamaño de los lotes al leer el Excel
    private const EXCEL_CHUNK_SIZE = 1000;
    
    // Constante para limitar la cláusula WHERE IN de SQL Server
    private const SQL_SERVER_BATCH_SIZE = 1000; 

    /**
     * Renderiza la vista del componente.
     */
    public function render()
    {
        try {
            return view('livewire.fuente-carga-form');
        } catch (\Throwable $th) {
            Log::error('Error al renderizar la carga de documentos fuente: ' . $th->getMessage());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error al cargar la interfaz', tipo: 'error', tiempo: 3000);
        }
    }

    /**
     * Valida el archivo, almacena temporalmente y verifica los encabezados.
     * Ya NO lee todo el archivo a memoria.
     * @return array|null [filePath, totalRows] o null si hay error.
     */
    private function validateAndGetFileMetadata()
    {
        $path = null;
        try {
            // 1. Validación de Livewire
            $this->validate([
                'archivo' => 'required|mimes:xlsx',
            ], [
                'archivo.required' => "Debes seleccionar el archivo con los documentos fuente.",
                'archivo.mimes' => "El archivo debe ser de tipo XLSX."
            ]);

            // 2. Almacenamiento temporal
            $path = $this->archivo->store('temp');
            $filePath = storage_path('app/' . $path);
            $this->path = $path; // Guardar el path para limpiar si algo falla después

            // 3. Lectura de encabezados y metadata
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath); 
            $sheet = $spreadsheet->getActiveSheet();
            $totalRows = $sheet->getHighestDataRow();
            
            // Leemos solo la primera fila para validar los encabezados
            $headerData = $sheet->rangeToArray('A1:' . $sheet->getHighestDataColumn() . '1', null, true, true, false)[0];

            // 4. Limpieza y validación de encabezados
            $encabezadosArchivo = array_values(array_filter(
                array_map('trim', $headerData),
                fn($valor) => $valor !== "" && $valor !== null
            ));
            
            $numEncabezadosRequeridos = count($this->encabezadosEsperados);
            $encabezadosRequeridosDelArchivo = array_slice($encabezadosArchivo, 0, $numEncabezadosRequeridos);

            if (count($encabezadosRequeridosDelArchivo) < $numEncabezadosRequeridos || array_diff($encabezadosRequeridosDelArchivo, $this->encabezadosEsperados)) {
                $mensajeError = "Las primeras cuatro columnas deben ser exactamente (en minúsculas): " . implode(', ', $this->encabezadosEsperados) . " en ese orden. Las columnas adicionales serán ignoradas.";
                $this->dispatch('mostrarMensaje', mensaje: $mensajeError, tipo: 'error', tiempo: 5000);
                Storage::delete($path);
                return null;
            }

            if ($totalRows < 2) {
                $this->dispatch('mostrarMensaje', mensaje: "El archivo subido no contiene filas de datos válidas, solo encabezados.", tipo: 'warning', tiempo: 4000);
                Storage::delete($path);
                return null;
            }

            return ['filePath' => $filePath, 'totalRows' => $totalRows];

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($path) Storage::delete($path);
            $this->dispatch('mostrarMensaje', mensaje: $e->getMessage(), tipo: 'error', tiempo: 3000);
            return null;
        } catch (Exception $e) {
            if ($path) Storage::delete($path);
            Log::error("Error de estructura o lectura inicial del archivo: " . $e->getMessage() . ' Línea: ' . $e->getLine());
            $this->dispatch('mostrarMensaje', mensaje: 'Hubo un error al leer la estructura inicial del archivo.', tipo: 'error', tiempo: 3000);
            return null;
        }
    }


    /**
     * Método principal para cargar el archivo y actualizar el documento fuente de forma masiva.
     */
    public function actualizarDocumentosFuente()
    {
        // MODIFICACIÓN AÑADIDA: Aumentar el límite de ejecución a 4 minutos (240 segundos)
        set_time_limit(240); 
        
        $this->dispatch('mostrarCargando'); // Mostrar spinner de carga
        $metadata = $this->validateAndGetFileMetadata();
        
        if (empty($metadata)) {
            $this->dispatch('esconderCargando');
            return;
        }

        $filePath = $metadata['filePath'];
        $totalRows = $metadata['totalRows'];
        
        $totalRegistrosProcesados = 0;
        $totalActualizados = 0;
        $errores = [];
        // Array para mapear [poliza_id => documento_fuente]
        $updates = []; 

        // Registro de bitácora de inicio
        (new BitacoraController())->bitacora('actualizarDocumentosFuente', 'Inició actualización masiva de documentos fuente.', request());

        DB::beginTransaction();
        try {
            $filter = new ChunkReadFilter();
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            
            // Bucle principal que procesa el Excel en bloques
            for ($startRow = 2; $startRow <= $totalRows; $startRow += self::EXCEL_CHUNK_SIZE) {
                $endRow = min($startRow + self::EXCEL_CHUNK_SIZE - 1, $totalRows);
                
                // 1. Configurar el filtro para leer solo el chunk actual
                $filter->setRows($startRow, $endRow);
                $reader->setReadFilter($filter);

                // 2. Cargar el chunk en memoria
                $chunkSpreadsheet = $reader->load($filePath);
                $chunkData = $chunkSpreadsheet->getActiveSheet()->toArray(null, true, true, false);

                // 3. Procesar filas del chunk 
                $numEncabezados = count($this->encabezadosEsperados);
                
                // Mapear el índice 0 del chunk al número de fila real en Excel ($startRow)
                foreach ($chunkData as $index => $fila) {
                    if (empty($fila) || (isset($fila[0]) && $fila[0] === null)) {
                        continue; 
                    }
                    
                    // Calcular el número de fila real del Excel: 
                    // El índice 0 del array chunkData corresponde a $startRow (la primera fila del lote)
                    $filaExcel = $startRow + $index; 

                    // Limpieza: Asegurarse de que no sea una fila vacía
                    $hasData = array_filter(array_map('trim', $fila), fn($val) => $val !== null && $val !== '');
                    if (empty($hasData)) {
                        continue;
                    }
                    
                    // Aseguramos que tenemos suficientes columnas para combinar
                    if (count($fila) < $numEncabezados) {
                         $errores[] = "Fila $filaExcel: Faltan datos en las columnas requeridas.";
                         continue;
                    }

                    // Mapeo de datos para el registro actual
                    $dato = array_combine($this->encabezadosEsperados, array_slice($fila, 0, $numEncabezados));

                    // Normalizar y limpiar datos
                    $evento = trim($dato['evento'] ?? '');
                    $tipoPoliza = trim($dato['tipo poliza'] ?? ''); 
                    $polizaNum = trim($dato['numero poliza'] ?? ''); 
                    $documentoFuente = trim($dato['documento fuente'] ?? '');

                    // Omitir filas con datos de búsqueda vacíos
                    if (empty($evento) || empty($polizaNum) || empty($tipoPoliza)) {
                        $errores[] = "Fila $filaExcel: Campos de búsqueda vacíos. Se saltó el registro.";
                        continue;
                    }
                    
                    $totalRegistrosProcesados++;

                    // 4. Buscar IDs de las pólizas coincidentes (SELECT id, optimizado)
                    $polizaIds = Poliza::where('evento', $evento)
                                             ->where('tipo_poliza', $tipoPoliza)
                                             ->where('numero_poliza', $polizaNum)
                                             ->pluck('id');

                    if ($polizaIds->isEmpty()) {
                        $errores[] = "Fila $filaExcel: No se encontró una póliza que coincida (Evento: '$evento', Tipo: '$tipoPoliza', Número: '$polizaNum').";
                        continue;
                    }

                    // 5. Acumular IDs para la actualización masiva.
                    foreach ($polizaIds as $id) {
                        // Mapea el ID de la póliza al nuevo documento fuente
                        $updates[$id] = $documentoFuente;
                    }
                }
                
                // Liberar memoria del chunk
                unset($chunkSpreadsheet);
                unset($chunkData);
            }
            
            // --- EJECUCIÓN DE ACTUALIZACIÓN MASIVA FINAL (CON BATCHING SQL PARA SQL SERVER) ---
            if (!empty($updates)) {
                
                // 1. Agrupar los updates por el valor del documento fuente
                $updatesByDocumentoFuente = [];
                foreach ($updates as $id => $docFuente) {
                    $updatesByDocumentoFuente[$docFuente][] = $id;
                }

                $updatedCount = 0;
                // 2. Iterar por cada documento fuente
                foreach ($updatesByDocumentoFuente as $docFuente => $ids) {
                    
                    // 3. Dividir el array de IDs en lotes seguros (máx. 1000 IDs por consulta)
                    $idChunks = array_chunk($ids, self::SQL_SERVER_BATCH_SIZE);

                    foreach ($idChunks as $idChunk) {
                         // 4. Ejecutar el UPDATE para el lote seguro.
                         $affectedRows = Poliza::whereIn('id', $idChunk)->update(['documento_fuente' => $docFuente]);
                         $updatedCount += $affectedRows;
                    }
                }
                $totalActualizados = $updatedCount;
            }
            // ---------------------------------------------------------------------------------

            // Limpieza final del archivo temporal
            Storage::delete($this->path);

            // Reportar resultados
            if (!empty($errores)) {
                DB::commit();
                $mensajeErroresHtml = "Se actualizaron **$totalActualizados** pólizas con éxito. Total de filas procesadas: $totalRegistrosProcesados. <br>Se encontraron los siguientes errores/advertencias (Fila de Excel):<br>" . implode("<br>", $errores);
                $this->dispatch('mostrarMensaje', mensaje: $mensajeErroresHtml, tipo: 'warning', tiempo: 15000);
            } else {
                DB::commit();
                $this->dispatch('mostrarMensaje', mensaje: "¡Carga y actualización de documentos fuente finalizada con éxito! Total de pólizas actualizadas: $totalActualizados", tipo: 'success', tiempo: 5000);
            }

            // Limpiar el input de archivo
            $this->archivo = null;

        } catch (\Throwable $e) {
            DB::rollBack();
            if (Storage::exists($this->path)) {
                Storage::delete($this->path);
            }
            Log::error('Error al actualizar documentos fuente (proceso de chunking): ' . $e->getMessage() . ' Línea: ' . $e->getLine());
            $this->dispatch('mostrarMensaje', mensaje: 'Ocurrió un error grave al actualizar. Contacte al administrador.', tipo: 'error', tiempo: 7000);
        } finally {
            $this->dispatch('esconderCargando');
        }
    }
}