<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TABLA 23 DE 23: AUDITORÍA Y MÉTRICAS
     * - auditorias
     * - alertas
     * - metricas_diarias
     */
    public function up(): void
    {
        // Auditorías
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $table->string('tabla_afectada', 60);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('accion', 20)->nullable()->comment('INSERT, UPDATE, DELETE');
            $table->json('payload_anterior')->nullable();
            $table->json('payload_nuevo')->nullable();
            $table->string('ip_origen', 45)->nullable();
            $table->string('user_agent', 200)->nullable();
            $table->timestamps();

            $table->index('tabla_afectada');
            $table->index('created_at');
            $table->index('usuario_id');
        });

        // Alertas
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_cobro_id')->nullable()->constrained('cuentas_cobro')->onDelete('cascade');
            $table->string('nivel', 20)->nullable()->comment('INFO, WARNING, ERROR, CRITICAL');
            $table->string('tipo_alerta', 50)->nullable();
            $table->text('mensaje')->nullable();
            $table->boolean('leida')->default(false);
            $table->foreignId('usuario_destino_id')->nullable()->constrained('usuarios');
            $table->timestamps();

            $table->index(['usuario_destino_id', 'leida']);
            $table->index('cuenta_cobro_id');
        });

        // Métricas Diarias
        Schema::create('metricas_diarias', function (Blueprint $table) {
            $table->date('fecha');
            $table->foreignId('bloque_id')->constrained('bloques_workflow');
            $table->integer('cantidad_procesada')->default(0);
            $table->integer('cantidad_aprobada')->default(0);
            $table->integer('cantidad_devuelta')->default(0);
            $table->decimal('promedio_tiempo_horas', 10, 2)->nullable();
            $table->integer('cumplimiento_sla_pct')->nullable();

            $table->primary(['fecha', 'bloque_id']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metricas_diarias');
        Schema::dropIfExists('alertas');
        Schema::dropIfExists('auditorias');
    }
};
