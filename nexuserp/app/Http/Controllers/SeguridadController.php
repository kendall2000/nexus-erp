<?php

namespace App\Http\Controllers;

use App\Models\Core\AuditoriaAcceso;
use App\Models\Core\Rol;
use App\Models\Core\Usuario;
use App\Support\Seguridad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * «Seguridad y accesos» (solo administrador): reglas de inicio de sesión,
 * verificación en dos pasos por rol e historial de accesos de su empresa.
 */
class SeguridadController extends Controller
{
    public function index(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;
        $buscar = trim((string) $request->query('buscar'));
        $evento = (string) $request->query('evento');

        // Solo accesos de usuarios de la misma empresa (auditoria_acceso no guarda la empresa).
        $accesos = AuditoriaAcceso::query()
            ->with('usuario:id_usuario,nombre_completo')
            ->whereIn('id_usuario', Usuario::withTrashed()->where('id_empresa', $idEmpresa)->select('id_usuario'))
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w->where('username_intento', 'like', "%{$buscar}%")->orWhere('ip_address', 'like', "%{$buscar}%")))
            ->when(array_key_exists($evento, AuditoriaAcceso::EVENTOS), fn ($q) => $q->where('accion', $evento))
            ->orderByDesc('id_auditoria')
            ->paginate(25)->withQueryString();

        return view('seguridad.index', [
            'config' => Seguridad::config(),
            'roles' => Rol::query()->where('id_empresa', $idEmpresa)->withCount('usuarios')->orderBy('nombre')->get(),
            'accesos' => $accesos,
            'eventos' => AuditoriaAcceso::EVENTOS,
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'max_intentos' => ['required', 'integer', 'min:1', 'max:20'],
            'bloqueo_minutos' => ['required', 'integer', 'min:1', 'max:1440'],
            'sesion_expira_min' => ['required', 'integer', 'min:5', 'max:1440'],
        ]);
        // Igual que la pantalla Configuración: se guarda en todas las filas de ConfiguracionSistema.
        DB::table('ConfiguracionSistema')->update([
            'maxIntentosSesion' => $datos['max_intentos'],
            'bloqueoMinutos' => $datos['bloqueo_minutos'],
            'sesionExpiraMin' => $datos['sesion_expira_min'],
            'actualizadoPor' => $request->user()->id_usuario,
            'fechaActualizacion' => now(),
        ]);
        Seguridad::olvidar();

        return back()->with('status', 'Reglas de inicio de sesión guardadas.');
    }

    public function roles(Request $request): RedirectResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $ids = array_map('intval', (array) $request->input('requiere_2fa', []));
        Rol::query()->where('id_empresa', $idEmpresa)->whereIn('id_rol', $ids)->update(['requiere_2fa' => true]);
        Rol::query()->where('id_empresa', $idEmpresa)->whereNotIn('id_rol', $ids)->update(['requiere_2fa' => false]);

        return back()->with('status', 'Verificación en dos pasos por rol guardada.');
    }
}
