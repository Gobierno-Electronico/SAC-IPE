<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('presupuesto_inicials', function (Blueprint $table) {
            $table->id();
            $table->string('area_recaudadora');
            $table->string('tipo');
            $table->integer('anio');
            $table->date('fecha');
            $table->string('CFF');
            $table->string('CRI');
            $table->string('cuenta');
            $table->string('descripcion');
            $table->string('concepto');
            $table->decimal('total', 15, 2);
            $table->decimal('monto_enero', 20, 2);
            $table->decimal('monto_febrero', 20, 2);
            $table->decimal('monto_marzo', 20, 2);
            $table->decimal('monto_abril', 20, 2);
            $table->decimal('monto_mayo', 20, 2);
            $table->decimal('monto_junio', 20, 2);
            $table->decimal('monto_julio', 20, 2);
            $table->decimal('monto_agosto', 20, 2);
            $table->decimal('monto_septiembre', 20, 2);
            $table->decimal('monto_octubre', 20, 2);
            $table->decimal('monto_noviembre', 20, 2);
            $table->decimal('monto_diciembre', 20, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuesto_inicial');
    }
};
