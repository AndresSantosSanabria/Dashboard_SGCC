<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transiciones_estado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_cobro_id')->nullable()->constrained('cuentas_cobro')->nullOnDelete();
            $table->foreignId('estado_origen_id')->nullable()->constrained('estados_workflow')->nullOnDelete();
            $table->foreignId('estado_destino_id')->nullable()->constrained('estados_workflow')->nullOnDelete();
            $table->foreignId('usuario_accion_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->text('comentarios')->nullable();
            $table->integer('tiempo_transcurrido_minutos')->nullable();
            $table->string('accion', 50)->nullable();
            $table->timestamps();
            
            $table->index('cuenta_cobro_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transiciones_estado');
    }
};
