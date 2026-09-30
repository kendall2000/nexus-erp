<?php

use App\Http\Middleware\EncabezadosSeguridad;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\SoloAdministrador;
use App\Http\Middleware\TienePermiso;
use App\Http\Middleware\UsuarioActivo;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Detrás del proxy del servidor: IP real del cliente para el historial y el bloqueo por IP.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            EncabezadosSeguridad::class,
            PreventBackHistory::class,
            UsuarioActivo::class,
        ]);

        // La API se autentica con la sesión del navegador (cookie + CSRF), ya no con
        // tokens guardados en el navegador. Mientras se migran los módulos a Blade,
        // las pantallas actuales siguen llamando a /api/v1 con esa misma sesión.
        $middleware->statefulApi();
        $middleware->api(append: [
            EncabezadosSeguridad::class,
            UsuarioActivo::class,
        ]);

        $middleware->alias(['admin' => SoloAdministrador::class, 'permiso' => TienePermiso::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // 401 en JSON para la API (las pantallas actuales redirigen al login al recibirlo).
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu sesión expiró. Vuelve a iniciar sesión.',
                ], 401);
            }
        });
    })->create();
