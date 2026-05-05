<?php
// app/Http/Controllers/Api/V1/Finanzas/PresupuestoController.php

namespace App\Http\Controllers\Api\V1\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\Finanzas\PresupuestoAnual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresupuestoController extends Controller
{
    /**
     * Listado con filtros.
     * GET /api/v1/finanzas/presupuestos
     */
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $anio      = $request->input('anio', now()->year);

        $presupuestos = PresupuestoAnual::with(['centroCosto:id_centro,codigo,nombre',
                                                'cuentaContable:id_cuenta,codigo,nombre,tipo'])
            ->porEmpresa($idEmpresa)
            ->porAnio($anio)
            ->when($request->id_centro, fn($q,$v) => $q->porCentro($v))
            ->when($request->estado,    fn($q,$v) => $q->porEstado($v))
            ->orderBy('id_centro')
            ->orderBy('id_cuenta')
            ->get()
            ->map(fn($p) => [
                'id_presupuesto'       => $p->id_presupuesto,
                'centro'               => $p->centroCosto?->codigo . ' — ' . $p->centroCosto?->nombre,
                'cuenta'               => $p->cuentaContable?->codigo . ' — ' . $p->cuentaContable?->nombre,
                'tipo_cuenta'          => $p->cuentaContable?->tipo,
                'anio'                 => $p->anio,
                'moneda'               => $p->moneda,
                'total_presupuestado'  => number_format($p->total_presupuestado, 2),
                'total_ejecutado'      => number_format($p->total_ejecutado, 2),
                'saldo_disponible'     => number_format($p->saldo_disponible, 2),
                'porcentaje_ejecucion' => $p->porcentaje_ejecucion,
                'estado_ejecucion'     => $p->estado_ejecucion,
                'estado'               => $p->estado,
            ]);

