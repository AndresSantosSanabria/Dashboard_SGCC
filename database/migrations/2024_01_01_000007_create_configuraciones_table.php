<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique()->comment('Ej: SLA_GLOBAL_HORAS');
            $table->text('valor')->nullable();
            $table->string('tipo_dato', 20)->nullable()->comment('STRING, INT, BOOL, JSON');
            $table->text('descripcion')->nullable();
            $table->foreignId('modificado_por_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
