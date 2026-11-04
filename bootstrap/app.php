<?php

use App\Http\Middleware\ContrasenaVencida;
use App\Http\Middleware\EmpresaActiva;
use App\Http\Middleware\EncabezadosSeguridad;
use App\Http\Middleware\LimitarRecuperacion;
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
        // Solo se cree el X-Forwarded-For de los proxies de confianza (por defecto, redes privadas
        // como la de Docker); con «*» cualquiera podría inventarse una IP y saltarse el bloqueo.
        $middleware->trustProxies(at: array_values(array_filter(array_map('trim', explode(',',
            (string) env('TRUSTED_PROXIES', '127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16')
        )))));

        $middleware->web(append: [
            EncabezadosSeguridad::class,
            PreventBackHistory::class,
            LimitarRecuperacion::class,
            UsuarioActivo::class,
            EmpresaActiva::class, // antes de los permisos de cada ruta: estos dependen de la empresa
            ContrasenaVencida::class,
            RequiereDosFactores::class,
        ]);

        // El navegador manda los avisos de la CSP sin token CSRF (ver ReporteCspController).
        $middleware->validateCsrfTokens(except: ['csp-reporte']);

        $middleware->alias(['admin' => SoloAdministrador::class, 'permiso' => TienePermiso::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Todo es Blade con sesión: sin API ni respuestas JSON propias.
    })->create();
