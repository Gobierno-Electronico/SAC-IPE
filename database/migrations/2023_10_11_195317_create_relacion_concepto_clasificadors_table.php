<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('relacion_concepto_clasificadors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concepto_id')->constrained();
            $table->foreignId('clasificador_de_concepto_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relacion_concepto_clasificadors');
    }
};