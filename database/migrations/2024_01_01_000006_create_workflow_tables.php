<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bloques de Workflow
        Schema::create('bloques_workflow', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 20)->unique();
            $table->smallInteger('orden')->unique();
            $table->integer('sla_horas')->nullable();
            $table->boolean('requiere_aprobacion')->default(false);
            $table->json('roles_permitidos')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('icono', 50)->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->boolean('es_activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Estados de Workflow
        Schema::create('estados_workflow', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bloque_id')->constrained('bloques_workflow')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->string('codigo', 30)->unique();
            $table->enum('tipo', ['INICIAL', 'EN_PROCESO', 'APROBADO', 'DEVUELTO', 'FINAL']);
            $table->boolean('es_inicial')->default(false);
            $table->boolean('es_final')->default(false);
            $table->boolean('permite_devolucion')->default(false);
            $table->boolean('contabiliza_tiempo')->default(true)->comment('Indica si el tiempo en este estado cuenta para SLA/Estancamiento');
            $table->boolean('afecta_indicadores')->default(true)->comment('Indica si las cuentas en este estado aparecen en gráficas de producción');
            $table->string('color_hex', 7)->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('es_activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Transiciones Permitidas
        Schema::create('transiciones_permitidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_origen_id')->constrained('estados_workflow')->cascadeOnDelete();
            $table->foreignId('estado_destino_id')->constrained('estados_workflow')->cascadeOnDelete();
            $table->boolean('requiere_comentario')->default(false);
            $table->boolean('requiere_documento')->default(false);
            $table->string('accion', 50)->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('es_activa')->default(true);
            $table->timestamps();

            $table->unique(['estado_origen_id', 'estado_destino_id'], 'uk_transicion');
        });

        // 4. Destinatarios de Alertas
        Schema::create('alerta_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->string('alerta_codigo', 100);
            $table->string('tipo_destinatario', 20); // 'USUARIO', 'ROL'
            $table->unsignedBigInteger('destinatario_id');
            $table->timestamps();

            $table->index('alerta_codigo');
            $table->index(['alerta_codigo', 'tipo_destinatario', 'destinatario_id'], 'idx_alerta_dest');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerta_destinatarios');
        Schema::dropIfExists('transiciones_permitidas');
        Schema::dropIfExists('estados_workflow');
        Schema::dropIfExists('bloques_workflow');
    }
};
