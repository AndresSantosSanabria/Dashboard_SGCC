<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_cobro_id')->nullable()->constrained('cuentas_cobro')->nullOnDelete();
            $table->string('nivel', 20)->nullable();
            $table->string('tipo_alerta', 50)->nullable();
            $table->text('mensaje')->nullable();
            $table->boolean('leida')->default(false);
            $table->foreignId('usuario_destino_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
