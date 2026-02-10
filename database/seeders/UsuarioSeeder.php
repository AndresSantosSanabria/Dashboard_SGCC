<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('nombre', 'Administrador')->first();
        $viewerRole = Role::where('nombre', 'Visualizador')->first();

        // Crear Admin
        Usuario::create([
            'primer_nombre' => 'Admin',
            'primer_apellido' => 'Sistema',
            'user' => 'admin',
            'password' => Hash::make('password123'),
            'rol_id' => $adminRole->id,
            'es_activo' => true,
        ]);

        // Crear Visualizador
        Usuario::create([
            'primer_nombre' => 'Juan',
            'primer_apellido' => 'Consulta',
            'user' => 'viewer',
            'password' => Hash::make('password123'),
            'rol_id' => $viewerRole->id,
            'es_activo' => true,
        ]);
    }
}