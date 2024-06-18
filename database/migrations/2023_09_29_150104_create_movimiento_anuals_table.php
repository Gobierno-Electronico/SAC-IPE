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
        Schema::create('movimientos_anuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cuenta')->constrained('cuentas');
            $table->integer('anio');
            $table->double('monto_inicial');
            foreach (['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $mes) {
                $table->double("cargo_$mes")->default(0);
                $table->double("abono_$mes")->default(0);
            }
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_anuales');
    }
};
