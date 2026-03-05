<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TABLA 22 DE 23: HISTORIAL DE WORKFLOW
     * Registra TODAS las transiciones de estado
     */
    public function up(): void
    {
        Schema::create('historial_workflow', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_cobro_id')->constrained('cuentas_cobro')->cascadeOnDelete();
            $table->foreignId('bloque_id')->constrained('bloques_workflow');
            $table->foreignId('estado_origen_id')->nullable()->constrained('estados_workflow');
            $table->foreignId('estado_destino_id')->constrained('estados_workflow');
            $table->foreignId('usuario_accion_id')->constrained('usuarios');
            $table->timestamp('fecha_transicion')->useCurrent();
            $table->integer('tiempo_en_estado_anterior_minutos')->nullable();
            $table->string('accion', 50)->nullable()->comment('APROBAR, RECHAZAR, DEVOLVER, PASAR_BLOQUE');
            $table->text('comentarios')->nullable();
            $table->json('documentos_adjuntos')->nullable();
            $table->json('metadata')->nullable();

            $table->index('cuenta_cobro_id');
            $table->index('bloque_id');
            $table->index('fecha_transicion');
            $table->index('usuario_accion_id');
            $table->index(['cuenta_cobro_id', 'fecha_transicion'], 'idx_workflow_latest');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_workflow');
    }
};
