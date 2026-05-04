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
        Schema::table('cuentas_cobro', function (Blueprint $table) {
            if (!Schema::hasColumn('cuentas_cobro', 'fecha_ultimo_cambio_estado')) {
                $table->timestamp('fecha_ultimo_cambio_estado')->nullable()->after('ultimo_inicio_conteo');
            }
            if (!Schema::hasColumn('cuentas_cobro', 'tiempo_total_proceso_segundos')) {
                $table->bigInteger('tiempo_total_proceso_segundos')->default(0)->after('fecha_ultimo_cambio_estado');
            }
            if (!Schema::hasColumn('cuentas_cobro', 'diferencia_cuentas')) {
                $table->integer('diferencia_cuentas')->default(0)->after('ss_ultima_cuenta');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuentas_cobro', function (Blueprint $table) {
            $table->dropColumn(['fecha_ultimo_cambio_estado', 'tiempo_total_proceso_segundos', 'diferencia_cuentas']);
        });
    }
};
