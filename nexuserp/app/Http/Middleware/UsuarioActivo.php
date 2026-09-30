<?php

namespace App\Http\Middleware;

use App\Support\Seguridad;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Si desactivan al usuario (o todos sus roles) mientras tiene la sesión abierta, lo saca en su siguiente petición. */
class UsuarioActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();
        if ($user && ! $user->puedeEntrar()) {
            Seguridad::registrar('LOGOUT', $user->username, $user->id_usuario);
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $mensaje = 'Tu usuario está desactivado. Consulta con el administrador.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $mensaje], 401);
            }

            return redirect()->route('login')->withErrors(['login' => $mensaje]);
        }

        return $next($request);
    }
}
