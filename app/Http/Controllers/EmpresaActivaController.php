<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EmpresaActiva;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Cambiar la empresa en la que se trabaja (selector de la barra superior). */
class EmpresaActivaController extends Controller
{
    public function cambiar(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        $datos = $request->validate(['id_empresa' => ['required', 'integer']]);
        $id = (int) $datos['id_empresa'];
        abort_unless($usuario->puedeUsarEmpresa($id), 403, 'No tienes acceso a esa empresa.');

        if ($id === $usuario->idEmpresaPrincipal()) {
            $request->session()->forget(EmpresaActiva::SESION);
        } else {
            $request->session()->put(EmpresaActiva::SESION, $id);
        }
        $nombre = $usuario->empresasDisponibles()->firstWhere('id_empresa', $id)?->nombre_comercial;

        // Al inicio y no «atrás»: el registro que estaba viendo es de la otra empresa.
        return redirect()->route('dashboard')->with('status', "Ahora trabajas en {$nombre}.");
    }
}
