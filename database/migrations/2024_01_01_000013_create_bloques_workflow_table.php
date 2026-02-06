<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloques_workflow', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 10)->unique();
            $table->smallInteger('orden')->unique();
            $table->integer('sla_horas')->nullable();
            $table->boolean('requiere_aprobacion')->default(false);
            $table->string('responsable', 100)->nullable();
            $table->string('icono', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloques_workflow');
    }
};
