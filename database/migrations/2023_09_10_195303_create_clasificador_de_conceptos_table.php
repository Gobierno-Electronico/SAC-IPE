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
        Schema::create('clasificador_de_conceptos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_clasificador');
            $table->string('descripcion_clasificador');
            $table->integer('Nivel');
            $table->bigInteger('clasificador_padre')->nullable();
            $table->boolean('estado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clasificador_de_conceptos');
    }
};
