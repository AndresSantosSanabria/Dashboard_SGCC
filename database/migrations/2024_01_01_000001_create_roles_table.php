<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Permisos (Maestra)
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('slug', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->string('modulo', 50)->nullable();
            $table->timestamps();
        });

        // 2. Tabla de Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['SISTEMA', 'PERSONALIZADO'])->default('SISTEMA');
            $table->boolean('es_activo')->default(true);
            $table->timestamps();
        });

        // 3. Relación Muchos a Muchos: Roles - Permisos
        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete();
            $table->primary(['rol_id', 'permiso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_permiso');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permisos');
    }
};
