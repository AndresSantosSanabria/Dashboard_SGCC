<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_proceso', 50)->nullable();
            $table->string('numero_contrato', 50)->unique();
            $table->foreignId('modalidad_id')->nullable()->constrained('modalidades')->nullOnDelete();
            $table->foreignId('contratista_id')->nullable()->constrained('contratistas')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('supervisores')->nullOnDelete();
            $table->text('objeto')->nullable();
            $table->decimal('monto_total', 19, 2);
            $table->foreignId('planta_id')->nullable()->constrained('plantas')->nullOnDelete();
            $table->foreignId('concepto_id')->nullable()->constrained('conceptos')->nullOnDelete();
            $table->string('cdp_codigo', 50)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('rp', 50)->nullable();
            $table->date('fecha_rp')->nullable();
            $table->decimal('valor_rp', 19, 2)->nullable();
            $table->boolean('es_activo')->default(true);
            $table->timestamps();
            
            $table->index('numero_proceso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
