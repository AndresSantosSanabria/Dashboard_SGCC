<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('primer_nombre', 50);
            $table->string('segundo_nombre', 50)->nullable();
            $table->string('primer_apellido', 50);
            $table->string('segundo_apellido', 50)->nullable();
            $table->string('usuario', 150)->unique();
            $table->string('password');
            $table->foreignId('rol_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->boolean('es_activo')->default(true);
            $table->dateTime('fecha_inactivacion')->nullable();
            $table->dateTime('ultimo_login')->nullable();
            $table->timestamps();

            $table->index('usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
