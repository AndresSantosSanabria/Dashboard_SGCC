<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Festivos
        Schema::create('festivos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        // 2. Tabla de Destinatarios de Alertas (Normalización de receptores)
        Schema::create('alerta_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->string('alerta_codigo', 100); // ej: 'ALERTA_ESTANCAMIENTO'
            $table->string('tipo_destinatario', 20); // 'USUARIO', 'ROL'
            $table->unsignedBigInteger('destinatario_id');
            $table->timestamps();

            $table->index(['alerta_codigo', 'tipo_destinatario', 'destinatario_id'], 'idx_alerta_dest');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerta_destinatarios');
        Schema::dropIfExists('festivos');
    }
};
