<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TABLAS 12, 13 DE 23: CONTRATOS
     * - contratos
     * - registros_presupuestales
     */
    public function up(): void
    {
        // TABLA 12: Contratos
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_proceso', 50)->nullable();

            // EL CAMBIO ESTÁ AQUÍ: Unique asegura integridad a nivel de motor de BD
            $table->string('numero_contrato', 50)->unique()->comment('NUMERO DE CONTRATO del Excel (col 1)');

            $table->foreignId('modalidad_id')->nullable()->constrained('modalidades');
            $table->foreignId('contratista_id')->constrained('contratistas');
            $table->foreignId('supervisor_id')->nullable()->constrained('supervisores')->comment('SUPERVISOR@ del Excel (col 9)');
            $table->text('objeto')->nullable();
            $table->decimal('monto_total', 19, 2);
            $table->foreignId('planta_id')->nullable()->constrained('plantas');
            $table->foreignId('concepto_id')->nullable()->constrained('conceptos');
            $table->string('cdp_codigo', 50)->nullable();
            $table->date('fecha_inicio')->nullable()->comment('FECHA DE INICIO del Excel (col 7)');
            $table->date('fecha_fin')->nullable()->comment('FECHA DE TERMINACIÓN del Excel (col 8)');
            $table->boolean('es_activo')->default(true);
            $table->timestamps();

            // Índices de búsqueda (Unique ya actúa como índice para numero_contrato)
            $table->index('numero_proceso');
            $table->index('contratista_id');
        });

        // TABLA 13: Registros Presupuestales
        Schema::create('registros_presupuestales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->onDelete('cascade');
            $table->string('numero_rp', 50)->comment('RP del Excel (col 4)');
            $table->date('fecha_rp')->nullable()->comment('FECHA RP del Excel (col 5)');
            $table->decimal('valor_rp', 19, 2)->nullable()->comment('VALOR RP del Excel (col 6)');
            $table->string('estado', 50)->default('ACTIVO');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('numero_rp');
            $table->index('contrato_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_presupuestales');
        Schema::dropIfExists('contratos');
    }
};
