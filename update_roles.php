<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Update Administrador role
$admin = \App\Models\Role::where('nombre', 'Administrador')->first();
if ($admin) {
    $permisos = $admin->permisos;
    $permisos['es_admin'] = true;
    $permisos['acceder_dashboard'] = true;
    $permisos['acceder_workflow'] = true;
    $permisos['responsable_sap'] = true;
    $permisos['responsable_facturacion'] = true;
    $admin->permisos = $permisos;
    $admin->save();
    echo "✅ Rol Administrador actualizado\n";
} else {
    echo "❌ Rol Administrador no encontrado\n";
}

// Update Visualizador role
$visualizador = \App\Models\Role::where('nombre', 'Visualizador')->first();
if ($visualizador) {
    $permisos = $visualizador->permisos;
    $permisos['es_admin'] = false;
    $permisos['acceder_dashboard'] = true;
    $permisos['acceder_workflow'] = true;
    $permisos['responsable_sap'] = false;
    $permisos['responsable_facturacion'] = false;
    $visualizador->permisos = $permisos;
    $visualizador->save();
    echo "✅ Rol Visualizador actualizado\n";
} else {
    echo "❌ Rol Visualizador no encontrado\n";
}

echo "\n🎉 Permisos actualizados correctamente!\n";
echo "Ahora recarga la página y verás el botón 'Configuración' en el sidebar.\n";
