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
        Schema::create('cuenta_clasificador_egreso', function (Blueprint $table) {
            $table->id();
            $table->string('codigoCuenta');
            $table->string('CTG');
            $table->string('CP');
            $table->string('COG');
            $table->string('CFG');
            $table->string('CA');
        });
    }

    /** 
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta_clasificador_egreso');
    }
};
