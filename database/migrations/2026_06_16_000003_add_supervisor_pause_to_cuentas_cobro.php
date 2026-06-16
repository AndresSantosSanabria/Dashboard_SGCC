<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la marca de pausa específica para devoluciones a supervisor.
     */
    public function up(): void
    {
        Schema::table('cuentas_cobro', function (Blueprint $table) {
            if (! Schema::hasColumn('cuentas_cobro', 'pausa_gestion_supervisor_desde')) {
                $table->timestamp('pausa_gestion_supervisor_desde')
                    ->nullable()
                    ->after('fecha_ultimo_cambio_estado');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuentas_cobro', function (Blueprint $table) {
            if (Schema::hasColumn('cuentas_cobro', 'pausa_gestion_supervisor_desde')) {
                $table->dropColumn('pausa_gestion_supervisor_desde');
            }
        });
    }
};