        return response()->json(['success' => true, 'data' => $presupuestos]);
    }

    /**
     * Catálogos para formulario y filtros.
     * GET /api/v1/finanzas/presupuestos/catalogos
     */
    public function catalogos(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $centros = \App\Models\Core\CentroCosto::porEmpresa($idEmpresa)
            ->activos()
            ->orderBy('codigo')
            ->get()
            ->map(fn($c) => ['id' => $c->id_centro, 'name' => "{$c->codigo} — {$c->nombre}"]);

        // Solo cuentas que permiten movimiento (de movimiento, no resumen)
        $cuentas = DB::table('cuenta_contable')
            ->where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->where('permite_movimiento', true)
            ->whereIn('tipo', ['INGRESO', 'GASTO', 'COSTO']) // típicas de presupuesto
            ->orderBy('codigo')
            ->get([
                'id_cuenta as id',
                DB::raw("CONCAT(codigo, ' — ', nombre) as name"),
                'tipo',
            ]);

        $aniosDisponibles = range(now()->year - 2, now()->year + 1);

        return response()->json([
            'success' => true,
            'data'    => [
                'centros'  => $centros,
                'cuentas'  => $cuentas,
                'monedas'  => ['GTQ', 'USD', 'EUR'],
                'estados'  => ['BORRADOR', 'APROBADO', 'CERRADO'],
                'anios'    => $aniosDisponibles,
            ],
        ]);
    }

    /**
     * Detalle (incluye los 12 meses).
     * GET /api/v1/finanzas/presupuestos/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $p = PresupuestoAnual::with(['centroCosto', 'cuentaContable',
                                     'aprobadoPor:id_usuario,nombre',
                                     'cerradoPor:id_usuario,nombre'])
            ->porEmpresa($request->user()->id_empresa)
            ->findOrFail($id);

        $meses = [];
        foreach (PresupuestoAnual::MESES as $num => $nombre) {
            $meses[] = [
                'mes'           => $num,
                'nombre'        => ucfirst($nombre),
                'presupuestado' => (float) $p->{"pre_{$nombre}"},
                'ejecutado'     => (float) $p->{"eje_{$nombre}"},
                'disponible'    => (float) $p->{"pre_{$nombre}"} - (float) $p->{"eje_{$nombre}"},
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id_presupuesto'       => $p->id_presupuesto,
                'id_centro'            => $p->id_centro,
                'id_cuenta'            => $p->id_cuenta,
                'anio'                 => $p->anio,
                'moneda'               => $p->moneda,
                'estado'                => $p->estado,
                'centro'               => $p->centroCosto?->nombre,
                'cuenta'               => $p->cuentaContable?->nombre,
                'meses'                => $meses,
                'total_presupuestado'  => $p->total_presupuestado,
                'total_ejecutado'      => $p->total_ejecutado,
                'saldo_disponible'     => $p->saldo_disponible,
                'porcentaje_ejecucion' => $p->porcentaje_ejecucion,
                'estado_ejecucion'     => $p->estado_ejecucion,
                'aprobado_por'         => $p->aprobadoPor?->nombre,
                'fecha_aprobacion'     => $p->fecha_aprobacion?->format('d/m/Y H:i'),
                'puede_editarse'       => $p->puedeEditarse(),
            ],
        ]);
    }

    /**
     * Crear presupuesto.
     * POST /api/v1/finanzas/presupuestos
     */
    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $datos     = $this->validarFormulario($request);

        // Verificar duplicado (UNIQUE: empresa+centro+cuenta+año)
        $existe = PresupuestoAnual::porEmpresa($idEmpresa)
            ->where('id_centro', $datos['id_centro'])
            ->where('id_cuenta', $datos['id_cuenta'])
            ->where('anio', $datos['anio'])
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un presupuesto para esta cuenta, centro y año.',
            ], 422);
        }

        $p = PresupuestoAnual::create(array_merge($datos, [
            'id_empresa' => $idEmpresa,
            'estado'     => PresupuestoAnual::ESTADO_BORRADOR,
            'created_by' => $request->user()->id_usuario,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Presupuesto creado correctamente.',
            'data'    => ['id_presupuesto' => $p->id_presupuesto],
        ], 201);
    }

    /**
     * Actualizar presupuesto (solo en BORRADOR).
     * PUT /api/v1/finanzas/presupuestos/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $p = PresupuestoAnual::porEmpresa($request->user()->id_empresa)->findOrFail($id);

        if (!$p->puedeEditarse()) {
            return response()->json([
                'success' => false,
                'message' => "No se puede editar un presupuesto en estado {$p->estado}.",
            ], 422);
        }

        $datos = $this->validarFormulario($request, $id);
        $p->update(array_merge($datos, ['updated_by' => $request->user()->id_usuario]));

        return response()->json([
            'success' => true,
            'message' => 'Presupuesto actualizado correctamente.',
        ]);
    }

    /**
     * Aprobar presupuesto.
     * PATCH /api/v1/finanzas/presupuestos/{id}/aprobar
     */
    public function aprobar(Request $request, int $id): JsonResponse
    {
        $p = PresupuestoAnual::porEmpresa($request->user()->id_empresa)->findOrFail($id);

        try {
            $p->aprobar($request->user()->id_usuario);
            return response()->json(['success' => true, 'message' => 'Presupuesto aprobado correctamente.']);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cerrar presupuesto.
     * PATCH /api/v1/finanzas/presupuestos/{id}/cerrar
     */
    public function cerrar(Request $request, int $id): JsonResponse
    {
        $p = PresupuestoAnual::porEmpresa($request->user()->id_empresa)->findOrFail($id);

        try {
            $p->cerrar($request->user()->id_usuario);
            return response()->json(['success' => true, 'message' => 'Presupuesto cerrado correctamente.']);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Eliminar (solo BORRADOR).
     * DELETE /api/v1/finanzas/presupuestos/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $p = PresupuestoAnual::porEmpresa($request->user()->id_empresa)->findOrFail($id);

        if (!$p->puedeEditarse()) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar un presupuesto en estado {$p->estado}.",
            ], 422);
        }

        $p->delete();
        return response()->json(['success' => true, 'message' => 'Presupuesto eliminado correctamente.']);
    }

    /**
     * Clonar presupuesto del año anterior como base del nuevo.
     * POST /api/v1/finanzas/presupuestos/clonar
     */
    public function clonar(Request $request): JsonResponse
    {
        $request->validate([
            'anio_origen'  => 'required|integer',
            'anio_destino' => 'required|integer|different:anio_origen',
            'incremento_pct' => 'nullable|numeric|min:-100|max:1000',
        ]);

        $idEmpresa     = $request->user()->id_empresa;
        $factor        = 1 + (($request->incremento_pct ?? 0) / 100);

        return DB::transaction(function () use ($request, $idEmpresa, $factor) {
            $origen = PresupuestoAnual::porEmpresa($idEmpresa)
                ->porAnio($request->anio_origen)
                ->get();

            if ($origen->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No hay presupuestos del año {$request->anio_origen} para clonar.",
                ], 422);
            }

            $clonados = 0;
            foreach ($origen as $o) {
                // Saltar si ya existe en el año destino
                $existe = PresupuestoAnual::porEmpresa($idEmpresa)
                    ->where('id_centro', $o->id_centro)
                    ->where('id_cuenta', $o->id_cuenta)
                    ->where('anio', $request->anio_destino)
                    ->exists();
                if ($existe) continue;

                $datos = ['id_empresa' => $idEmpresa,
                          'id_centro'  => $o->id_centro,
                          'id_cuenta'  => $o->id_cuenta,
                          'anio'       => $request->anio_destino,
                          'moneda'     => $o->moneda,
                          'estado'     => PresupuestoAnual::ESTADO_BORRADOR,
                          'created_by' => $request->user()->id_usuario];

                foreach (PresupuestoAnual::MESES as $m) {
                    $datos["pre_{$m}"] = round($o->{"pre_{$m}"} * $factor, 4);
                    $datos["eje_{$m}"] = 0;
                }

                PresupuestoAnual::create($datos);
                $clonados++;
            }

            return response()->json([
                'success' => true,
                'message' => "{$clonados} presupuestos clonados correctamente"
                           . ($request->incremento_pct ? " con incremento del {$request->incremento_pct}%" : '') . ".",
            ]);
        });
    }

    /**
     * Dashboard: KPIs + serie mensual + comparativo año anterior.
     * GET /api/v1/finanzas/presupuestos/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $idEmpresa  = $request->user()->id_empresa;
        $anio       = (int) $request->input('anio', now()->year);
        $anioAnt    = $anio - 1;

        // KPIs del año actual
        $actuales = PresupuestoAnual::porEmpresa($idEmpresa)->porAnio($anio)->get();
        $totalPre = $actuales->sum('total_presupuestado');
        $totalEje = $actuales->sum('total_ejecutado');

        // Serie mensual: presupuestado vs ejecutado
        $serieMensual = [];
        foreach (PresupuestoAnual::MESES as $num => $nombre) {
            $serieMensual[] = [
                'mes'           => ucfirst($nombre),
                'presupuestado' => (float) $actuales->sum("pre_{$nombre}"),
                'ejecutado'     => (float) $actuales->sum("eje_{$nombre}"),
            ];
        }

        // Comparativo año anterior (totales mensuales)
        $anteriores = PresupuestoAnual::porEmpresa($idEmpresa)->porAnio($anioAnt)->get();
        $comparativo = [];
        foreach (PresupuestoAnual::MESES as $num => $nombre) {
            $comparativo[] = [
                'mes'         => ucfirst($nombre),
                'anio_actual' => (float) $actuales->sum("eje_{$nombre}"),
                'anio_anterior' => (float) $anteriores->sum("eje_{$nombre}"),
            ];
        }

        // Top 5 cuentas más ejecutadas
        $topCuentas = PresupuestoAnual::with('cuentaContable:id_cuenta,codigo,nombre')
            ->porEmpresa($idEmpresa)->porAnio($anio)
            ->orderByDesc('total_ejecutado')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'cuenta'               => $p->cuentaContable?->codigo . ' — ' . $p->cuentaContable?->nombre,
                'presupuestado'        => (float) $p->total_presupuestado,
                'ejecutado'            => (float) $p->total_ejecutado,
                'porcentaje_ejecucion' => $p->porcentaje_ejecucion,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'anio'              => $anio,
                'kpi' => [
                    'total_presupuestado'  => round($totalPre, 2),
                    'total_ejecutado'      => round($totalEje, 2),
                    'saldo_disponible'     => round($totalPre - $totalEje, 2),
                    'porcentaje_ejecucion' => $totalPre > 0 ? round(($totalEje / $totalPre) * 100, 2) : 0,
                ],
                'serie_mensual' => $serieMensual,
                'comparativo'   => $comparativo,
                'top_cuentas'   => $topCuentas,
            ],
        ]);
    }

    // ── Helper de validación ───────────────────────────────────
    private function validarFormulario(Request $request, ?int $idIgnorar = null): array
    {
        return $request->validate([
            'id_centro'      => 'required|exists:centro_costo,id_centro',
            'id_cuenta'      => 'required|exists:cuenta_contable,id_cuenta',
            'anio'           => 'required|integer|min:2020|max:2100',
            'moneda'         => 'required|string|size:3',
            'pre_enero'      => 'nullable|numeric|min:0',
            'pre_febrero'    => 'nullable|numeric|min:0',
            'pre_marzo'      => 'nullable|numeric|min:0',
            'pre_abril'      => 'nullable|numeric|min:0',
            'pre_mayo'       => 'nullable|numeric|min:0',
            'pre_junio'      => 'nullable|numeric|min:0',
            'pre_julio'      => 'nullable|numeric|min:0',
            'pre_agosto'     => 'nullable|numeric|min:0',
            'pre_septiembre' => 'nullable|numeric|min:0',
            'pre_octubre'    => 'nullable|numeric|min:0',
            'pre_noviembre'  => 'nullable|numeric|min:0',
            'pre_diciembre'  => 'nullable|numeric|min:0',
        ]);
    }
}