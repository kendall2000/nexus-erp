<?php

namespace App\Support;

use App\Models\Core\AuditoriaAcceso;
use App\Models\Core\ConfiguracionSistema;
use App\Models\Core\Usuario;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

/**
 * Seguridad de acceso: parámetros del inicio de sesión (ConfiguracionSistema)
 * y registro en el historial de accesos (auditoria_acceso).
 *
 *  - intentos: intentos fallidos permitidos antes del bloqueo (maxIntentosSesion).
 *  - bloqueo: minutos que dura el bloqueo, por usuario/correo + IP (bloqueoMinutos).
 *  - expira: minutos sin actividad tras los que la sesión se cierra (sesionExpiraMin).
 *  - zona: zona horaria del sistema (zonaHoraria), que AppServiceProvider aplica a PHP y a MySQL.
 */
class Seguridad
{
    private const CACHE = 'config_seguridad';

    /** Minutos de la cookie de «Recordar sesión» (los mismos que usa Laravel por defecto). */
    private const DURACION_RECORDAR = 576000;

    /** @return array{intentos: int, bloqueo: int, expira: int, zona: string} */
    public static function config(): array
    {
        try {
            if (is_array($guardada = Cache::get(self::CACHE))) {
                return $guardada;
            }
            $c = ConfiguracionSistema::obtenerLogin();
        } catch (Throwable) {
            // Sin conexión o sin tabla: valores por defecto, sin guardarlos en caché.
            return ['intentos' => 5, 'bloqueo' => 15, 'expira' => 120, 'zona' => config('app.timezone')];
        }
        $config = [
            'intentos' => max(1, (int) ($c?->maxIntentosSesion ?: 5)),
            'bloqueo' => max(1, (int) ($c?->bloqueoMinutos ?: 15)),
            'expira' => max(5, (int) ($c?->sesionExpiraMin ?: 120)),
            // Zona horaria elegida en Configuración (si no es válida, la de config/app.php).
            'zona' => in_array($c?->zonaHoraria, \DateTimeZone::listIdentifiers(), true) ? $c->zonaHoraria : config('app.timezone'),
        ];
        Cache::put(self::CACHE, $config, 600);

        return $config;
    }

    /** Llamar al guardar la configuración para que los cambios apliquen de inmediato. */
    public static function olvidar(): void
    {
        Cache::forget(self::CACHE);
    }

    /**
     * Busca al usuario por correo o por nombre de usuario (el campo «login»
     * del formulario acepta cualquiera de los dos).
     */
    public static function buscarUsuario(?string $login): ?Usuario
    {
        $login = trim((string) $login);
        if ($login === '') {
            return null;
        }

        return Usuario::query()
            ->where(str_contains($login, '@') ? 'email' : 'username', mb_strtolower($login))
            ->first();
    }

