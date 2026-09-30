<?php

namespace App\Providers;

use App\Support\Seguridad;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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

        // Minutos sin actividad tras los que se cierra la sesión (ConfiguracionSistema.sesionExpiraMin).
        config(['session.lifetime' => Seguridad::config()['expira']]);
    }
}