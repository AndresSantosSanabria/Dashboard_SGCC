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
        Schema::table('contratistas', function (Blueprint $table) {
            // Ampliar nit para que quepa el valor cifrado 
            if (Schema::hasColumn('contratistas', 'nit')) {
                $table->string('nit', 500)->change();
            }

            // Blind index para búsqueda segura (SHA-256 HMAC)
            if (!Schema::hasColumn('contratistas', 'nit_blind_index')) {
                $table->string('nit_blind_index', 64)->nullable()->after('nit');
            } else {
                $table->string('nit_blind_index', 64)->nullable()->change();
            }

            // Columnas financieras cifradas
            if (!Schema::hasColumn('contratistas', 'cuenta_bancaria')) {
                $table->string('cuenta_bancaria', 500)->nullable()->after('nit_blind_index');
            } else {
                $table->string('cuenta_bancaria', 500)->nullable()->change();
            }
            
            if (!Schema::hasColumn('contratistas', 'banco')) {
                $table->string('banco', 500)->nullable()->after('cuenta_bancaria');
            } else {
                $table->string('banco', 500)->nullable()->change();
            }
            
            if (!Schema::hasColumn('contratistas', 'tipo_cuenta')) {
                $table->string('tipo_cuenta', 500)->nullable()->after('banco');
            } else {
                $table->string('tipo_cuenta', 500)->nullable()->change();
            }
            
            if (!Schema::hasColumn('contratistas', 'cdp_codigo')) {
                $table->string('cdp_codigo', 500)->nullable()->after('tipo_cuenta');
            } else {
                $table->string('cdp_codigo', 500)->nullable()->change();
            }
        });
        
        // Eliminar el índice antiguo sobre nit 
        try {
            Schema::table('contratistas', function (Blueprint $table) {
                $table->dropIndex(['nit']);
            });
        } catch (\Exception $e) {
        }

        // Crear índice sobre blind_index para búsquedas eficientes
        Schema::table('contratistas', function (Blueprint $table) {
            if (!\Illuminate\Support\Facades\DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'contratistas' AND indexname = 'contratistas_nit_blind_index_index'")) {
                $table->index('nit_blind_index', 'contratistas_nit_blind_index_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contratistas', function (Blueprint $table) {
            $table->string('nit', 20)->change();
            $table->string('nit_blind_index', 255)->nullable()->change();
        });
    }
};
