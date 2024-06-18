<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recalendarizacion', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('area');
            $table->string('cog');
            $table->string('mes');
            $table->string('afectacion');
            $table->decimal('inicial', 20, 10);
            $table->decimal('aumentado', 20, 10);
            $table->decimal('disminuido', 20, 10);
            $table->decimal('final', 20, 10);
            $table->string('evento');
            $table->string('tipo_poliza');
            $table->string('numero_poliza');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recalendarizacion');
    }
};
