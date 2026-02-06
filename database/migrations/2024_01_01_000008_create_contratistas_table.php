<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratistas', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social', 150)->nullable()->unique()->comment('Si es PJ');
            $table->string('nit', 20)->unique()->comment('Documento Identidad / CEDULA');
            $table->string('representante_legal', 150)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('direccion_fisica')->nullable();
            $table->string('entidad_salud', 100)->nullable();
            $table->string('entidad_pension', 100)->nullable();
            $table->string('entidad_arl', 100)->nullable();
            $table->boolean('es_activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratistas');
    }
};
