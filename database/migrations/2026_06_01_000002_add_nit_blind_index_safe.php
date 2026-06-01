<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SOLUCIÓN SIMPLE: Solo agregar la columna si no existe
        if (!Schema::hasColumn('contratistas', 'nit_blind_index')) {
            Schema::table('contratistas', function (Blueprint $table) {
                $table->string('nit_blind_index', 64)->nullable()->after('nit');
            });
        }
        
        // Asegurar que nit es suficientemente grande para datos cifrados
        if (Schema::hasColumn('contratistas', 'nit')) {
            try {
                Schema::table('contratistas', function (Blueprint $table) {
                    $table->string('nit', 500)->change();
                });
            } catch (\Exception $e) {
                // Puede fallar si ya está al tamaño correcto
            }
        }
        
        // Crear índice solo si no existe
        $indexExists = DB::select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'contratistas' AND indexname = 'contratistas_nit_blind_index_index'"
        );
        
        if (empty($indexExists)) {
            try {
                Schema::table('contratistas', function (Blueprint $table) {
                    $table->index('nit_blind_index', 'contratistas_nit_blind_index_index');
                });
            } catch (\Exception $e) {
                \Log::warning('Could not create index on nit_blind_index: ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        Schema::table('contratistas', function (Blueprint $table) {
            if (Schema::hasColumn('contratistas', 'nit_blind_index')) {
                $table->dropColumn('nit_blind_index');
            }
        });
    }
};
