<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permiso;
use App\Models\Role;
use App\Models\Usuario;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            ['nombre' => 'Admin de Sistema', 'slug' => 'es_admin', 'modulo' => 'SISTEMA', 'descripcion' => 'Acceso total'],
            ['nombre' => 'Acceder Dashboard', 'slug' => 'acceder_dashboard', 'modulo' => 'GENERAL', 'descripcion' => 'Ver pantalla de inicio'],
            ['nombre' => 'Acceder Workflow', 'slug' => 'acceder_workflow', 'modulo' => 'GENERAL', 'descripcion' => 'Ver procesos'],
            ['nombre' => 'Acceder Analítica', 'slug' => 'acceder_analitica', 'modulo' => 'BI', 'descripcion' => 'Ver estadísticas'],
            ['nombre' => 'Editar Dashboard', 'slug' => 'editar_dashboard', 'modulo' => 'GENERAL', 'descripcion' => 'Cargar datos y editar configuración'],
        ];

        foreach ($permisos as $p) {
            Permiso::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // Asignar todos al Administrador
        $adminRole = Role::where('nombre', 'Administrador')->first();
        if ($adminRole) {
            $allPerms = Permiso::all()->pluck('id');
            $adminRole->permisos()->sync($allPerms);
        }

        // También asignar individualmente al usuario admin si existe
        $adminUser = Usuario::where('user', 'admin')->first();
        if ($adminUser) {
            $adminUser->individualPermissions()->sync(Permiso::all()->pluck('id'));
        }
    }
}
