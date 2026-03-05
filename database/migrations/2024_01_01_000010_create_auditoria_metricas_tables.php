<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AUDITORÍA Y NOTIFICACIONES (Módulo de Observabilidad)
     * 
     * Este archivo centraliza la trazabilidad de acciones del usuario y 
     * el sistema proactivo de alertas.
     */
    public function up(): void
    {
        // 1. Auditorías
        // Registro forense de cambios. Guardamos el payload anterior y nuevo 
        // en archivos JSON para poder reconstruir cualquier registro en caso de error.
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $table->string('tabla_afectada', 60);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('accion', 20)->nullable()->comment('INSERT, UPDATE, DELETE');
            $table->json('payload_anterior')->nullable();
            $table->json('payload_nuevo')->nullable();

            // Contexto técnico para seguridad
            $table->string('ip_origen', 45)->nullable();
            $table->string('user_agent', 200)->nullable();
            $table->timestamps();

            $table->index('tabla_afectada');
            $table->index('created_at');
        });

        // 2. Alertas (Notificaciones In-App)
        // Optimizada para no generar SPAM: Usamos un índice único para asegurar 
        // que un usuario solo tenga una alerta activa de un tipo por cuenta.
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_cobro_id')->nullable()->constrained('cuentas_cobro')->cascadeOnDelete();
            $table->string('nivel', 20)->nullable()->comment('INFO, WARNING, ERROR, CRITICAL');
            $table->string('tipo_alerta', 50)->nullable();
            $table->text('mensaje')->nullable();
            $table->boolean('leida')->default(false);
            $table->foreignId('usuario_destino_id')->nullable()->constrained('usuarios');
            $table->timestamps();

            // INTEGRIDAD: El sistema de estancamiento usa este índice para hacer 'upserts'.
            // Evita que la bandeja de entrada del usuario se llene de alertas idénticas.
            $table->unique(
                ['cuenta_cobro_id', 'usuario_destino_id', 'tipo_alerta', 'nivel'],
                'uk_alerta_estancamiento_unica'
            );

            // RENDIMIENTO: Agregamos índices compuestos para que las vistas del dashboard 
            // carguen en milisegundos incluso con miles de alertas.
            $table->index(['tipo_alerta', 'leida', 'cuenta_cobro_id'], 'idx_alertas_performance_filter');
            $table->index(['usuario_destino_id', 'leida'], 'idx_alertas_optimization');
            $table->index('cuenta_cobro_id');
        });

        // 3. Destinatarios de Alertas (Configuración proactiva)
        // Permite mapear qué Roles o Usuarios deben ser notificados ante ciertos eventos.
        Schema::create('alerta_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->string('alerta_codigo', 100)->index(); // Ej: 'ALERTA_ESTANCAMIENTO'
            $table->string('tipo_destinatario', 20); // 'USUARIO', 'ROL'
            $table->unsignedBigInteger('destinatario_id');
            $table->timestamps();

            $table->index(['alerta_codigo', 'tipo_destinatario', 'destinatario_id'], 'idx_alerta_dest');
        });

        // 4. Métricas Diarias (Inteligencia de Negocio)
        // Tabla de agregación para el módulo de analítica. Evita calcular promedios 
        // pesados sobre el historial de workflow en tiempo real cada vez que abren un gráfico.
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
        Schema::dropIfExists('alerta_destinatarios');
        Schema::dropIfExists('alertas');
        Schema::dropIfExists('auditorias');
    }
};
