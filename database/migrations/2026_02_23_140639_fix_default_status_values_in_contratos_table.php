<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Limpiar valores existentes primero para evitar problemas de truncado si se intenta hacer NOT NULL
        $fields = [];
        for ($i = 1; $i <= 12; $i++) {
            $fields["cta{$i}_secop_status"] = '';
            $fields["cta{$i}_sia_status"] = '';
        }
        $fields['soportes_status'] = '';
        $fields['acuerdo_confidencialidad_status'] = '';
        $fields['arl_status'] = '';
        $fields['estudios_previos_status'] = '';
        $fields['idoneidad_status'] = '';
        $fields['clausulado_status'] = '';
        $fields['acta_inicio_status'] = '';
        $fields['delegacion_status'] = '';
        $fields['rpc_status'] = '';

        DB::table('contratos')->update($fields);

        Schema::table('contratos', function (Blueprint $table) {
            // Actualizar 12 cuentas
            for ($i = 1; $i <= 12; $i++) {
                $table->string("cta{$i}_secop_status")->nullable()->default('')->change();
                $table->string("cta{$i}_sia_status")->nullable()->default('')->change();
            }

            // Checklist extendido
            $table->string('soportes_status')->nullable()->default('')->change();
            $table->string('acuerdo_confidencialidad_status')->nullable()->default('')->change();
            $table->string('arl_status')->nullable()->default('')->change();
            $table->string('estudios_previos_status')->nullable()->default('')->change();
            $table->string('idoneidad_status')->nullable()->default('')->change();
            $table->string('clausulado_status')->nullable()->default('')->change();
            $table->string('acta_inicio_status')->nullable()->default('')->change();
            $table->string('delegacion_status')->nullable()->default('')->change();
            $table->string('rpc_status')->nullable()->default('')->change();
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
