<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permiso;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Crear Permisos Primero (Normalización 3NF)
        $permisosData = [
            ['nombre' => 'Admin de Sistema', 'slug' => 'es_admin', 'modulo' => 'SISTEMA', 'descripcion' => 'Acceso total'],
            ['nombre' => 'Acceder Dashboard', 'slug' => 'acceder_dashboard', 'modulo' => 'GENERAL', 'descripcion' => 'Ver pantalla de inicio'],
            ['nombre' => 'Editar Dashboard', 'slug' => 'editar_dashboard', 'modulo' => 'GENERAL', 'descripcion' => 'Cargar datos y editar configuración'],
            ['nombre' => 'Acceder Workflow', 'slug' => 'acceder_workflow', 'modulo' => 'GENERAL', 'descripcion' => 'Ver procesos'],
            ['nombre' => 'Editar Workflow', 'slug' => 'editar_workflow', 'modulo' => 'GENERAL', 'descripcion' => 'Editar estructura workflow'],
            ['nombre' => 'Acceder Analítica', 'slug' => 'acceder_analitica', 'modulo' => 'BI', 'descripcion' => 'Ver estadísticas'],
            ['nombre' => 'Acceder Consolidado', 'slug' => 'acceder_consolidado', 'modulo' => 'GENERAL', 'descripcion' => 'Vista de solo lectura'],
            ['nombre' => 'Responsable SAP', 'slug' => 'responsable_sap', 'modulo' => 'CARGO', 'descripcion' => 'Rol responsable SAP'],
            ['nombre' => 'Responsable Facturación', 'slug' => 'responsable_facturacion', 'modulo' => 'CARGO', 'descripcion' => 'Rol responsable Facturación'],
            ['nombre' => 'Ver Solo Asignados', 'slug' => 'ver_solo_asignados', 'modulo' => 'SISTEMA', 'descripcion' => 'Restringir vista a asignados'],
            ['nombre' => 'Exportar Reportes', 'slug' => 'reportes_exportar', 'modulo' => 'GENERAL', 'descripcion' => 'Exportar a PDF/Excel'],
            ['nombre' => 'Ver Logs', 'slug' => 'logs_ver', 'modulo' => 'SISTEMA', 'descripcion' => 'Ver auditoría'],
        ];

        foreach ($permisosData as $p) {
            Permiso::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // 1. Rol Administrador
        $admin = Role::updateOrCreate(
            ['nombre' => 'Administrador'],
            ['descripcion' => 'Acceso total y gestión de datos del sistema.', 'es_activo' => true]
        );
        $admin->permisos()->sync(Permiso::all()->pluck('id'));


        // 2. Rol Visualizador
        $visualizador = Role::updateOrCreate(
            ['nombre' => 'Visualizador'],
            ['descripcion' => 'Consulta de información sin permisos de edición.', 'es_activo' => true]
        );
        $visualizador->permisos()->sync(
            Permiso::whereIn('slug', ['acceder_consolidado', 'acceder_workflow'])->pluck('id')
        );

        
    }
}
