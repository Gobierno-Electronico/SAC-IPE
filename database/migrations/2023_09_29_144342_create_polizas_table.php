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
        Schema::create('polizas', function (Blueprint $table) {
            $table->id();
            $table->string('area_recaudadora');
            $table->string('tipo_poliza');
            $table->string('numero_poliza');
            $table->date('fecha');
            $table->string('origen_recurso');
            $table->string('CRI');
            $table->string('cuenta');
            $table->text('concepto');
            $table->decimal('total', 20, 2);
            $table->string('tipo_interaccion');
            $table->timestamps();          
            $table->enum('mes', ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polizas');
    }
};
