<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TABLAS 19, 20, 21 DE 23: RELACIONADAS A CUENTAS
     * - planillas_seguridad_social
     * - documentos
     * - estado_bloque_cuenta (CRÍTICA PARA INTEGRIDAD SECUENCIAL)
     */
    public function up(): void
    {
        // TABLA 19: Planillas de Seguridad Social
        Schema::create('planillas_seguridad_social', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_cobro_id')->constrained('cuentas_cobro')->onDelete('cascade');
            $table->string('mes_planilla', 20)->comment('PLANILLA SS del Excel (col 17)');
            $table->year('anio_planilla')->nullable();
            $table->string('numero_planilla', 50)->nullable();
            $table->decimal('valor_total', 19, 2)->nullable();
            $table->date('fecha_pago')->nullable();
            $table->boolean('es_ultima')->default(false);
            $table->timestamps();

            $table->index('cuenta_cobro_id');
            $table->index(['mes_planilla', 'anio_planilla']);
        });

        // TABLA 20: Documentos
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->nullable()->constrained('contratos')->onDelete('cascade');
            $table->foreignId('tipo_documento_id')->nullable()->constrained('tipos_documento');
            $table->string('nombre_archivo', 255)->nullable();
            $table->text('url_almacenamiento')->nullable();
            $table->string('estado_validacion', 50)->default('PENDIENTE');
            $table->foreignId('subido_por_id')->nullable()->constrained('usuarios');
            $table->smallInteger('version')->default(1);
            $table->timestamps();

            $table->index('contrato_id');
            $table->index('tipo_documento_id');
        });

        // TABLA 21: Estado por Bloque de Cuenta (CRÍTICA)
        // Esta tabla asegura la INTEGRIDAD SECUENCIAL del workflow
        Schema::create('estado_bloque_cuenta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_cobro_id')->constrained('cuentas_cobro')->onDelete('cascade');
            $table->foreignId('bloque_id')->constrained('bloques_workflow');
            $table->foreignId('estado_actual_id')->constrained('estados_workflow');
            $table->foreignId('responsable_id')->nullable()->constrained('usuarios')->comment('Responsable en este bloque específico');
            $table->dateTime('fecha_ingreso_bloque')->comment('Cuándo entró a este bloque');
            $table->dateTime('fecha_completado_bloque')->nullable()->comment('Cuándo completó el bloque (estado FINAL)');
            $table->dateTime('fecha_ultima_actualizacion')->nullable();
            $table->integer('numero_devoluciones')->default(0);
            $table->boolean('bloque_completado')->default(false)->comment('TRUE cuando alcanza estado FINAL');
            $table->text('observaciones')->nullable();
            $table->json('metadata')->nullable()->comment('Datos específicos por bloque');
            $table->timestamps();

            // CONSTRAINT CRÍTICO: Una cuenta solo puede tener UN registro por bloque
            $table->unique(['cuenta_cobro_id', 'bloque_id'], 'uk_cuenta_bloque');

            $table->index('cuenta_cobro_id');
            $table->index(['bloque_id', 'estado_actual_id']);
            $table->index('bloque_completado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estado_bloque_cuenta');
        Schema::dropIfExists('documentos');
        Schema::dropIfExists('planillas_seguridad_social');
    }
};
