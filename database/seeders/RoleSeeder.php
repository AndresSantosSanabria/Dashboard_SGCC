<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Rol Administrador: Tiene todo en true
        Role::updateOrCreate(
            ['nombre' => 'Administrador'],
            [
                'descripcion' => 'Acceso total y gestión de datos del sistema.',
                'permisos' => [
                    'contratos_ver' => true,
                    'contratos_editar' => true,
                    'cuentas_ver' => true,
                    'cuentas_editar' => true,
                    'usuarios_gestionar' => true,
                    'configuracion_sistema' => true,
                    'es_admin' => true,
                    'acceder_dashboard' => true,     // Acceso a Gestión
                    'acceder_consolidado' => true,   // Acceso a Vista Solo Lectura
                    'acceder_workflow' => true,
                    'editar_workflow' => true,
                    'responsable_sap' => false,
                    'responsable_facturacion' => false,
                    'editar_dashboard' => true,       // Permiso explícito para editar en dashboard
                    'reportes_exportar' => true,
                    'logs_ver' => true,
                ],
                'es_activo' => true
            ]
        );

        // 2. Rol Editor (Gestor): Puede editar pero no configurar el sistema
        Role::updateOrCreate(
            ['nombre' => 'Editor'],
            [
                'descripcion' => 'Gestión operativa de cuentas y contratos.',
                'permisos' => [
                    'contratos_ver' => true,
                    'contratos_editar' => true,      // Puede editar contratos
                    'cuentas_ver' => true,
                    'cuentas_editar' => true,        // Puede editar cuentas
                    'usuarios_gestionar' => false,   // NO puede gestionar usuarios
                    'configuracion_sistema' => false, // NO puede tocar config
                    'es_admin' => false,
                    'acceder_dashboard' => true,     // Puede gestionar
                    'acceder_consolidado' => true,
                    'acceder_workflow' => true,
                    'editar_workflow' => false,      // No edita la estructura del workflow
                    'responsable_sap' => false,      // Se asigna individualmente si aplica
                    'responsable_facturacion' => false,
                    'editar_dashboard' => true,
                    'reportes_exportar' => true,
                    'logs_ver' => false,
                ],
                'es_activo' => true
            ]
        );

        // 3. Rol Visualizador: Solo lectura estricta
        Role::updateOrCreate(
            ['nombre' => 'Visualizador'],
            [
                'descripcion' => 'Consulta de información sin permisos de edición.',
                'permisos' => [
                    'contratos_ver' => true,
                    'contratos_editar' => false,
                    'cuentas_ver' => true,
                    'cuentas_editar' => false,
                    'usuarios_gestionar' => false,
                    'configuracion_sistema' => false,
                    'es_admin' => false,
                    'acceder_dashboard' => false,     // NO accede a gestión
                    'acceder_consolidado' => true,    // SI accede a la vista consolidada (Solución Issue 403)
                    'acceder_workflow' => true,       // Puede ver el flujo
                    'editar_workflow' => false,
                    'responsable_sap' => false,
                    'responsable_facturacion' => false,
                    'editar_dashboard' => false,
                    'reportes_exportar' => false,     // Por seguridad, no exporta data masiva
                    'logs_ver' => false,
                ],
                'es_activo' => true
            ]
        );

        // 4. Rol Auditor: Solo lectura + Logs
        Role::updateOrCreate(
            ['nombre' => 'Auditor'],
            [
                'descripcion' => 'Auditoría y revisión de logs del sistema.',
                'permisos' => [
                    'contratos_ver' => true,
                    'contratos_editar' => false,
                    'cuentas_ver' => true,
                    'cuentas_editar' => false,
                    'usuarios_gestionar' => false,
                    'configuracion_sistema' => false,
                    'es_admin' => false,
                    'acceder_dashboard' => false,
                    'acceder_consolidado' => true,
                    'acceder_workflow' => true,
                    'editar_workflow' => false,
                    'responsable_sap' => false,
                    'responsable_facturacion' => false,
                    'editar_dashboard' => false,
                    'reportes_exportar' => true,      // Auditor puede necesitar evidencia
                    'logs_ver' => true,               // VISUALIZA LOGS
                ],
                'es_activo' => true
            ]
        );
    }
}
