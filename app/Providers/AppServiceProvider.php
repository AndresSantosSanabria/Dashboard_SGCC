<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Soporte para hosting en subcarpetas en producción 
        // Detecta el APP_URL del archivo .env y fuerza a Laravel a usarlo como base.
        if (config('app.url') && config('app.url') !== 'http://localhost') {
            URL::forceRootUrl(config('app.url'));
            
            // Si el APP_URL usa HTTPS, forzar que todos los assets y rutas lo usen.
            if (Str::startsWith(config('app.url'), 'https')) {
                URL::forceScheme('https');
            }
        }

        Paginator::useBootstrapFive();
        $this->ensureSchedulerRunning();
    }

    /**
     * Verifica si el scheduler está corriendo. Si no, lo lanza en background.
     * Optimizado para no ralentizar las peticiones HTTP.
     */
    protected function ensureSchedulerRunning(): void
    {
        // Solo correr en contexto HTTP/CLI de servidor
        if ($this->app->runningInConsole() && !$this->isServeCommand()) {
            return;
        }

        try {
            // Usar la caché para evitar comprobaciones de sistema costosas en cada petición
            // Verificamos el estado solo una vez cada 5 minutos
            if (\Illuminate\Support\Facades\Cache::has('scheduler_last_check')) {
                return;
            }

            $lockFile = storage_path('framework/scheduler.pid');
            $isRunning = false;

            if (file_exists($lockFile)) {
                $pid = (int) file_get_contents($lockFile);
                if ($pid > 0) {
                    $isRunning = $this->isProcessRunning($pid);
                }
            }

            if (!$isRunning) {
                $this->launchScheduler($lockFile);
            }

            // Marcar que ya comprobamos el estado (por 5 minutos)
            \Illuminate\Support\Facades\Cache::put('scheduler_last_check', true, 300);

        } catch (\Exception $e) {
            // Silencioso para no romper la app si falla la caché
            Log::warning('Error en auto-scheduler: ' . $e->getMessage());
        }
    }

    /**
     * Lanza el proceso schedule:work en segundo plano.
     */
    protected function launchScheduler(string $lockFile): void
    {
        $artisan = base_path('artisan');
        $phpBin = PHP_BINARY;

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = "Start-Process -WindowStyle Hidden -FilePath \"{$phpBin}\" -ArgumentList \"{$artisan}\", \"schedule:work\", \"--no-interaction\"";
            pclose(popen("powershell -Command \"{$cmd}\"", 'r'));
        } else {
            $cmd = "\"{$phpBin}\" \"{$artisan}\" schedule:work --no-interaction";
            shell_exec("nohup {$cmd} > /dev/null 2>&1 &");
        }
    }

    /**
     * Verifica si el proceso con el PID dado está vivo.
     */
    protected function isProcessRunning(int $pid): bool
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $output = shell_exec("tasklist /FI \"PID eq {$pid}\" /NH 2>NUL");
                return $output && str_contains($output, (string) $pid);
            }
            return file_exists("/proc/{$pid}") || (bool) shell_exec("ps -p {$pid} -o pid= 2>/dev/null");
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Detecta si se está corriendo con `php artisan serve`.
     */
    protected function isServeCommand(): bool
    {
        global $argv;
        return isset($argv[1]) && $argv[1] === 'serve';
    }
}
