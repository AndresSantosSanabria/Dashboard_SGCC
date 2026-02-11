<?php

use App\Models\CuentaCobro;
use App\Models\EstadoWorkflow;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function testTransitions() {
    echo "🧪 Iniciando prueba de transiciones automáticas...\n";

    // 1. Buscar una cuenta en el primer bloque
    $cuenta = CuentaCobro::where('bloque_actual_id', 1)->first();
    if (!$cuenta) {
        echo "❌ No se encontró ninguna cuenta en el Bloque 1.\n";
        return;
    }

    $estadoPasa = EstadoWorkflow::where('codigo', 'REV1_PASA')->first();
    $estadoSapEsp = EstadoWorkflow::where('codigo', 'SAP_ESP')->first();

    echo "Account ID: {$cuenta->id}\n";
    echo "Estado Actual: {$cuenta->estadoActual->nombre} (Bloque {$cuenta->bloque_actual_id})\n";

    // Simular el cambio de estado a "PASA"
    // Llamamos directamente al controlador o simulamos la lógica
    $controller = new \App\Http\Controllers\WorkflowController();
    $request = new \Illuminate\Http\Request([
        'estado_destino_id' => $estadoPasa->id,
        'comentario' => 'Prueba de automatismo'
    ]);

    echo "➡️ Cambiando a estado 'PASA'...\n";
    
    // Auth mock if needed
    // auth()->loginUsingId(1); 

    try {
        $response = $controller->cambiarEstado($request, $cuenta->id);
        $data = json_decode($response->getContent(), true);

        if ($data['success']) {
            $cuenta->refresh();
            echo "✅ Cambio exitoso.\n";
            echo "Nuevo Estado: {$cuenta->estadoActual->nombre} (ID: {$cuenta->estado_actual_id})\n";
            echo "Nuevo Bloque: {$cuenta->bloque_actual_id} (". ($cuenta->bloqueActual->nombre ?? 'N/A') .")\n";

            if ($cuenta->estado_actual_id == $estadoSapEsp->id) {
                echo "⭐ ¡ÉXITO! El contrato saltó automáticamente a 'en espera ingreso mercancia' en el Bloque 2.\n";
            } else {
                echo "❌ FALLO: El contrato no llegó al estado inicial del Bloque 2.\n";
            }
        } else {
            echo "❌ Error en el controlador: " . $data['message'] . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Excepción: " . $e->getMessage() . "\n";
    }
}

testTransitions();
