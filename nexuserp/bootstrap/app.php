<?php

use App\Http\Middleware\EncabezadosSeguridad;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\RequiereDosFactores;
use App\Http\Middleware\SoloAdministrador;
use App\Http\Middleware\TienePermiso;
use App\Http\Middleware\UsuarioActivo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
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
            RequiereDosFactores::class,
        ]);

        $middleware->alias(['admin' => SoloAdministrador::class, 'permiso' => TienePermiso::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Todo es Blade con sesión: sin API ni respuestas JSON propias.
    })->create();
