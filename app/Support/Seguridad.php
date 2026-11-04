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
        $login = self::normalizarLogin($login);
        if ($login === '') {
            return null;
        }

        return Usuario::query()
            ->where(str_contains($login, '@') ? 'email' : 'username', $login)
            ->first();
    }

    /**
     * Usuario o correo tal como se guarda: sin espacios ni caracteres invisibles (ancho cero,
     * marcas de dirección…) y en minúsculas. MySQL ignora los invisibles al comparar, así que
     * sin esto «admin» y «admin\u{200B}» serían la misma cuenta con contadores de intentos distintos.
     */
    public static function normalizarLogin(?string $login): string
    {
        return mb_strtolower((string) preg_replace('/[\p{Cc}\p{Cf}\p{Z}]+/u', '', (string) $login));
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

    /**
     * Llave del límite de intentos de inicio de sesión: cuenta + IP (FortifyServiceProvider).
     * Si el usuario existe se usa su id, así da igual que escriba el usuario o el correo
     * (o una variante con mayúsculas o invisibles): todos los intentos cuentan para la misma cuenta.
     */
    public static function llaveIntentos(?string $login, ?string $ip): string
    {
        $usuario = self::buscarUsuario($login);

        return ($usuario ? 'usuario:'.$usuario->id_usuario : Str::transliterate(self::normalizarLogin($login))).'|'.$ip;
    }

    /**
     * Bloqueo de la cuenta sin importar la IP (bloqueado_hasta): frena a quien prueba
     * contraseñas desde muchas IPs. Se permite el doble de intentos que por IP para que
     * un tercero no pueda bloquear a alguien con facilidad.
     */
    public static function intentosCuenta(): int
    {
        return self::config()['intentos'] * 2;
    }

    /** Suma un fallido a la cuenta y la bloquea si llegó al límite. Devuelve si quedó bloqueada. */
    public static function fallidoDeCuenta(Usuario $usuario): bool
    {
        $c = self::config();
        $llave = 'cuenta|'.$usuario->id_usuario;
        $intentos = RateLimiter::hit($llave, $c['bloqueo'] * 60);
        $bloquear = $intentos >= self::intentosCuenta();
        $usuario->forceFill([
            'intentos_fallidos' => min(255, $intentos),
            'bloqueado_hasta' => $bloquear ? now()->addMinutes($c['bloqueo']) : $usuario->bloqueado_hasta,
        ])->saveQuietly();
        if ($bloquear) {
            RateLimiter::clear($llave);
        }

        return $bloquear;
    }

    /** Quita el bloqueo de la cuenta y reinicia su contador (al entrar bien o al desbloquear). */
    public static function limpiarCuenta(Usuario $usuario): void
    {
        RateLimiter::clear('cuenta|'.$usuario->id_usuario);
        if ($usuario->intentos_fallidos || $usuario->bloqueado_hasta) {
            $usuario->forceFill(['intentos_fallidos' => 0, 'bloqueado_hasta' => null])->saveQuietly();
        }
    }

    /** Llave con la que ThrottleRequests guarda el contador del limitador «login». */
    private static function llaveLimitador(?string $login, ?string $ip): string
    {
        return md5('login'.self::llaveIntentos($login, $ip));
    }

    /**
     * Bloqueos vigentes: cuenta + IP que agotaron los intentos y siguen esperando, y cuentas
     * bloqueadas en todas las IPs (bloqueado_hasta, «ip» = null). Los candidatos por IP salen
     * de los fallidos recientes del historial; el contador real está en la caché.
     *
     * @param  \Closure(Builder): mixed|null  $filtro  para limitar a los accesos de una empresa
     * @return Collection<int, object{login: string, ip: ?string, id_usuario: ?int, fallidos: int, ultimo: string, segundos: int}>
     */
    public static function bloqueos(?\Closure $filtro = null, ?int $idEmpresa = null): Collection
    {
        $c = self::config();

        $porIp = AuditoriaAcceso::query()
            ->whereIn('accion', ['LOGIN_FAIL', 'BLOQUEO'])
            ->where('created_at', '>=', now()->subMinutes($c['bloqueo']))
            ->when($filtro, $filtro)
            ->groupBy('username_intento', 'ip_address')
            ->selectRaw('username_intento AS login, ip_address AS ip, MAX(id_usuario) AS id_usuario, COUNT(*) AS fallidos, MAX(created_at) AS ultimo')
            ->toBase()->get()
            // Varias formas de escribir la misma cuenta (usuario, correo…) comparten un solo contador.
            ->groupBy(fn ($b) => self::llaveLimitador($b->login, $b->ip))
            ->filter(fn ($grupo, $llave) => RateLimiter::tooManyAttempts($llave, $c['intentos']))
            ->map(function (Collection $grupo, string $llave) {
                $b = $grupo->sortByDesc('ultimo')->first();
                $b->id_usuario = $grupo->max('id_usuario') === null ? null : (int) $grupo->max('id_usuario');
                $b->fallidos = (int) $grupo->sum('fallidos');
                $b->segundos = RateLimiter::availableIn($llave);

                return $b;
            });

        $porCuenta = Usuario::query()
            ->where('bloqueado_hasta', '>', now())
            ->when($idEmpresa, fn ($q) => $q->where('id_empresa', $idEmpresa))
            ->get()
            ->map(fn (Usuario $u) => (object) [
                'login' => $u->username, 'ip' => null, 'id_usuario' => (int) $u->id_usuario,
                'fallidos' => (int) $u->intentos_fallidos, 'ultimo' => (string) $u->bloqueado_hasta,
                'segundos' => (int) max(1, now()->diffInSeconds($u->bloqueado_hasta)),
            ]);

        return $porCuenta->concat($porIp->sortByDesc('ultimo'))->values();
    }

    /** Quita el bloqueo de un usuario/correo + IP, o el de la cuenta en todas las IPs si no hay IP. */
    public static function desbloquear(string $login, ?string $ip): void
    {
        if ($ip === null) {
            if ($usuario = self::buscarUsuario($login)) {
                self::limpiarCuenta($usuario);
            }

            return;
        }
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
            (int) config('auth.guards.web.remember', 43200),
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
