<?php

namespace Tests\Feature;

use App\Livewire\EgresosCapitulo1ComprometidoForm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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
}
