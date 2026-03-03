<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TABLA 2 DE 23: USUARIOS
     * Usuarios del sistema con acceso a la aplicación
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('primer_nombre', 50);
            $table->string('segundo_nombre', 50)->nullable();
            $table->string('primer_apellido', 50);
            $table->string('segundo_apellido', 50)->nullable();
            $table->string('user', 150)->unique();
            $table->string('password');
            $table->foreignId('rol_id')->constrained('roles');
            $table->boolean('es_activo')->default(true);
            $table->timestamp('fecha_inactivacion')->nullable();
            $table->timestamp('ultimo_login')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('user');
            $table->index('rol_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
