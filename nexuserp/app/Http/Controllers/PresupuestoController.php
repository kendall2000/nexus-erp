<?php

namespace App\Http\Controllers;

use App\Models\Core\CentroCosto;
use App\Models\Core\CuentaContable;
use App\Models\Core\Empresa;
use App\Models\Finanzas\PresupuestoAnual;
use App\Models\Inventario\DetalleOrdenCompra;
use App\Support\EjecucionPresupuesto;
use App\Support\ExportarCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Presupuesto anual por centro de costo y cuenta. Permisos: presupuesto.ver / crear /
 * editar / aprobar / cerrar / reabrir / exportar.
 *
 * Flujo: BORRADOR (se edita o elimina) → APROBADO (recibe la ejecución de las órdenes
 * de compra, App\Support\EjecucionPresupuesto) → CERRADO (ya no recibe ejecución) y,
 * con permiso, se reabre a APROBADO.
 */
class PresupuestoController extends Controller
{
    public const ESTADOS = [
        'BORRADOR' => ['Borrador', 'secondary'],
        'APROBADO' => ['Aprobado', 'success'],
        'CERRADO' => ['Cerrado', 'dark'],
    ];

    /** Semáforo de ejecución: [nombre, color]. */
    public const EJECUCION = [
        'NORMAL' => ['Normal', 'success'],
        'ALERTA' => ['Alerta (≥ 75 %)', 'warning'],
        'CRITICO' => ['Crítico (≥ 90 %)', 'danger'],
        'SOBRE_EJECUTADO' => ['Sobreejecutado', 'danger'],
    ];

    /** Tipos de cuenta que se presupuestan. */
    public const TIPOS_CUENTA = ['INGRESO', 'GASTO', 'COSTO'];

