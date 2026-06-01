<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRÍTICA: Corrige la inconsistencia de unidades en tiempo_en_estado_anterior_minutos
     * 
     * El campo fue mal nombrado (_minutos) pero usado para guardar SEGUNDOS.
     * Algunos registros históricos tienen MINUTOS debido a código antiguo defectuoso.
     * 
     * Esta migración:
     * 1. Detecta valores menores a 86,400 (1 día en segundos)
     * 2. Heurística: Si < 3,600 segundos (1h), probablemente sea minutos históricos
     * 3. Convierte minutos → segundos (* 60)
     * 4. Preserva valores que ya son correctos
     */
    public function up(): void
    {
        // Obtener todos los registros con tiempo_en_estado_anterior_minutos
        $records = DB::table('historial_workflow')
            ->whereNotNull('tiempo_en_estado_anterior_minutos')
            ->where('tiempo_en_estado_anterior_minutos', '>', 0)
            ->get();

        foreach ($records as $record) {
            $valor = $record->tiempo_en_estado_anterior_minutos;
            
            /**
             * HEURÍSTICA DE DETECCIÓN:
             * - Si el valor es pequeño y no múltiplo de 3600 (1 hora), probablemente sea MINUTOS
             * - Si valor < 3600 y > 0, DEFINITIVAMENTE es minuto (< 1 hora en segundos)
             * - Si 3600 <= valor < 86400 y NOT múltiplo de 3600, probablemente sea minutos
             */
            $esMinutos = false;
            
            // Caso 1: Claramente menores a 1 hora en segundos → son MINUTOS
            if ($valor < 3600 && $valor > 0) {
                $esMinutos = true;
            }
            // Caso 2: Entre 1h y 1 día, pero no es múltiplo de 3600 → probablemente minutos
            elseif ($valor >= 3600 && $valor < 86400 && ($valor % 3600) !== 0) {
                $esMinutos = true;
            }
            // Caso 3: Valores muy grandes (> 1 año en segundos) - verificar
            // Si es > 31,536,000 (1 año), probablemente sean MINUTOS (valor antiguo)
            elseif ($valor > 31536000) {
                // Podría ser minutos: convertir solo si /60 resulta en un valor razonable
                $comoSegundos = $valor / 60;
                if ($comoSegundos < 31536000) { // Si convertido cabe en 1 año
                    $esMinutos = true;
                }
            }

            // Aplicar conversión si es necesario
            if ($esMinutos) {
                $nuevoValor = $valor * 60; // Convertir MINUTOS → SEGUNDOS
                DB::table('historial_workflow')
                    ->where('id', $record->id)
                    ->update(['tiempo_en_estado_anterior_minutos' => $nuevoValor]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertir: la conversión es unidireccional
        // Si se necesita deshacer, se debe restaurar desde backup
    }
};
