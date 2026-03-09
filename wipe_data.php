<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🗑️  Iniciando limpieza de tablas transaccionales...\n";

// Tablas en orden inverso de dependencia o usando CASCADE
$tables = [
    'planillas_seguridad_social',
    'historial_workflow',
    'estados_bloque_cuentas',
    'alertas',
    'registros_presupuestales',
    'cuentas_cobro',
    'contratista_seguridad_social',
    'contratos',
    'contratistas',
    'supervisores',
    'seguimiento_mensual',
    'seguimiento_requisito',
    'auditoria',
    'documentos',
];

DB::transaction(function () use ($tables) {
    // Desactivar temporalmente los triggers de FK para PostgreSQL
    DB::statement('SET session_replication_role = replica;');

    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            echo "  Limpiando tabla: {$table}\n";
            DB::table($table)->delete();
            // Opcional: reiniciar secuencias si es PostgreSQL
            $sequence = $table . '_id_seq';
            try {
                DB::statement("ALTER SEQUENCE IF EXISTS {$sequence} RESTART WITH 1;");
            } catch (\Exception $e) {
                // Ignorar si no tiene secuencia estándar
            }
        }
    }

    DB::statement('SET session_replication_role = DEFAULT;');
});

echo "✅ Base de datos (datos transaccionales) limpia.\n";
echo "💡 Los catálogos (Roles, Usuarios, Estados de Workflow) se conservaron.\n";
