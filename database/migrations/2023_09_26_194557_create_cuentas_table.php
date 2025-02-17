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
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('Codigo_cuenta')->unique();
            $table->string('Descripcion_cuenta');
            $table->boolean('Cuenta_registro')->default(false);    
            $table->string('Clasificador_rubro_ingreso')->nullable();
            $table->string('Clasificador_objeto_gasto')->nullable();
            $table->integer('Nivel');
            $table->bigInteger('Cuenta_padre_ID')->nullable();
            $table->boolean('Estado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