    /**
     * Registra un evento de acceso (ver AuditoriaAcceso::EVENTOS). Nunca
     * interrumpe el inicio de sesión.
     */
    public static function registrar(string $accion, ?string $login, ?int $idUsuario = null, ?string $detalle = null): void
    {
        try {
            $request = request();
            $idUsuario ??= self::buscarUsuario($login)?->id_usuario;
            AuditoriaAcceso::registrar(
                $accion,
                mb_substr(mb_strtolower((string) $login), 0, 60),
                (string) $request?->ip(),
                $idUsuario,
                mb_substr((string) $request?->userAgent(), 0, 500) ?: null,
                $detalle ? mb_substr($detalle, 0, 255) : null,
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Llave del límite de intentos de inicio de sesión: usuario/correo + IP (FortifyServiceProvider). */
    public static function llaveIntentos(?string $login, ?string $ip): string
    {
        return Str::transliterate(Str::lower((string) $login).'|'.$ip);
    }

    /** Llave con la que ThrottleRequests guarda el contador del limitador «login». */
    private static function llaveLimitador(?string $login, ?string $ip): string
    {
        return md5('login'.self::llaveIntentos($login, $ip));
    }

    /**
     * Bloqueos vigentes: usuario/correo + IP que agotaron los intentos y siguen esperando.
     * Los candidatos salen de los fallidos recientes del historial; el contador real está en la caché.
     *
     * @param  \Closure(Builder): mixed|null  $filtro  para limitar a los accesos de una empresa
     * @return Collection<int, object{login: string, ip: string, id_usuario: ?int, fallidos: int, ultimo: string, segundos: int}>
     */
    public static function bloqueos(?\Closure $filtro = null): Collection
    {
        $c = self::config();

        return AuditoriaAcceso::query()
            ->whereIn('accion', ['LOGIN_FAIL', 'BLOQUEO'])
            ->where('created_at', '>=', now()->subMinutes($c['bloqueo']))
            ->when($filtro, $filtro)
            ->groupBy('username_intento', 'ip_address')
            ->selectRaw('username_intento AS login, ip_address AS ip, MAX(id_usuario) AS id_usuario, COUNT(*) AS fallidos, MAX(created_at) AS ultimo')
            ->toBase()->get()
            ->filter(fn ($b) => RateLimiter::tooManyAttempts(self::llaveLimitador($b->login, $b->ip), $c['intentos']))
            ->map(function ($b) {
                $b->id_usuario = $b->id_usuario === null ? null : (int) $b->id_usuario;
                $b->fallidos = (int) $b->fallidos;
                $b->segundos = RateLimiter::availableIn(self::llaveLimitador($b->login, $b->ip));

                return $b;
            })
            ->sortByDesc('ultimo')->values();
    }

    /** Quita el bloqueo de un usuario/correo + IP antes de que venza. */
    public static function desbloquear(string $login, string $ip): void
    {
        RateLimiter::clear(self::llaveLimitador($login, $ip));
    }

    /**
     * Cierra las sesiones abiertas de un usuario, menos la indicada. Devuelve cuántas cerró.
     *
     * Borrar la fila de la sesión no basta: la cookie de «Recordar sesión» volvería a
     * iniciarla sola. Por eso se cambia el token de recordar, lo que invalida esa cookie
     * en todos los dispositivos; el dispositivo que se conserva recibe una cookie nueva.
     */
    public static function cerrarSesiones(int $idUsuario, ?string $excepto = null): int
    {
        $cerradas = DB::table('sessions')->where('user_id', $idUsuario)
            ->when($excepto, fn ($q) => $q->where('id', '!=', $excepto))
            ->delete();

        $token = Str::random(60);
        DB::table('usuario')->where('id_usuario', $idUsuario)->update(['remember_token' => $token]);
        if ($excepto) {
            self::renovarCookieRecordar($idUsuario, $token);
        }

        return $cerradas;
    }

    /** El dispositivo actual conserva su «Recordar sesión», ahora con el token nuevo. */
    private static function renovarCookieRecordar(int $idUsuario, string $token): void
    {
        $guard = Auth::guard('web');
        $user = $guard->user();
        if (! $guard instanceof SessionGuard || (int) $user?->getAuthIdentifier() !== $idUsuario
            || ! request()->cookies->has($guard->getRecallerName())) {
            return;
        }

        $user->setRememberToken($token);
        Cookie::queue(
            $guard->getRecallerName(),
            $idUsuario.'|'.$token.'|'.$guard->hashPasswordForCookie($user->getAuthPassword()),
            self::DURACION_RECORDAR,
        );
    }

    /** «Chrome en Windows», «Safari en iPhone»… a partir del user agent. */
    public static function dispositivo(?string $agente): string
    {
        $a = (string) $agente;
        if ($a === '') {
            return 'Desconocido';
        }
        $navegador = match (true) {
            str_contains($a, 'Edg/') => 'Edge',
            str_contains($a, 'OPR/') || str_contains($a, 'Opera') => 'Opera',
            str_contains($a, 'Firefox/') => 'Firefox',
            str_contains($a, 'Chrome/') => 'Chrome',
            str_contains($a, 'Safari/') => 'Safari',
            default => 'Navegador',
        };
        $sistema = match (true) {
            str_contains($a, 'iPhone') => 'iPhone',
            str_contains($a, 'iPad') => 'iPad',
            str_contains($a, 'Android') => 'Android',
            str_contains($a, 'Windows') => 'Windows',
            str_contains($a, 'Mac OS') => 'Mac',
            str_contains($a, 'Linux') => 'Linux',
            default => 'otro sistema',
        };

        return "{$navegador} en {$sistema}";
    }
}
