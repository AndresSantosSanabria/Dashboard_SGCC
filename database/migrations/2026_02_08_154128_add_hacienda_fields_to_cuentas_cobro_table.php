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
            $table->string('ultima_factura_hacienda', 50)->nullable()->after('observaciones');
            $table->dateTime('fecha_radicacion_hacienda')->nullable()->after('ultima_factura_hacienda');
            $table->text('observacion_hacienda')->nullable()->after('fecha_radicacion_hacienda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuentas_cobro', function (Blueprint $table) {
            $table->dropColumn(['ultima_factura_hacienda', 'fecha_radicacion_hacienda', 'observacion_hacienda']);
        });
    }
};
