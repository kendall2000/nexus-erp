<?php

namespace App\Providers;

use App\Support\Seguridad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Forzar HTTPS si la request llega por HTTPS
        // (funciona detrás de reverse-proxy)
        if (request()->isSecure() || request()->server('HTTP_X_FORWARDED_PROTO') === 'https') {
            URL::forceScheme('https');
        }

        // En producción no se permiten migrate:fresh, db:wipe ni similares.
        DB::prohibitDestructiveCommands(app()->isProduction());

        // Contraseñas: 12+ caracteres con mayúsculas, minúsculas, números y símbolos.
        // En producción además se rechazan las que aparecen en filtraciones conocidas.
        Password::defaults(function (): Password {
            $regla = Password::min(12)->mixedCase()->letters()->numbers()->symbols();

            return app()->isProduction() ? $regla->uncompromised() : $regla;
        });

        // Minutos sin actividad tras los que se cierra la sesión (ConfiguracionSistema.sesionExpiraMin).
        config(['session.lifetime' => Seguridad::config()['expira']]);
    }
}
