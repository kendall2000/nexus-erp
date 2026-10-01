<?php

namespace App\Http\Controllers;

use App\Models\Core\AuditoriaCambio;
use App\Models\Core\Usuario;
use App\Support\Bitacora;
use App\Support\ExportarCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bitácora de cambios (auditoria_cambio). Permisos: bitacora.ver / exportar.
 * Solo consulta: los registros los escribe App\Support\Bitacora y no se editan ni se borran.
 */
class BitacoraController extends Controller
{
    public function index(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;

        return view('bitacora.index', [
            'cambios' => $this->filtrados($request)->with('usuario')->orderByDesc('id_cambio')->paginate(50)->withQueryString(),
            'usuarios' => Usuario::withTrashed()->where('id_empresa', $idEmpresa)->orderBy('nombre_completo')->get(['id_usuario', 'nombre_completo', 'username']),
            'tablas' => $this->deMiEmpresa($request)->distinct()->orderBy('tabla_afectada')->pluck('tabla_afectada')
                ->mapWithKeys(fn ($t) => [$t => Bitacora::tabla($t)])->sort(),
            'acciones' => Bitacora::ACCIONES,
            'filtros' => $request->only(['usuario', 'tabla', 'accion', 'registro', 'desde', 'hasta']),
        ]);
    }

    public function show(Request $request, int $cambio): View
    {
        $cambio = $this->deMiEmpresa($request)->with('usuario')->findOrFail($cambio);

        return view('bitacora.show', [
            'cambio' => $cambio,
            'campos' => Bitacora::campos($cambio),
            'historial' => $this->deMiEmpresa($request)->where('tabla_afectada', $cambio->tabla_afectada)
                ->where('id_registro', $cambio->id_registro)->count(),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $cambios = $this->filtrados($request)->with('usuario')->orderByDesc('id_cambio')->lazyById(500, 'id_cambio');
        $texto = fn (mixed $v) => is_array($v) ? implode(', ', $v) : (string) $v;

        // Una fila por campo, para poder filtrar en Excel.
        return ExportarCsv::descargar('bitacora_cambios', ['Fecha', 'Usuario', 'IP', 'Acción', 'Tabla', 'Registro', 'Campo', 'Antes', 'Después'],
            (function () use ($cambios, $texto) {
                foreach ($cambios as $c) {
                    $base = [$c->created_at?->format('d/m/Y H:i:s'), $c->usuario?->nombre_completo ?? 'Sistema', $c->ip_address,
                        Bitacora::ACCIONES[$c->accion][0] ?? $c->accion, Bitacora::tabla($c->tabla_afectada), $c->id_registro];
                    foreach (Bitacora::campos($c) ?: [['campo' => '', 'antes' => null, 'despues' => null]] as $f) {
                        yield [...$base, $f['campo'], $texto($f['antes']), $texto($f['despues'])];
                    }
                }
            })());
    }

    private function filtrados(Request $request): Builder
    {
        $fecha = function (mixed $v): ?string {
            try {
                return is_string($v) && $v !== '' ? Carbon::parse($v)->toDateString() : null;
            } catch (\Throwable) {
                return null;
            }
        };

        return $this->deMiEmpresa($request)
            ->when($request->filled('usuario'), fn ($q) => $q->where('id_usuario', $request->integer('usuario')))
            ->when($request->filled('tabla'), fn ($q) => $q->where('tabla_afectada', (string) $request->query('tabla')))
            ->when(isset(Bitacora::ACCIONES[(string) $request->query('accion')]), fn ($q) => $q->where('accion', $request->query('accion')))
            ->when($request->filled('registro'), fn ($q) => $q->where('id_registro', (string) $request->query('registro')))
            ->when($fecha($request->query('desde')), fn ($q, $d) => $q->where('created_at', '>=', $d.' 00:00:00'))
            ->when($fecha($request->query('hasta')), fn ($q, $d) => $q->where('created_at', '<=', $d.' 23:59:59'));
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return AuditoriaCambio::query()->where('id_empresa', $request->user()->id_empresa);
    }
}
