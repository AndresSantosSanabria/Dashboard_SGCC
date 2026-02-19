<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->string('estudios_previos_status')->default('ROJO')->change();
            $table->string('idoneidad_status')->default('ROJO')->change();
            $table->string('clausulado_status')->default('ROJO')->change();
            $table->string('rpc_status')->default('ROJO')->change();
            $table->string('acta_inicio_status')->default('ROJO')->change();
            $table->string('delegacion_status')->default('ROJO')->change();
            $table->string('poliza_status')->default('ROJO')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            //
        });
    }
};
