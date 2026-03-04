<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Usar try-catch para ignorar errores si el índice ya existe
        try {
            Schema::table('historial_workflow', function (Blueprint $table) {
                $table->index(['cuenta_cobro_id', 'fecha_transicion'], 'idx_workflow_latest');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('alerta_destinatarios', function (Blueprint $table) {
                $table->index('alerta_codigo');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('alertas', function (Blueprint $table) {
                // Usar un nombre específico para evitar colisiones
                $table->index(['usuario_destino_id', 'leida'], 'idx_alertas_optimization');
            });
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        Schema::table('historial_workflow', function (Blueprint $table) {
            $table->dropIndex('idx_workflow_latest');
        });
        Schema::table('alerta_destinatarios', function (Blueprint $table) {
            $table->dropIndex(['alerta_codigo']);
        });
        Schema::table('alertas', function (Blueprint $table) {
            $table->dropIndex(['usuario_destino_id', 'leida']);
        });
    }
};
