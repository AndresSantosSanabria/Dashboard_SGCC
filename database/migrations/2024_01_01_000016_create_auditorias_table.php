<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('tabla_afectada', 60);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('accion', 20)->nullable();
            $table->json('payload_anterior')->nullable();
            $table->json('payload_nuevo')->nullable();
            $table->string('ip_origen', 45)->nullable();
            $table->string('user_agent', 200)->nullable();
            $table->timestamps();
            
            $table->index('tabla_afectada');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
