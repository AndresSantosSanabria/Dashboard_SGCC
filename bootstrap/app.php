<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\QueryException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Capturar errores de base de datos (QueryException) en la auditoría
        $exceptions->reportable(function (QueryException $e) {
            try {
                $url = request()->fullUrl();
                $method = request()->method();
                \App\Models\Auditoria::create([
                    'usuario_id' => \Illuminate\Support\Facades\Auth::id(),
                    'tabla_afectada' => 'SISTEMA',
                    'registro_id' => 0,
                    'accion' => 'FAILURE_DATABASE',
                    'payload_anterior' => null,
                    'payload_nuevo' => [
                        'error' => $e->getMessage(),
                        'codigo_sql' => $e->getCode(),
                        'clase' => get_class($e),
                        'url' => $url,
                        'metodo' => $method,
                        'ubicacion' => $e->getFile() . ':' . $e->getLine(),
                        'trace' => substr($e->getTraceAsString(), 0, 800),
                    ],
                    'ip_origen' => request()->ip() ?? '127.0.0.1',
                    'user_agent' => substr(request()->userAgent() ?? 'none', 0, 200),
                ]);
            } catch (\Exception $ex) {
                // Si falla la auditoría, al menos loguear en archivo
                \Illuminate\Support\Facades\Log::error('Fallo al auditar QueryException: ' . $ex->getMessage());
            }
        })->stop();

        // Capturar errores generales del servidor (500) que no sean QueryException
        $exceptions->reportable(function (\Throwable $e) {
            // Solo capturar errores graves (no 404, validación, etc.)
            $ignorar = [
                \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
                \Illuminate\Validation\ValidationException::class,
                \Illuminate\Auth\AuthenticationException::class,
                \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
            ];

            if (in_array(get_class($e), $ignorar)) {
                return false;
            }

            try {
                \App\Models\Auditoria::create([
                    'usuario_id' => \Illuminate\Support\Facades\Auth::id(),
                    'tabla_afectada' => 'SISTEMA',
                    'registro_id' => 0,
                    'accion' => 'FAILURE_SERVER',
                    'payload_anterior' => null,
                    'payload_nuevo' => [
                        'error' => $e->getMessage(),
                        'clase' => get_class($e),
                        'url' => request()->fullUrl(),
                        'metodo' => request()->method(),
                        'ubicacion' => $e->getFile() . ':' . $e->getLine(),
                        'trace' => substr($e->getTraceAsString(), 0, 800),
                    ],
                    'ip_origen' => request()->ip() ?? '127.0.0.1',
                    'user_agent' => substr(request()->userAgent() ?? 'none', 0, 200),
                ]);
            } catch (\Exception $ex) {
                \Illuminate\Support\Facades\Log::error('Fallo al auditar excepción: ' . $ex->getMessage());
            }
        })->stop();
    })->create();
