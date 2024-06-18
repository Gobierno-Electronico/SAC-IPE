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
        Schema::create('interaccion_cuenta_conceptos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            // $table->foreign(['id_concepto','id_cuenta','id_clasificador_concepto'])->references(['id','id','id'])->on(['conceptos','cuentas','clasificador_de_conceptos']);
            $table->foreignId('concepto_id')->constrained();
            $table->foreignId('cuenta_id')->constrained();
            $table->foreignId('clasificador_de_concepto_id')->constrained();
            $table->string('tipo_interaccion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interaccion_cuenta_conceptos');
    }
};
