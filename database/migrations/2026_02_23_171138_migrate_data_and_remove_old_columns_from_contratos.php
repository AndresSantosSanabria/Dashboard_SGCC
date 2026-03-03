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
        // 1. Migrar datos existentes
        $contratos = DB::table('contratos')->get();

        foreach ($contratos as $c) {
            // Migrar Mensuales
            for ($i = 1; $i <= 12; $i++) {
                $secopField = "cta{$i}_secop_status";
                $siaField = "cta{$i}_sia_status";

                if (isset($c->$secopField) && $c->$secopField) {
                    DB::table('seguimiento_mensual')->insertOrIgnore([
                        'contrato_id' => $c->id,
                        'mes' => $i,
                        'fuente' => 'SECOP',
                        'estado' => $c->$secopField,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if (isset($c->$siaField) && $c->$siaField) {
                    DB::table('seguimiento_mensual')->insertOrIgnore([
                        'contrato_id' => $c->id,
                        'mes' => $i,
                        'fuente' => 'SIA',
                        'estado' => $c->$siaField,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Migrar Requisitos y Cierre
            $requisitos = [
                'estudios_previos_status',
                'soportes_status',
                'idoneidad_status',
                'acuerdo_confidencialidad_status',
                'clausulado_status',
                'acta_inicio_status',
                'delegacion_status',
                'arl_status',
                'rpc_status',
                'poliza_status',
                'evaluacion_proveedor_status',
                'acta_cierre_expediente_status',
                'requiere_acta_liq_status',
                'acta_liq_repositorio_status',
                'acta_liq_secop_status',
                'acta_liq_sia_status',
            ];

            foreach ($requisitos as $req) {
                if (isset($c->$req) && $c->$req) {
                    DB::table('seguimiento_requisitos')->insertOrIgnore([
                        'contrato_id' => $c->id,
                        'nombre' => $req,
                        'estado' => $c->$req,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 2. Eliminar columnas viejas
        Schema::table('contratos', function (Blueprint $table) {
            $colsToRemove = [];
            for ($i = 1; $i <= 12; $i++) {
                $colsToRemove[] = "cta{$i}_secop_status";
                $colsToRemove[] = "cta{$i}_sia_status";
            }

            $colsToRemove = array_merge($colsToRemove, [
                'estudios_previos_status',
                'soportes_status',
                'idoneidad_status',
                'acuerdo_confidencialidad_status',
                'clausulado_status',
                'acta_inicio_status',
                'delegacion_status',
                'arl_status',
                'rpc_status',
                'poliza_status',
                'evaluacion_proveedor_status',
                'acta_cierre_expediente_status',
                'requiere_acta_liq_status',
                'acta_liq_repositorio_status',
                'acta_liq_secop_status',
                'acta_liq_sia_status',
            ]);

            $table->dropColumn($colsToRemove);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es reversible fácilmente sin recrear todas las columnas
    }
};
