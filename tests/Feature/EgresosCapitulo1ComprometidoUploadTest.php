<?php

namespace Tests\Feature;

use App\Livewire\EgresosCapitulo1ComprometidoForm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\FileUploadController;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EgresosCapitulo1ComprometidoUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['livewire.temporary_file_upload.disk' => 'local']);
    }

    public function test_missing_file_keeps_the_validation_error_visible(): void
    {
        Livewire::test(EgresosCapitulo1ComprometidoForm::class)
            ->set('documentoFuente', 'Memorandum')
            ->call('cargarComprometido')
            ->assertHasErrors(['archivo' => 'Debes seleccionar al menos un archivo.'])
            ->assertSee('Debes seleccionar al menos un archivo.')
            ->assertDispatched('esconderCargando');
    }

    public function test_uploaded_xlsx_is_recognized_and_clears_the_previous_missing_file_error(): void
    {
        $archivo = UploadedFile::fake()->createWithContent(
            'comprometido.xlsx',
            file_get_contents(public_path('plantillas/formatoCargaComprometido1000.xlsx'))
        );

        // Omitir el documento fuente detiene la importación antes de registrar pólizas.
        Livewire::test(EgresosCapitulo1ComprometidoForm::class)
            ->call('cargarComprometido')
            ->assertHasErrors(['archivo' => 'Debes seleccionar al menos un archivo.'])
            ->set('archivo', $archivo)
            ->assertHasNoErrors('archivo')
            ->call('cargarComprometido')
            ->assertHasNoErrors('archivo')
            ->assertHasErrors('documentoFuente');
    }

    public function test_temporary_upload_rejection_is_shown_in_the_form(): void
    {
        $mensaje = 'El archivo supera el tamaño permitido.';

        Livewire::test(EgresosCapitulo1ComprometidoForm::class)
            ->call('_uploadErrored', 'archivo', json_encode([
                'errors' => ['files.0' => [$mensaje]],
            ]), false)
            ->assertHasErrors('archivo')
            ->assertSee($mensaje);
    }

    public function test_temporary_upload_accepts_the_reported_file_size(): void
    {
        $archivo = UploadedFile::fake()->create('comprometido.xlsx', 33183);

        $paths = app(FileUploadController::class)->validateAndStore([$archivo], 'local');

        $this->assertCount(1, $paths);
        Storage::disk('local')->assertExists('livewire-tmp/'.$paths[0]);
    }

    public function test_temporary_upload_rejects_files_larger_than_thirty_five_megabytes(): void
    {
        $archivo = UploadedFile::fake()->create('comprometido.xlsx', 35841);

        $this->expectException(ValidationException::class);

        app(FileUploadController::class)->validateAndStore([$archivo], 'local');
    }

    public function test_extra_and_missing_headers_show_a_specific_error_before_importing(): void
    {
        $headers = ['Area Ejecutora', 'CUENTA', 'DESCRIPCION', 'CONCEPTO', 'TOTAL', 'ENERO',
            'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE',
            'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        $missingMiddle = $headers;
        $missingMiddle[1] = '';

        foreach ([
            [array_merge($headers, ['EXTRA']), "Columna R: columna adicional 'EXTRA'"],
            [array_slice($headers, 0, -1), "Columna Q: se esperaba 'DICIEMBRE'"],
            [$missingMiddle, "Columna B: se esperaba 'CUENTA'"],
        ] as [$encabezados, $mensaje]) {
            $path = Storage::disk('local')->path('encabezados.xlsx');
            $book = new Spreadsheet();
            $book->getActiveSheet()->fromArray([$encabezados]);
            (new Xlsx($book))->save($path);
            $book->disconnectWorksheets();
            $archivo = UploadedFile::fake()->createWithContent('encabezados.xlsx', file_get_contents($path));

            Livewire::test(EgresosCapitulo1ComprometidoForm::class)
                ->set('archivo', $archivo)
                ->set('documentoFuente', 'Memorandum')
                ->call('cargarComprometido')
                ->assertDispatched('mostrarMensaje', fn ($evento, $params) =>
                    $params['tipo'] === 'error' && str_contains($params['mensaje'], $mensaje))
                ->assertNotDispatched('consultar-registro')
                ->assertDispatched('esconderCargando');
        }
    }

    public function test_excel_reader_uses_the_active_sheet_and_ignores_empty_formatted_cells(): void
    {
        $path = Storage::disk('local')->path('lectura.xlsx');
        $book = new Spreadsheet();
        $book->getActiveSheet()->setTitle('Otra hoja')->setCellValue('A1', 'No importar');
        $book->getActiveSheet()->setCellValue('A2', 50);
        $sheet = $book->createSheet();
        $sheet->setTitle('Comprometido');
        $sheet->fromArray([
            ['Area Ejecutora', 'CUENTA', 'DESCRIPCION', 'CONCEPTO', 'TOTAL', 'ENERO'],
            ['001', '8.2.2.1', 'Sueldos', 'Compromiso', '=SUM(F2:Q2)', 123.45],
        ]);
        $sheet->setCellValue('Q2', 0);
        $sheet->setCellValue('G2', "='Otra hoja'!A2");
        $sheet->getStyle('ZZ100000')->getFont()->setBold(true);
        $book->setActiveSheetIndex(1);
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        unset($book, $sheet);

        $component = new EgresosCapitulo1ComprometidoForm();
        $method = new \ReflectionMethod($component, 'leerDatosExcel');

        try {
            $data = $method->invoke($component, $path);

            $this->assertCount(2, $data);
            $this->assertCount(17, $data[0]);
            $this->assertSame('Area Ejecutora', $data[0][0]);
            $this->assertSame('001', $data[1][0]);
            $this->assertEquals(173.45, $data[1][4]);
            $this->assertEquals(50, $data[1][6]);
            $this->assertEquals(0, $data[1][16]);
        } finally {
            Storage::disk('local')->delete('lectura.xlsx');
        }
    }
}
