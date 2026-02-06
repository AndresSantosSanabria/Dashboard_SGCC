<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_workflow', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bloque_id')->nullable()->constrained('bloques_workflow')->nullOnDelete();
            $table->string('nombre', 100);
            $table->string('codigo', 20)->unique();
            $table->string('tipo', 20)->nullable();
            $table->boolean('es_inicial')->default(false);
            $table->boolean('es_final')->default(false);
            $table->boolean('permite_rechazo')->default(false);
            $table->string('color_hex', 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_workflow');
    }
};
