<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cuentas_cobro')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $indexes = DB::select("
                SELECT indexname, indexdef
                FROM pg_indexes
                WHERE schemaname = current_schema()
                  AND tablename = 'cuentas_cobro'
            ");

            foreach ($indexes as $index) {
                $indexDef = strtolower($index->indexdef ?? '');
                $indexName = $index->indexname ?? null;

                if (! $indexName) {
                    continue;
                }

                $isOldUnique = str_contains($indexDef, 'unique')
                    && str_contains($indexDef, '(contrato_id')
                    && ! str_contains($indexDef, '(contrato_id, numero_cuenta)')
                    && ! str_contains($indexDef, '(numero_cuenta, contrato_id)');

                if ($isOldUnique) {
                    DB::statement('DROP INDEX IF EXISTS "' . str_replace('"', '""', $indexName) . '"');
                }
            }
        }

        Schema::table('cuentas_cobro', function (Blueprint $table) {
            if (! $this->indexExists('cuentas_cobro', 'cuentas_cobro_contrato_numero_unique')) {
                $table->unique(['contrato_id', 'numero_cuenta'], 'cuentas_cobro_contrato_numero_unique');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $result = DB::selectOne(
                'SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ? LIMIT 1',
                [$table, $indexName]
            );

            return $result !== null;
        }

        return false;
    }

    public function down(): void
    {
        if (! Schema::hasTable('cuentas_cobro')) {
            return;
        }

        Schema::table('cuentas_cobro', function (Blueprint $table) {
            $table->dropUnique('cuentas_cobro_contrato_numero_unique');
        });
    }
};
