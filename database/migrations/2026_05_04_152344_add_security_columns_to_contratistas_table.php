<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratistas', function (Blueprint $table) {
            // 1. Ampliar nit para que quepa el valor cifrado (~380 chars en base64)
            //    varchar(500) = tamaño acotado y correcto para valores Laravel encrypt()
            $table->string('nit', 500)->change();

            // 2. Blind index para búsqueda segura (SHA-256 HMAC = siempre 64 chars)
            if (!Schema::hasColumn('contratistas', 'nit_blind_index')) {
                $table->string('nit_blind_index', 64)->nullable()->after('nit');
            }

            // 3. Columnas financieras cifradas (varchar(500) acotado, no TEXT ilimitado)
            if (!Schema::hasColumn('contratistas', 'cuenta_bancaria')) {
                $table->string('cuenta_bancaria', 500)->nullable()->after('nit_blind_index');
            }
            if (!Schema::hasColumn('contratistas', 'banco')) {
                $table->string('banco', 500)->nullable()->after('cuenta_bancaria');
            }
            if (!Schema::hasColumn('contratistas', 'tipo_cuenta')) {
                $table->string('tipo_cuenta', 500)->nullable()->after('banco');
            }
            if (!Schema::hasColumn('contratistas', 'cdp_codigo')) {
                $table->string('cdp_codigo', 500)->nullable()->after('tipo_cuenta');
            }
        });

        // Eliminar el índice antiguo sobre nit (ya no sirve con datos cifrados)
        try {
            Schema::table('contratistas', function (Blueprint $table) {
                $table->dropIndex(['nit']);
            });
        } catch (\Exception $e) {
            // El índice puede no existir; ignorar el error
        }

        // Crear índice sobre blind_index para búsquedas eficientes
        Schema::table('contratistas', function (Blueprint $table) {
            if (!$this->indexExists('contratistas', 'contratistas_nit_blind_index_index')) {
                $table->index('nit_blind_index', 'contratistas_nit_blind_index_index');
            }
        });
    }

    public function down(): void
    {
        // En PostgreSQL, dropColumn y change() NO pueden ir en el mismo bloque
        // → se ejecutan en llamadas separadas a Schema::table

        // 1. Eliminar las columnas nuevas
        Schema::table('contratistas', function (Blueprint $table) {
            $cols = array_filter([
                Schema::hasColumn('contratistas', 'nit_blind_index') ? 'nit_blind_index' : null,
                Schema::hasColumn('contratistas', 'cuenta_bancaria')  ? 'cuenta_bancaria'  : null,
                Schema::hasColumn('contratistas', 'banco')            ? 'banco'            : null,
                Schema::hasColumn('contratistas', 'tipo_cuenta')      ? 'tipo_cuenta'      : null,
                Schema::hasColumn('contratistas', 'cdp_codigo')       ? 'cdp_codigo'       : null,
            ]);

            if (!empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });

        // 2. Revertir nit a varchar(20) y restaurar el índice original
        Schema::table('contratistas', function (Blueprint $table) {
            $table->string('nit', 20)->change();
            $table->index('nit');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select(
            "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
            [$table, $index]
        );
        return !empty($indexes);
    }
};
