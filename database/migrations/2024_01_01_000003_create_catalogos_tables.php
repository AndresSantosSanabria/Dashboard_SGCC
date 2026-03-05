<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modalidades
        Schema::create('modalidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('es_activa')->default(true);
            $table->timestamps();
        });

        // Conceptos
        Schema::create('conceptos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('es_activo')->default(true);
            $table->timestamps();
        });

        // Plantas
        Schema::create('plantas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->boolean('es_activa')->default(true);
            $table->timestamps();
        });

        // Tipos de Documento
        Schema::create('tipos_documento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->boolean('es_obligatorio')->default(false);
            $table->string('categoria', 50)->nullable();
            $table->boolean('es_activo')->default(true);
            $table->timestamps();
        });

        // Tipos de Contratista (3NF)
        Schema::create('tipos_contratista', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->boolean('es_activo')->default(true);
            $table->timestamps();
        });

        // Estados Contrato SECOP (3NF)
        Schema::create('estados_contrato_secop', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->boolean('es_activo')->default(true);
            $table->timestamps();
        });

        // Festivos
        Schema::create('festivos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        // Configuraciones (Depende de Usuarios)
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->text('valor')->nullable();
            $table->string('tipo_dato', 20)->nullable()->comment('STRING, INT, BOOL, JSON');
            $table->text('descripcion')->nullable();
            $table->foreignId('modificado_por_id')->nullable()->constrained('usuarios');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
        Schema::dropIfExists('festivos');
        Schema::dropIfExists('estados_contrato_secop');
        Schema::dropIfExists('tipos_contratista');
        Schema::dropIfExists('tipos_documento');
        Schema::dropIfExists('plantas');
        Schema::dropIfExists('conceptos');
        Schema::dropIfExists('modalidades');
    }
};
