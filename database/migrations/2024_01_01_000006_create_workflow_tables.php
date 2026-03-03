<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TABLAS 14, 15, 16, 17 DE 23: WORKFLOW
     * - bloques_workflow
     * - estados_workflow
     * - transiciones_permitidas
     * - (cuentas_cobro se crea en siguiente migración por dependencias)
     */
    public function up(): void
    {
        // TABLA 14: Bloques de Workflow
        Schema::create('bloques_workflow', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 20)->unique();
            $table->smallInteger('orden')->unique()->comment('Orden secuencial: 1, 2, 3, 4, 5');
            $table->integer('sla_horas')->nullable();
            $table->boolean('requiere_aprobacion')->default(false);
            $table->json('roles_permitidos')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('icono', 50)->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->boolean('es_activo')->default(true);
            $table->timestamps();

            $table->index('orden');
            $table->index('codigo');
        });

        // TABLA 15: Estados de Workflow
        Schema::create('estados_workflow', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bloque_id')->constrained('bloques_workflow')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->string('codigo', 30)->unique();
            $table->enum('tipo', ['INICIAL', 'EN_PROCESO', 'APROBADO', 'DEVUELTO', 'FINAL']);
            $table->boolean('es_inicial')->default(false)->comment('Primer estado al entrar al bloque');
            $table->boolean('es_final')->default(false)->comment('Estado que completa el bloque');
            $table->boolean('permite_devolucion')->default(false)->comment('Permite regresar al bloque anterior');
            $table->string('color_hex', 7)->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('es_activo')->default(true);
            $table->timestamps();

            $table->index('bloque_id');
            $table->index('tipo');
            $table->index('codigo');
        });

        // TABLA 16: Transiciones Permitidas
        Schema::create('transiciones_permitidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_origen_id')->constrained('estados_workflow')->onDelete('cascade');
            $table->foreignId('estado_destino_id')->constrained('estados_workflow')->onDelete('cascade');
            $table->boolean('requiere_comentario')->default(false);
            $table->boolean('requiere_documento')->default(false);
            $table->string('accion', 50)->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('es_activa')->default(true);
            $table->timestamps();

            $table->unique(['estado_origen_id', 'estado_destino_id'], 'uk_transicion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transiciones_permitidas');
        Schema::dropIfExists('estados_workflow');
        Schema::dropIfExists('bloques_workflow');
    }
};
