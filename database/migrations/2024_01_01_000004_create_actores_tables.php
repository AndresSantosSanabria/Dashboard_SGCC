<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TABLAS 8, 9, 10, 11 DE 23: ACTORES
     * - contratistas
     * - entidades_seguridad_social
     * - contratista_seguridad_social
     * - supervisores
     */
    public function up(): void
    {
        // TABLA 8: Contratistas
        Schema::create('contratistas', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social', 150)->comment('CONTRATISTA del Excel (col 2)');
            $table->string('nit', 20)->comment('CEDULA del Excel (col 3)');
            $table->enum('tipo_persona', ['NATURAL', 'JURIDICA'])->default('NATURAL');
            $table->string('representante_legal', 150)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('direccion_fisica')->nullable();
            $table->boolean('es_activo')->default(true);
            $table->timestamps();

            $table->index('nit');
            $table->index('razon_social');
        });

        // TABLA 9: Entidades de Seguridad Social
        Schema::create('entidades_seguridad_social', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->enum('tipo', ['SALUD', 'PENSION', 'ARL']);
            $table->string('codigo', 20)->nullable();
            $table->boolean('es_activa')->default(true);
            $table->timestamps();

            $table->index(['tipo', 'nombre']);
        });

        // TABLA 10: Relación Contratista - Seguridad Social
        Schema::create('contratista_seguridad_social', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');
            $table->foreignId('entidad_salud_id')->nullable()->constrained('entidades_seguridad_social')->comment('ENTIDAD SALUD del Excel (col 14)');
            $table->foreignId('entidad_pension_id')->nullable()->constrained('entidades_seguridad_social')->comment('ENTIDAD PENSIÓN del Excel (col 15)');
            $table->foreignId('entidad_arl_id')->nullable()->constrained('entidades_seguridad_social')->comment('ENTIDAD ARL del Excel (col 16)');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('es_vigente')->default(true);
            $table->timestamps();

            $table->index('contratista_id');
            $table->index('es_vigente');
        });

        // TABLA 11: Supervisores
        Schema::create('supervisores', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('cargo', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->boolean('es_activo')->default(true);
            $table->timestamps();

            $table->index(['nombres', 'apellidos']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisores');
        Schema::dropIfExists('contratista_seguridad_social');
        Schema::dropIfExists('entidades_seguridad_social');
        Schema::dropIfExists('contratistas');
    }
};
