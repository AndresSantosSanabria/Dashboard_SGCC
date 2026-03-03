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
            $table->string('link_secop')->nullable()->after('cdp_codigo');

            // Campos de Checklist Documental
            $table->string('estudios_previos_status')->default('Rojo')->comment('Verde, Amarillo, Rojo');
            $table->string('idoneidad_status')->default('Rojo')->comment('Verde, Amarillo, Rojo');
            $table->string('clausulado_status')->default('Rojo')->comment('Verde, Amarillo, Rojo');
            $table->string('rpc_status')->default('Rojo')->comment('Verde, Amarillo, Rojo');
            $table->string('acta_inicio_status')->default('Rojo')->comment('Verde, Amarillo, Rojo');
            $table->string('delegacion_status')->default('Rojo')->comment('Verde, Amarillo, Rojo');
            $table->string('poliza_status')->default('Rojo')->comment('Verde, Amarillo, Rojo');
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropColumn([
                'link_secop',
                'estudios_previos_status',
                'idoneidad_status',
                'clausulado_status',
                'rpc_status',
                'acta_inicio_status',
                'delegacion_status',
                'poliza_status',
            ]);
        });
    }
};
