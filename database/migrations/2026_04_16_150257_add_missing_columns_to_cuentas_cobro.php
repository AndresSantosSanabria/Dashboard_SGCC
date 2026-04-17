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
            // Columna ss_ultima_cuenta: Mes de la última cuenta de Seguridad Social finalizada.
            if (!Schema::hasColumn('cuentas_cobro', 'ss_ultima_cuenta')) {
                $table->string('ss_ultima_cuenta', 50)->nullable()->after('observaciones');
            }

            // Columna diferencia_cuentas: Calculada como pagos_totales - facturas_radicadas.
            // Aunque hay un accesor, el controlador intenta persistirla.
            if (!Schema::hasColumn('cuentas_cobro', 'diferencia_cuentas')) {
                $table->integer('diferencia_cuentas')->default(0)->after('porcentaje_cuentas');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuentas_cobro', function (Blueprint $table) {
            $table->dropColumn(['ss_ultima_cuenta', 'diferencia_cuentas']);
        });
    }
};
