<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimiento_campos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 120)->unique();
            $table->string('etiqueta', 150);
            $table->string('tipo', 30)->default('text');
            $table->integer('orden')->default(0);
            $table->boolean('es_activo')->default(true);
            $table->json('configuracion')->nullable();
            $table->timestamps();

            $table->index(['es_activo', 'orden']);
        });

        Schema::create('seguimiento_campo_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seguimiento_campo_id')->constrained('seguimiento_campos')->cascadeOnDelete();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->text('valor_texto')->nullable();
            $table->decimal('valor_decimal', 19, 2)->nullable();
            $table->date('valor_fecha')->nullable();
            $table->json('valor_json')->nullable();
            $table->timestamps();

            $table->unique(['seguimiento_campo_id', 'contrato_id'], 'uk_seguimiento_campo_valor');
            $table->index(['contrato_id', 'seguimiento_campo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimiento_campo_valores');
        Schema::dropIfExists('seguimiento_campos');
    }
};
