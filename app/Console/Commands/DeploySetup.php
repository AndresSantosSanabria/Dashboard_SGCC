<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DeploySetup extends Command
{
    protected $signature = 'app:deploy {--fresh : Reinicia toda la base de datos}';
    protected $description = 'Ejecuta todas las migraciones, seeders y configura el sistema para producción';

    public function handle(): int
    {
        $this->info('🚀 Iniciando configuración del sistema SGCC...');
        $this->newLine();

        // 1. Migraciones
        $this->info('📦 Corriendo migraciones...');
        if ($this->option('fresh')) {
            Artisan::call('migrate:fresh', ['--force' => true], $this->output);
        } else {
            Artisan::call('migrate', ['--force' => true], $this->output);
        }

        // 2. Seeders
        $this->info('🌱 Cargando datos iniciales...');
        Artisan::call('db:seed', ['--force' => true], $this->output);

        // 3. Optimizaciones para producción
        $this->info('⚡ Optimizando caches...');
        Artisan::call('config:cache', [], $this->output);
        Artisan::call('route:cache', [], $this->output);
        Artisan::call('view:cache', [], $this->output);

        // 4. Limpiar PID anterior del scheduler para que se relance limpio
        $pidFile = storage_path('framework/scheduler.pid');
        if (file_exists($pidFile)) {
            unlink($pidFile);
            $this->line('  ↺ PID del scheduler reiniciado.');
        }

        $this->newLine();
        $this->info('✅ Sistema listo. El scheduler de alertas se iniciará automáticamente con la primera petición.');
        $this->line('   Para ejecutar el scheduler manualmente: <comment>php artisan schedule:work</comment>');
        $this->line('   Para verificar alertas inmediatamente: <comment>php artisan contracts:check-stagnation</comment>');

        return self::SUCCESS;
    }
}
