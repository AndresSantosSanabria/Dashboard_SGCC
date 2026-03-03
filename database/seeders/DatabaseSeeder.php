<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UsuarioSeeder::class,
            UpdateWorkflowStatesSeeder::class,
            SimulationDataSeeder::class,
        ]);
    }
}
