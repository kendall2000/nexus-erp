<?php

namespace App\Support;

use App\Models\Core\ConfiguracionSistema;
use App\Models\Core\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Throwable;

/**
 * Política de contraseñas (ConfiguracionSistema, pantalla «Seguridad y accesos»).
 *
 *  - minimo: largo mínimo (passwordMinimo, entre 8 y 64).
 *  - mayusculas / numeros / simbolos: complejidad exigida.
 *  - vence: días de vigencia; 0 = no vence (passwordVenceDias).
 *  - historial: cuántas de las últimas contraseñas no se pueden repetir, contando la actual;
 *    0 = sin control (passwordHistorial).
 *
 * Toda contraseña nueva se guarda con cambiar(), que lleva el historial y la fecha del cambio.
 */
class Contrasenas
{
    private const CACHE = 'politica_contrasenas';

    /** Hashes anteriores que se conservan por usuario (el máximo que se puede configurar). */
    public const HISTORIAL_MAXIMO = 24;

    /** Días antes del vencimiento en que se avisa al usuario. */
    public const DIAS_AVISO = 7;

    /** @return array{minimo: int, mayusculas: bool, numeros: bool, simbolos: bool, vence: int, historial: int} */
    public static function politica(): array
    {
        $porDefecto = ['minimo' => 12, 'mayusculas' => true, 'numeros' => true, 'simbolos' => true, 'vence' => 0, 'historial' => 0];
        try {
            if (is_array($guardada = Cache::get(self::CACHE))) {
                return $guardada;
            }
            $c = ConfiguracionSistema::obtenerLogin();
        } catch (Throwable) {
            return $porDefecto; // sin conexión o sin tabla: la regla de siempre, sin guardarla en caché
        }
        $politica = $c?->passwordMinimo === null ? $porDefecto : [
            'minimo' => min(64, max(8, (int) $c->passwordMinimo)),
            'mayusculas' => (bool) $c->passwordMayusculas,
            'numeros' => (bool) $c->passwordNumeros,
            'simbolos' => (bool) $c->passwordSimbolos,
            'vence' => max(0, (int) $c->passwordVenceDias),
            'historial' => min(self::HISTORIAL_MAXIMO, max(0, (int) $c->passwordHistorial)),
        ];
        Cache::put(self::CACHE, $politica, 600);

        return $politica;
    }

    public static function olvidar(): void
    {
        Cache::forget(self::CACHE);
    }

    /** Regla de validación (Password::defaults). En producción también rechaza contraseñas filtradas. */
    public static function regla(): Password
    {
        $p = self::politica();
        $regla = Password::min($p['minimo'])->letters();
        $regla = $p['mayusculas'] ? $regla->mixedCase() : $regla;
        $regla = $p['numeros'] ? $regla->numbers() : $regla;
        $regla = $p['simbolos'] ? $regla->symbols() : $regla;

        return app()->isProduction() ? $regla->uncompromised() : $regla;
    }

    /** «Mínimo 12 caracteres, con mayúsculas y minúsculas, números y símbolos.» */
    public static function requisitos(): string
    {
        $p = self::politica();
        $con = array_filter([
            $p['mayusculas'] ? 'mayúsculas y minúsculas' : 'letras',
            $p['numeros'] ? 'números' : null,
            $p['simbolos'] ? 'símbolos' : null,
        ]);
        $ultima = array_pop($con);
        $texto = "Mínimo {$p['minimo']} caracteres, con ".($con ? implode(', ', $con).' y ' : '').$ultima.'.';
        if ($p['historial'] > 1) {
            $texto .= " No puede ser ninguna de tus últimas {$p['historial']}.";
        } elseif ($p['historial'] === 1) {
            $texto .= ' No puede ser la actual.';
        }

        return $texto;
    }

    /** ¿La contraseña es una de las últimas que la política no deja repetir? */
    public static function repetida(Usuario $usuario, string $plano): bool
    {
        $n = self::politica()['historial'];
        if ($n === 0) {
            return false;
        }
        $hashes = [$usuario->password_hash, ...DB::table('historial_password')->where('id_usuario', $usuario->id_usuario)
            ->orderByDesc('id_historial')->limit($n - 1)->pluck('password_hash')->all()];

        foreach (array_filter($hashes) as $hash) {
            if (Hash::check($plano, $hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Guarda la contraseña nueva: pasa la anterior al historial, anota la fecha y si debe
     * cambiarla al entrar (cuando la pone otra persona).
     */
    public static function cambiar(Usuario $usuario, string $plano, bool $debeCambiar = false): void
    {
        if ($usuario->exists && $usuario->getRawOriginal('password_hash')) {
            DB::table('historial_password')->insert([
                'id_usuario' => $usuario->id_usuario, 'password_hash' => $usuario->getRawOriginal('password_hash'), 'created_at' => now(),
            ]);
            $sobran = DB::table('historial_password')->where('id_usuario', $usuario->id_usuario)
                ->orderByDesc('id_historial')->skip(self::HISTORIAL_MAXIMO)->take(PHP_INT_MAX)->pluck('id_historial');
            DB::table('historial_password')->whereIn('id_historial', $sobran)->delete();
        }

        $usuario->forceFill([
            'password_hash' => Hash::make($plano),
            'password_cambiado_at' => now(),
            'debe_cambiar_password' => $debeCambiar,
        ])->save();
    }

    /** Fecha en que vence la contraseña, o null si la política no la hace vencer. */
    public static function venceEl(Usuario $usuario): ?Carbon
    {
        $dias = self::politica()['vence'];
        if ($dias === 0) {
            return null;
        }

        return Carbon::parse($usuario->password_cambiado_at ?? $usuario->created_at ?? now())->addDays($dias);
    }

    /** ¿Tiene que cambiarla antes de seguir? (la puso el administrador o ya venció). */
    public static function debeCambiar(Usuario $usuario): bool
    {
        return (bool) $usuario->debe_cambiar_password || (self::venceEl($usuario)?->isPast() ?? false);
    }
}
