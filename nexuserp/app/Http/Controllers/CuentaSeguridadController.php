<?php

namespace App\Http\Controllers;

use App\Support\Seguridad;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** «Seguridad de mi cuenta»: contraseña, verificación en dos pasos y sesiones abiertas. */
class CuentaSeguridadController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $actual = $request->session()->getId();
        $sesiones = config('session.driver') !== 'database' ? collect() : DB::table('sessions')
            ->where('user_id', $user->id_usuario)->orderByDesc('last_activity')->get()
            ->map(fn ($s) => [
                'dispositivo' => Seguridad::dispositivo($s->user_agent),
                'ip' => $s->ip_address,
                'actividad' => Carbon::createFromTimestamp($s->last_activity, config('app.timezone'))->format('d/m/Y H:i'),
                'actual' => $s->id === $actual,
            ]);

        return view('cuenta.seguridad', [
            'user' => $user,
            'rolExige' => $user->rolExigeDosPasos(),
            'sesiones' => $sesiones,
        ]);
    }

    /** Cierra todas las demás sesiones del usuario (pide la contraseña). */
    public function cerrarSesiones(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password:web']], [
            'password.current_password' => 'La contraseña no es correcta.',
        ]);
        $user = $request->user();
        $n = Seguridad::cerrarSesiones($user->id_usuario, $request->session()->getId());
        Seguridad::registrar('SESION_CERRADA', $user->username, $user->id_usuario, "{$n} sesiones cerradas por el propio usuario");

        return back()->with('status', $n === 1 ? 'Se cerró 1 sesión.' : "Se cerraron {$n} sesiones.");
    }
}