    public function index(Request $request): View
    {
        $anio = $this->anio($request);
        $partidas = $this->filtrados($request, $anio)->with(['centroCosto', 'cuentaContable'])->orderBy('id_centro')->orderBy('id_cuenta')->get();
        // El resumen y el gráfico cuentan lo que ya compromete: aprobados y cerrados.
        $vigentes = $partidas->where('estado', '!=', PresupuestoAnual::ESTADO_BORRADOR);

        $meses = [];
        foreach (PresupuestoAnual::MESES as $num => $mes) {
            $meses[] = ['mes' => ucfirst($mes), 'presupuestado' => round((float) $vigentes->sum("pre_{$mes}"), 2), 'ejecutado' => round((float) $vigentes->sum("eje_{$mes}"), 2)];
        }
        $totalPre = (float) $vigentes->sum('total_presupuestado');
        $totalEje = (float) $vigentes->sum('total_ejecutado');

        return view('presupuesto.index', [
            'anio' => $anio,
            'anios' => $this->aniosDisponibles($request, $anio),
            'partidas' => $partidas,
            'resumen' => ['presupuestado' => $totalPre, 'ejecutado' => $totalEje, 'saldo' => $totalPre - $totalEje,
                'porcentaje' => $totalPre > 0 ? round($totalEje / $totalPre * 100, 1) : 0, 'borradores' => $partidas->count() - $vigentes->count()],
            'meses' => $meses,
            'monedas' => $vigentes->pluck('moneda')->unique()->values(),
            'centros' => CentroCosto::query()->where('id_empresa', $request->user()->id_empresa)->orderBy('codigo')->get(),
            'estados' => self::ESTADOS,
            'ejecucion' => self::EJECUCION,
            'filtros' => $request->only(['centro', 'estado']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $anio = $this->anio($request);
        $partidas = $this->filtrados($request, $anio)->with(['centroCosto', 'cuentaContable'])->orderBy('id_centro')->orderBy('id_cuenta')->get();
        $meses = array_map('ucfirst', PresupuestoAnual::MESES);

        return ExportarCsv::descargar("presupuesto_{$anio}", ['Año', 'Centro de costo', 'Cuenta', 'Moneda', 'Estado',
            ...array_map(fn ($m) => "Presupuestado {$m}", $meses), ...array_map(fn ($m) => "Ejecutado {$m}", $meses), 'Total presupuestado', 'Total ejecutado', 'Saldo', '% ejecución'],
            $partidas->map(fn ($p) => [$p->anio, $p->centroCosto?->etiqueta, $p->cuentaContable?->etiqueta, $p->moneda, self::ESTADOS[$p->estado][0] ?? $p->estado,
                ...array_map(fn ($m) => round((float) $p->{"pre_{$m}"}, 2), PresupuestoAnual::MESES),
                ...array_map(fn ($m) => round((float) $p->{"eje_{$m}"}, 2), PresupuestoAnual::MESES),
                round((float) $p->total_presupuestado, 2), round((float) $p->total_ejecutado, 2), round($p->saldo_disponible, 2), $p->porcentaje_ejecucion]));
    }

    public function show(Request $request, int $presupuesto): View
    {
        $p = $this->deMiEmpresa($request)->with(['centroCosto', 'cuentaContable', 'aprobadoPor', 'cerradoPor'])->findOrFail($presupuesto);

        return view('presupuesto.show', [
            'p' => $p,
            'estados' => self::ESTADOS,
            'ejecucion' => self::EJECUCION,
            'ordenes' => $this->ordenesQueEjecutan($p),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new PresupuestoAnual(['anio' => $this->anio($request), 'moneda' => 'GTQ', 'estado' => PresupuestoAnual::ESTADO_BORRADOR]));
    }

    public function store(Request $request): RedirectResponse
    {
        $p = PresupuestoAnual::create($this->validar($request) + [
            'id_empresa' => $request->user()->id_empresa,
            'estado' => PresupuestoAnual::ESTADO_BORRADOR,
            'created_by' => $request->user()->id_usuario,
            'updated_by' => $request->user()->id_usuario,
        ]);

        return redirect()->route('presupuesto.show', $p->id_presupuesto)->with('status', 'Presupuesto creado en borrador.');
    }

    public function edit(Request $request, int $presupuesto): View|RedirectResponse
    {
        $p = $this->deMiEmpresa($request)->findOrFail($presupuesto);
        if (! $p->puedeEditarse()) {
            return redirect()->route('presupuesto.show', $p->id_presupuesto)->withErrors(['presupuesto' => 'Solo se editan presupuestos en borrador.']);
        }

        return $this->formulario($request, $p);
    }

    public function update(Request $request, int $presupuesto): RedirectResponse
    {
        $p = $this->deMiEmpresa($request)->findOrFail($presupuesto);
        if (! $p->puedeEditarse()) {
            return redirect()->route('presupuesto.show', $p->id_presupuesto)->withErrors(['presupuesto' => 'Solo se editan presupuestos en borrador.']);
        }
        $p->update($this->validar($request, $p) + ['updated_by' => $request->user()->id_usuario]);

        return redirect()->route('presupuesto.show', $p->id_presupuesto)->with('status', 'Presupuesto actualizado.');
    }

    public function destroy(Request $request, int $presupuesto): RedirectResponse
    {
        $p = $this->deMiEmpresa($request)->findOrFail($presupuesto);
        if (! $p->puedeEditarse()) {
            return back()->withErrors(['presupuesto' => 'Solo se eliminan presupuestos en borrador.']);
        }
        $p->delete();

        return redirect()->route('presupuesto.index', ['anio' => $p->anio])->with('status', 'Presupuesto eliminado.');
    }

    public function aprobar(Request $request, int $presupuesto): RedirectResponse
    {
        return $this->transicion($request, $presupuesto, PresupuestoAnual::ESTADO_BORRADOR, 'aprobar', function (PresupuestoAnual $p) use ($request) {
            if ((float) $p->total_presupuestado <= 0) {
                return 'No se puede aprobar un presupuesto en cero.';
            }
            $p->update(['estado' => PresupuestoAnual::ESTADO_APROBADO, 'aprobado_por' => $request->user()->id_usuario, 'fecha_aprobacion' => now()]);

            return null;
        }, 'aprobado. Desde ahora las órdenes de compra aprobadas lo ejecutan.');
    }

    public function cerrar(Request $request, int $presupuesto): RedirectResponse
    {
        return $this->transicion($request, $presupuesto, PresupuestoAnual::ESTADO_APROBADO, 'cerrar', function (PresupuestoAnual $p) use ($request) {
            $p->update(['estado' => PresupuestoAnual::ESTADO_CERRADO, 'cerrado_por' => $request->user()->id_usuario, 'fecha_cierre' => now()]);

            return null;
        }, 'cerrado. Ya no recibe ejecución.');
    }

    public function reabrir(Request $request, int $presupuesto): RedirectResponse
    {
        return $this->transicion($request, $presupuesto, PresupuestoAnual::ESTADO_CERRADO, 'reabrir', function (PresupuestoAnual $p) {
            $p->update(['estado' => PresupuestoAnual::ESTADO_APROBADO, 'cerrado_por' => null, 'fecha_cierre' => null]);

            return null;
        }, 'reabierto: vuelve a estar aprobado.');
    }

    /** Copia las partidas de un año a otro (en borrador y sin ejecución), con un ajuste en %. */
    public function clonar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'anio_origen' => ['required', 'integer', 'between:2000,2100'],
            'anio_destino' => ['required', 'integer', 'between:2000,2100', 'different:anio_origen'],
            'incremento' => ['nullable', 'numeric', 'min:-100', 'max:1000'],
        ], ['anio_destino.different' => 'El año destino debe ser distinto del de origen.']);
        $idEmpresa = $request->user()->id_empresa;
        $factor = 1 + ((float) ($datos['incremento'] ?? 0)) / 100;

        [$copiados, $omitidos] = DB::transaction(function () use ($request, $datos, $idEmpresa, $factor) {
            $origen = PresupuestoAnual::query()->where('id_empresa', $idEmpresa)->where('anio', $datos['anio_origen'])->get();
            if ($origen->isEmpty()) {
                throw ValidationException::withMessages(['anio_origen' => "No hay presupuestos de {$datos['anio_origen']} para copiar."]);
            }
            $existentes = PresupuestoAnual::query()->where('id_empresa', $idEmpresa)->where('anio', $datos['anio_destino'])
                ->get(['id_centro', 'id_cuenta'])->map(fn ($p) => "{$p->id_centro}-{$p->id_cuenta}")->flip();
            $copiados = 0;
            foreach ($origen as $o) {
                if (isset($existentes["{$o->id_centro}-{$o->id_cuenta}"])) {
                    continue;
                }
                $nuevo = ['id_empresa' => $idEmpresa, 'id_centro' => $o->id_centro, 'id_cuenta' => $o->id_cuenta, 'anio' => $datos['anio_destino'],
                    'moneda' => $o->moneda, 'estado' => PresupuestoAnual::ESTADO_BORRADOR, 'created_by' => $request->user()->id_usuario, 'updated_by' => $request->user()->id_usuario];
                foreach (PresupuestoAnual::MESES as $m) {
                    $nuevo["pre_{$m}"] = round((float) $o->{"pre_{$m}"} * $factor, 2);
                    $nuevo["eje_{$m}"] = 0;
                }
                PresupuestoAnual::create($nuevo);
                $copiados++;
            }

            return [$copiados, $origen->count() - $copiados];
        });

        return redirect()->route('presupuesto.index', ['anio' => $datos['anio_destino']])
            ->with('status', "{$copiados} partidas copiadas de {$datos['anio_origen']} a {$datos['anio_destino']} en borrador".($omitidos ? " ({$omitidos} ya existían y se dejaron igual)." : '.'));
    }

    // ── Privados ─────────────────────────────────────────────────────────

    /** @param  callable(PresupuestoAnual): ?string  $cambio  devuelve un error o null */
    private function transicion(Request $request, int $id, string $desde, string $verbo, callable $cambio, string $mensaje): RedirectResponse
    {
        return DB::transaction(function () use ($request, $id, $desde, $verbo, $cambio, $mensaje) {
            $p = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($id);
            if ($p->estado !== $desde) {
                return back()->withErrors(['presupuesto' => "Solo se puede {$verbo} un presupuesto ".strtolower(self::ESTADOS[$desde][0]).'.']);
            }
            if ($error = $cambio($p)) {
                return back()->withErrors(['presupuesto' => $error]);
            }

            return back()->with('status', "Presupuesto {$mensaje}");
        });
    }

    /** Líneas de órdenes aprobadas del mismo año cuyo centro y cuenta (propios o heredados del producto) son los del presupuesto. */
    private function ordenesQueEjecutan(PresupuestoAnual $p)
    {
        $empresa = Empresa::find($p->id_empresa);
        [$tasaIva, $ivaIncluido] = [$empresa ? (float) $empresa->tasa_iva_decimal : 0.12, (bool) $empresa?->iva_incluido_en_precio];

        return DetalleOrdenCompra::query()
            ->with(['ordenCompra', 'producto'])
            ->whereHas('ordenCompra', fn ($q) => $q->where('id_empresa', $p->id_empresa)->whereIn('estado', ['ENVIADA', 'PARCIAL', 'RECIBIDA'])->whereYear('fecha_emision', $p->anio))
            ->leftJoin('producto', 'producto.id_producto', '=', 'detalle_orden_compra.id_producto')
            ->whereRaw('COALESCE(detalle_orden_compra.id_centro, producto.id_centro_default) = ?', [$p->id_centro])
            ->whereRaw('COALESCE(detalle_orden_compra.id_cuenta, producto.id_cuenta_gasto) = ?', [$p->id_cuenta])
            ->select('detalle_orden_compra.*')
            ->get()
            // Mismo monto que suma EjecucionPresupuesto: la base sin IVA.
            ->each(fn ($d) => $d->setAttribute('monto_neto', EjecucionPresupuesto::montoNeto((float) $d->subtotal, true, $tasaIva, $ivaIncluido)))
            ->sortByDesc(fn ($d) => $d->ordenCompra->fecha_emision)
            ->values();
    }

    private function formulario(Request $request, PresupuestoAnual $p): View
    {
        $idEmpresa = $request->user()->id_empresa;

        return view('presupuesto.form', [
            'p' => $p,
            'centros' => CentroCosto::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('codigo')->get(),
            'cuentas' => CuentaContable::query()->where('id_empresa', $idEmpresa)->where('activo', true)->where('permite_movimiento', true)
                ->whereIn('tipo', self::TIPOS_CUENTA)->orderBy('codigo')->get(),
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
        ]);
    }

    private function filtrados(Request $request, int $anio): Builder
    {
        return $this->deMiEmpresa($request)->where('anio', $anio)
            ->when($request->filled('centro'), fn ($q) => $q->where('id_centro', $request->integer('centro')))
            ->when(array_key_exists((string) $request->query('estado'), self::ESTADOS), fn ($q) => $q->where('estado', $request->query('estado')));
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return PresupuestoAnual::query()->where('id_empresa', $request->user()->id_empresa);
    }

    private function anio(Request $request): int
    {
        $anio = $request->integer('anio', (int) now()->year);

        return $anio >= 2000 && $anio <= 2100 ? $anio : (int) now()->year;
    }

    /** Años con presupuestos más el actual, el siguiente y el consultado. */
    private function aniosDisponibles(Request $request, int $anio): array
    {
        $anios = $this->deMiEmpresa($request)->distinct()->pluck('anio')->map(fn ($a) => (int) $a)
            ->merge([(int) now()->year, (int) now()->year + 1, $anio])->unique()->sortDesc()->values()->all();

        return $anios;
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?PresupuestoAnual $p = null): array
    {
        $idEmpresa = $request->user()->id_empresa;
        $reglasMeses = [];
        foreach (PresupuestoAnual::MESES as $m) {
            $reglasMeses["pre_{$m}"] = ['nullable', 'numeric', 'min:0', 'max:9999999999'];
        }

        $datos = $request->validate([
            'id_centro' => ['required', 'integer', Rule::exists('centro_costo', 'id_centro')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'id_cuenta' => ['required', 'integer', Rule::exists('cuenta_contable', 'id_cuenta')->where('id_empresa', $idEmpresa)->where('activo', true)
                ->where('permite_movimiento', true)->whereIn('tipo', self::TIPOS_CUENTA)],
            'anio' => ['required', 'integer', 'between:2000,2100'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
        ] + $reglasMeses, [
            'id_centro.exists' => 'El centro de costo no es válido o está inactivo.',
            'id_cuenta.exists' => 'La cuenta debe ser de movimiento, activa y de ingreso, gasto o costo.',
        ]);

        $duplicado = $this->deMiEmpresa($request)->where('id_centro', $datos['id_centro'])->where('id_cuenta', $datos['id_cuenta'])
            ->where('anio', $datos['anio'])->when($p, fn ($q) => $q->whereKeyNot($p->id_presupuesto))->exists();
        if ($duplicado) {
            throw ValidationException::withMessages(['id_cuenta' => 'Ya hay un presupuesto de ese centro y cuenta para '.$datos['anio'].'.']);
        }
        foreach (PresupuestoAnual::MESES as $m) {
            $datos["pre_{$m}"] = round((float) ($datos["pre_{$m}"] ?? 0), 2);
        }

        return $datos;
    }
}
