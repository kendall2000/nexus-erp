<?php

namespace App\Http\Controllers;

use App\Models\Core\AuditoriaAcceso;
use App\Models\Core\Rol;
use App\Models\Core\Usuario;
use App\Support\Bitacora;
use App\Support\ExportarCsv;
use App\Support\Seguridad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * «Seguridad y accesos» (solo administrador): reglas de inicio de sesión, verificación en
 * dos pasos por rol, bloqueos vigentes (con desbloqueo) e historial de accesos de su empresa.
 */
class SeguridadController extends Controller
{
    public function index(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;
        $desde24 = now()->subDay();
        $resumen = $this->deMiEmpresa($request)->where('created_at', '>=', $desde24)
            ->selectRaw("SUM(CASE WHEN accion = 'LOGIN_OK' THEN 1 ELSE 0 END) AS ingresos")
            ->selectRaw("SUM(CASE WHEN accion IN ('LOGIN_FAIL', 'LOGIN_FAIL_2FA') THEN 1 ELSE 0 END) AS fallidos")
            ->selectRaw("SUM(CASE WHEN accion = 'BLOQUEO' THEN 1 ELSE 0 END) AS bloqueos")
            ->selectRaw("COUNT(DISTINCT CASE WHEN accion IN ('LOGIN_FAIL', 'LOGIN_FAIL_2FA', 'BLOQUEO') THEN ip_address END) AS ips")
            ->toBase()->first();

        return view('seguridad.index', [
            'config' => Seguridad::config(),
            'roles' => Rol::query()->where('id_empresa', $idEmpresa)->withCount('usuarios')->orderBy('nombre')->get(),
            'accesos' => $this->filtrados($request)->with('usuario:id_usuario,nombre_completo')->orderByDesc('id_auditoria')->paginate(25)->withQueryString(),
            'eventos' => AuditoriaAcceso::EVENTOS,
            'usuarios' => Usuario::withTrashed()->where('id_empresa', $idEmpresa)->orderBy('nombre_completo')->get(['id_usuario', 'nombre_completo', 'username']),
            'resumen' => $resumen,
            'bloqueos' => Seguridad::bloqueos(fn ($q) => $this->soloMiEmpresa($q, $request)),
            'ipsSospechosas' => $this->deMiEmpresa($request)->whereIn('accion', ['LOGIN_FAIL', 'LOGIN_FAIL_2FA', 'BLOQUEO'])
                ->where('created_at', '>=', now()->subDays(7))->whereNotNull('ip_address')
                ->groupBy('ip_address')->selectRaw('ip_address, COUNT(*) AS intentos, COUNT(DISTINCT username_intento) AS cuentas, MAX(created_at) AS ultimo')
                ->orderByDesc('intentos')->limit(5)->toBase()->get(),
            'filtros' => $request->only(['buscar', 'evento', 'usuario', 'desde', 'hasta']),
        ]);
    }

    /** Quita un bloqueo vigente por intentos fallidos (usuario/correo + IP). */
    public function desbloquear(Request $request): RedirectResponse
    {
        $datos = $request->validate(['login' => ['required', 'string', 'max:60'], 'ip' => ['required', 'string', 'max:45']]);
        $bloqueo = Seguridad::bloqueos(fn ($q) => $this->soloMiEmpresa($q, $request))
            ->first(fn ($b) => $b->login === $datos['login'] && $b->ip === $datos['ip']);
        if (! $bloqueo) {
            return back()->with('aviso', 'Ese bloqueo ya no está vigente.');
        }

        Seguridad::desbloquear($bloqueo->login, $bloqueo->ip);
        Seguridad::registrar('DESBLOQUEO', $bloqueo->login, $bloqueo->id_usuario, "Por {$request->user()->username} (IP {$bloqueo->ip})");

        return back()->with('status', "Se quitó el bloqueo de {$bloqueo->login} desde {$bloqueo->ip}.");
    }

    public function exportar(Request $request): StreamedResponse
    {
        $accesos = $this->filtrados($request)->with('usuario:id_usuario,nombre_completo')->orderByDesc('id_auditoria')->lazyById(500, 'id_auditoria');

        return ExportarCsv::descargar('historial_accesos', ['Fecha', 'Evento', 'Usuario', 'Escribió', 'IP', 'Dispositivo', 'Detalle'],
            (function () use ($accesos) {
                foreach ($accesos as $a) {
                    yield [$a->created_at?->format('d/m/Y H:i:s'), AuditoriaAcceso::EVENTOS[$a->accion] ?? $a->accion,
                        $a->usuario?->nombre_completo ?? '(no existe)', $a->username_intento, $a->ip_address, Seguridad::dispositivo($a->user_agent), $a->detalle];
                }
            })());
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'max_intentos' => ['required', 'integer', 'min:1', 'max:20'],
            'bloqueo_minutos' => ['required', 'integer', 'min:1', 'max:1440'],
            'sesion_expira_min' => ['required', 'integer', 'min:5', 'max:1440'],
        ]);
        // Igual que la pantalla Configuración: se guarda en todas las filas de ConfiguracionSistema.
        $cambios = [
            'maxIntentosSesion' => $datos['max_intentos'],
            'bloqueoMinutos' => $datos['bloqueo_minutos'],
            'sesionExpiraMin' => $datos['sesion_expira_min'],
        ];
        Bitacora::configuracion($cambios);
        DB::table('ConfiguracionSistema')->update($cambios + [
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
        // Uno por uno para que cada cambio quede en la bitácora (solo se guardan los que cambian).
        foreach (Rol::query()->where('id_empresa', $idEmpresa)->get() as $rol) {
            $rol->update(['requiere_2fa' => in_array($rol->id_rol, $ids, true)]);
        }

        return back()->with('status', 'Verificación en dos pasos por rol guardada.');
    }

    private function filtrados(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));
        $evento = (string) $request->query('evento');
        $fecha = function (mixed $v): ?string {
            try {
                return is_string($v) && $v !== '' ? Carbon::parse($v)->toDateString() : null;
            } catch (\Throwable) {
                return null;
            }
        };

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w->where('username_intento', 'like', "%{$buscar}%")->orWhere('ip_address', 'like', "%{$buscar}%")))
            ->when(array_key_exists($evento, AuditoriaAcceso::EVENTOS), fn ($q) => $q->where('accion', $evento))
            ->when($request->filled('usuario'), fn ($q) => $q->where('id_usuario', $request->integer('usuario')))
            ->when($fecha($request->query('desde')), fn ($q, $d) => $q->where('created_at', '>=', $d.' 00:00:00'))
            ->when($fecha($request->query('hasta')), fn ($q, $d) => $q->where('created_at', '<=', $d.' 23:59:59'));
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return $this->soloMiEmpresa(AuditoriaAcceso::query(), $request);
    }

    /**
     * auditoria_acceso no guarda la empresa: accesos de usuarios de mi empresa y, además, los
     * intentos con un usuario o correo que no existe (sin id_usuario), que son la señal típica
     * de un ataque y no pertenecen a ninguna empresa.
     */
    private function soloMiEmpresa(Builder $consulta, Request $request): Builder
    {
        return $consulta->where(fn ($q) => $q->whereNull('id_usuario')
            ->orWhereIn('id_usuario', Usuario::withTrashed()->where('id_empresa', $request->user()->id_empresa)->select('id_usuario')));
    }
}
