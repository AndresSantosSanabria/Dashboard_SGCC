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
        // Rol Administrador: Tiene todo en true
        Role::create([
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso total y gestión de datos del sistema.',
            'permisos' => [
                'contratos_ver' => true,
                'contratos_editar' => true,
                'cuentas_ver' => true,
                'cuentas_editar' => true,
                'usuarios_gestionar' => true,
                'configuracion_sistema' => true,
            ],
            'es_activo' => true
        ]);

        // Rol Visualizador: Solo ver
        Role::create([
            'nombre' => 'Visualizador',
            'descripcion' => 'Consulta de información sin permisos de edición.',
            'permisos' => [
                'contratos_ver' => true,
                'contratos_editar' => false,
                'cuentas_ver' => true,
                'cuentas_editar' => false,
                'usuarios_gestionar' => false,
                'configuracion_sistema' => false,
            ],
            'es_activo' => true
        ]);
    }
}