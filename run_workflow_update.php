<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Ejecutando script SQL de actualización de workflow...\n\n";

try {
    $sqlFile = __DIR__ . '/database/update_workflow_states.sql';

    if (!file_exists($sqlFile)) {
        die("ERROR: No se encontró el archivo SQL en: $sqlFile\n");
    }

    $sql = file_get_contents($sqlFile);

    echo "Archivo SQL cargado correctamente.\n";
    echo "Ejecutando queries...\n\n";

    DB::unprepared($sql);

    echo "✓ Script ejecutado exitosamente!\n\n";

    // Verificar resultados
    echo "Verificando bloques creados:\n";
    $bloques = DB::table('bloques_workflow')->orderBy('orden')->get();
    foreach ($bloques as $bloque) {
        echo "  - {$bloque->nombre} (orden: {$bloque->orden})\n";
    }

    echo "\nVerificando estados creados:\n";
    $estados = DB::table('estados_workflow')
        ->join('bloques_workflow', 'estados_workflow.bloque_id', '=', 'bloques_workflow.id')
        ->select('bloques_workflow.nombre as bloque', 'estados_workflow.nombre as estado', 'estados_workflow.tipo')
        ->orderBy('bloques_workflow.orden')
        ->orderBy('estados_workflow.id')
        ->get();

    $currentBloque = '';
    foreach ($estados as $estado) {
        if ($currentBloque !== $estado->bloque) {
            $currentBloque = $estado->bloque;
            echo "\n  {$currentBloque}:\n";
        }
        echo "    - {$estado->estado} ({$estado->tipo})\n";
    }

    echo "\n✓ Total de estados: " . count($estados) . "\n";

    echo "\nVerificando transiciones:\n";
    $transiciones = DB::table('transiciones_permitidas')->where('es_activa', true)->count();
    echo "  - Total de transiciones activas: {$transiciones}\n";

    echo "\n✓✓✓ ACTUALIZACIÓN COMPLETADA EXITOSAMENTE ✓✓✓\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
