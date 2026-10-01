<?php

namespace App\Providers;

use App\Support\Bitacora;
use App\Support\Seguridad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    private function aplicarZonaHoraria(string $zona): void
    {
        config(['app.timezone' => $zona]);
        date_default_timezone_set($zona);
        try {
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('SET time_zone = ?', [now($zona)->format('P')]);
            }
        } catch (\Throwable) {
            // Sin conexión: se aplicará en la siguiente petición.
        }
    }

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

        $seguridad = Seguridad::config();

        // Minutos sin actividad tras los que se cierra la sesión (ConfiguracionSistema.sesionExpiraMin).
        config(['session.lifetime' => $seguridad['expira']]);

        // Zona horaria de Configuración para PHP y para la sesión de MySQL (las columnas con
        // CURRENT_TIMESTAMP también quedan en hora local). Sin esto todo corría en UTC y «hoy»
        // cambiaba de día a las 18:00 en Guatemala.
        $this->aplicarZonaHoraria($seguridad['zona']);

        // Bitácora de cambios: altas, ediciones y bajas de todos los modelos (auditoria_cambio).
        Bitacora::escuchar();
    }
}
