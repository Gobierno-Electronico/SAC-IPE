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
        Schema::create('interaccion_cuenta_cuentas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_interaccion_concepto_cuenta_1');
            $table->unsignedBigInteger('id_interaccion_concepto_cuenta_2');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interaccion_cuenta_cuentas');
    }
};
