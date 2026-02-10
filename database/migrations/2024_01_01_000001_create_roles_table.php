<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TABLA 1 DE 23: ROLES
     * Catálogo de roles del sistema
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique()->comment('Nombre del rol');
            $table->text('descripcion')->nullable();
            $table->json('permisos')->comment('Estructura de permisos');
            $table->boolean('es_activo')->default(true);
            $table->timestamps();
            
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
