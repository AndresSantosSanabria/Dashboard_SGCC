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
            if (!Schema::hasColumn('contratos', 'tipo_contratista')) {
                $table->string('tipo_contratista')->nullable()->after('supervisor_id');
            }
            if (!Schema::hasColumn('contratos', 'secop_estado_contrato')) {
                $table->string('secop_estado_contrato')->nullable()->after('plazo_ejecucion');
            }
            if (!Schema::hasColumn('contratos', 'abogado_responsable')) {
                $table->string('abogado_responsable')->nullable()->after('razon_no_liquidacion');
            }
            if (!Schema::hasColumn('contratos', 'contador_responsable')) {
                $table->string('contador_responsable')->nullable()->after('abogado_responsable');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropColumn(['tipo_contratista', 'secop_estado_contrato', 'abogado_responsable', 'contador_responsable']);
        });
    }
};
