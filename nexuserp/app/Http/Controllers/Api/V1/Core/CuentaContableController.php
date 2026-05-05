<?php
// app/Http/Controllers/Api/V1/Core/CuentaContableController.php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\CuentaContable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CuentaContableController extends Controller
{
    /**
     * Listado plano (para tabla y filtros).
     * GET /api/v1/core/cuentas-contables
     */
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $cuentas = CuentaContable::porEmpresa($idEmpresa)
            ->when($request->tipo,       fn($q,$v) => $q->where('tipo', $v))
            ->when($request->naturaleza, fn($q,$v) => $q->where('naturaleza', $v))
            ->when($request->activo !== null,
                   fn($q) => $q->where('activo', $request->boolean('activo')))
            ->orderBy('codigo')
            ->get()
            ->map(fn($c) => [
                'id_cuenta'          => $c->id_cuenta,
                'id_padre'           => $c->id_padre,
                'codigo'             => $c->codigo,
                'nombre'             => $c->nombre,
                'tipo'               => $c->tipo,
                'naturaleza'         => $c->naturaleza,
                'nivel'              => $c->nivel,
                'permite_movimiento' => $c->permite_movimiento,
                'activo'             => $c->activo,
            ]);

        return response()->json(['success' => true, 'data' => $cuentas]);
    }

    /**
     * Árbol jerárquico recursivo.
     * GET /api/v1/core/cuentas-contables/arbol
     */
    public function arbol(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        // Cargar todas en una sola query y armar árbol en memoria (más eficiente)
        $todas = CuentaContable::porEmpresa($idEmpresa)
            ->orderBy('codigo')
            ->get();

        $arbol = $this->construirArbol($todas, null);

        return response()->json(['success' => true, 'data' => $arbol]);
    }

    /**
     * Catálogo simple para selects.
     * GET /api/v1/core/cuentas-contables/catalogo
     */
    public function catalogo(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $cuentas = CuentaContable::porEmpresa($idEmpresa)
            ->activos()
            ->orderBy('codigo')
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id_cuenta,
                'name'       => "{$c->codigo} — {$c->nombre}",
                'tipo'       => $c->tipo,
                'nivel'      => $c->nivel,
                'permite_movimiento' => $c->permite_movimiento,
            ]);

        $tipos       = ['ACTIVO', 'PASIVO', 'PATRIMONIO', 'INGRESO', 'GASTO', 'COSTO'];
        $naturalezas = ['DEUDORA', 'ACREEDORA'];

        return response()->json([
            'success' => true,
            'data' => [
                'cuentas'     => $cuentas,
                'tipos'       => $tipos,
                'naturalezas' => $naturalezas,
            ],
        ]);
    }

    /**
     * Detalle de una cuenta.
     * GET /api/v1/core/cuentas-contables/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $cuenta = CuentaContable::with('padre:id_cuenta,codigo,nombre')
            ->porEmpresa($request->user()->id_empresa)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $cuenta,
        ]);
    }

    /**
     * Crear nueva cuenta.
     * POST /api/v1/core/cuentas-contables
     */
    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $datos     = $this->validar($request, $idEmpresa);

        // Calcular nivel automáticamente según el padre
        $datos['nivel'] = $this->calcularNivel($datos['id_padre'] ?? null);

        $cuenta = CuentaContable::create(array_merge($datos, [
            'id_empresa' => $idEmpresa,
            'activo'     => $datos['activo'] ?? true,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Cuenta contable creada correctamente.',
            'data'    => ['id_cuenta' => $cuenta->id_cuenta],
        ], 201);
    }

    /**
     * Actualizar cuenta.
     * PUT /api/v1/core/cuentas-contables/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $cuenta    = CuentaContable::porEmpresa($idEmpresa)->findOrFail($id);
        $datos     = $this->validar($request, $idEmpresa, $id);

        // Validar que no sea su propio padre ni descendiente
        if (!empty($datos['id_padre']) && $this->esDescendiente($id, $datos['id_padre'])) {
            return response()->json([
                'success' => false,
                'message' => 'Una cuenta no puede ser hija de uno de sus descendientes (ciclo).',
            ], 422);
        }

        // Si tiene hijas, no puede ser cuenta de movimiento
        $tieneHijas = $cuenta->hijas()->exists();
        if ($tieneHijas && ($datos['permite_movimiento'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Una cuenta con subcuentas no puede permitir movimientos directos.',
            ], 422);
        }

        $datos['nivel'] = $this->calcularNivel($datos['id_padre'] ?? null);
        $cuenta->update($datos);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta contable actualizada correctamente.',
        ]);
    }

    /**
     * Toggle activo/inactivo.
     * PATCH /api/v1/core/cuentas-contables/{id}/toggle
     */
    public function toggle(Request $request, int $id): JsonResponse
    {
        $cuenta = CuentaContable::porEmpresa($request->user()->id_empresa)->findOrFail($id);
        $cuenta->update(['activo' => !$cuenta->activo]);

        return response()->json([
            'success' => true,
            'message' => $cuenta->activo ? 'Cuenta activada.' : 'Cuenta desactivada.',
        ]);
    }

    /**
     * Eliminar cuenta (solo si no tiene hijas ni presupuestos).
     * DELETE /api/v1/core/cuentas-contables/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $cuenta = CuentaContable::porEmpresa($request->user()->id_empresa)->findOrFail($id);

        if ($cuenta->hijas()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: la cuenta tiene subcuentas asociadas.',
            ], 422);
        }

        if ($cuenta->presupuestos()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: la cuenta tiene presupuestos asociados.',
            ], 422);
        }

        $cuenta->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta eliminada correctamente.',
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // IMPORTADOR EXCEL
    // ════════════════════════════════════════════════════════════

    /**
     * Validar archivo importado SIN guardar (preview).
     * POST /api/v1/core/cuentas-contables/import/preview
     */
    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'cuentas'                       => 'required|array|min:1',
            'cuentas.*.codigo'              => 'required|string|max:20',
            'cuentas.*.nombre'              => 'required|string|max:200',
            'cuentas.*.tipo'                => 'required|in:ACTIVO,PASIVO,PATRIMONIO,INGRESO,GASTO,COSTO',
            'cuentas.*.naturaleza'          => 'required|in:DEUDORA,ACREEDORA',
            'cuentas.*.codigo_padre'        => 'nullable|string|max:20',
            'cuentas.*.permite_movimiento'  => 'nullable|boolean',
        ]);

        $idEmpresa = $request->user()->id_empresa;
        $resultado = $this->analizarImportacion($request->cuentas, $idEmpresa);

        return response()->json(['success' => true, 'data' => $resultado]);
    }

    /**
     * Confirmar importación: guarda en BD dentro de transacción.
     * POST /api/v1/core/cuentas-contables/import/commit
     */
    public function importCommit(Request $request): JsonResponse
    {
        $request->validate([
            'cuentas' => 'required|array|min:1',
        ]);

        $idEmpresa = $request->user()->id_empresa;
        $analisis  = $this->analizarImportacion($request->cuentas, $idEmpresa);

        if (count($analisis['errores']) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Existen errores en el archivo. Corrige antes de importar.',
                'data'    => $analisis,
            ], 422);
        }

        return DB::transaction(function () use ($analisis, $idEmpresa) {
            $codigoToId    = []; // mapa código → id_cuenta para resolver padres
            $insertadas    = 0;
            $actualizadas  = 0;

            // Primero indexar las existentes
            foreach (CuentaContable::porEmpresa($idEmpresa)->get(['id_cuenta', 'codigo']) as $c) {
                $codigoToId[$c->codigo] = $c->id_cuenta;
            }

            // Procesar en orden: primero las que no tienen padre, luego las hijas
            $pendientes = $analisis['validas'];
            $maxIntentos = 10; // evita ciclos infinitos

            while (!empty($pendientes) && $maxIntentos-- > 0) {
                $sigPendientes = [];
                foreach ($pendientes as $row) {
                    // Resolver id_padre desde codigo_padre
                    $idPadre = null;
                    if (!empty($row['codigo_padre'])) {
                        if (!isset($codigoToId[$row['codigo_padre']])) {
                            $sigPendientes[] = $row; // padre aún no insertado
                            continue;
                        }
                        $idPadre = $codigoToId[$row['codigo_padre']];
                    }

                    $datos = [
                        'id_empresa'         => $idEmpresa,
                        'id_padre'           => $idPadre,
                        'codigo'             => $row['codigo'],
                        'nombre'             => $row['nombre'],
                        'tipo'               => $row['tipo'],
                        'naturaleza'         => $row['naturaleza'],
                        'nivel'              => $this->calcularNivel($idPadre),
                        'permite_movimiento' => $row['permite_movimiento'] ?? true,
                        'activo'             => true,
                    ];

                    if (isset($codigoToId[$row['codigo']])) {
                        // Actualizar existente
                        CuentaContable::where('id_cuenta', $codigoToId[$row['codigo']])
                                       ->update($datos);
                        $actualizadas++;
                    } else {
                        // Insertar nueva
                        $nueva = CuentaContable::create($datos);
                        $codigoToId[$row['codigo']] = $nueva->id_cuenta;
                        $insertadas++;
                    }
                }
                $pendientes = $sigPendientes;
            }

            if (!empty($pendientes)) {
                throw new \Exception(
                    'Hay códigos padre que no se pudieron resolver. Revisa la jerarquía del archivo.'
                );
            }

            return response()->json([
                'success' => true,
                'message' => "Importación completada: {$insertadas} nuevas, {$actualizadas} actualizadas.",
                'data'    => ['insertadas' => $insertadas, 'actualizadas' => $actualizadas],
            ]);
        });
    }

    // ════════════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ════════════════════════════════════════════════════════════

    /**
     * Validación común para store/update.
     */
    private function validar(Request $request, int $idEmpresa, ?int $idIgnorar = null): array
    {
        return $request->validate([
            'codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('cuenta_contable', 'codigo')
                    ->where(fn($q) => $q->where('id_empresa', $idEmpresa))
                    ->ignore($idIgnorar, 'id_cuenta'),
            ],
            'nombre'             => 'required|string|max:200',
            'tipo'               => 'required|in:ACTIVO,PASIVO,PATRIMONIO,INGRESO,GASTO,COSTO',
            'naturaleza'         => 'required|in:DEUDORA,ACREEDORA',
            'id_padre'           => 'nullable|exists:cuenta_contable,id_cuenta',
            'permite_movimiento' => 'boolean',
            'activo'             => 'boolean',
        ], [
            'codigo.unique' => 'Ya existe una cuenta con este código en la empresa.',
        ]);
    }

    /**
     * Calcula el nivel jerárquico según el padre.
     */
    private function calcularNivel(?int $idPadre): int
    {
        if (!$idPadre) return 1;
        $padre = CuentaContable::find($idPadre);
        return $padre ? ($padre->nivel + 1) : 1;
    }

    /**
     * Verifica si idCandidato es descendiente de idCuenta (evita ciclos).
     */
    private function esDescendiente(int $idCuenta, int $idCandidato): bool
    {
        if ($idCuenta === $idCandidato) return true;

        $hijas = CuentaContable::where('id_padre', $idCuenta)->pluck('id_cuenta');
        foreach ($hijas as $idHija) {
            if ($this->esDescendiente($idHija, $idCandidato)) return true;
        }
        return false;
    }

    /**
     * Construye árbol recursivo a partir de colección plana.
     */
    private function construirArbol($todas, $idPadre): array
    {
        return $todas
            ->where('id_padre', $idPadre)
            ->map(fn($c) => [
                'id_cuenta'          => $c->id_cuenta,
                'codigo'             => $c->codigo,
                'nombre'             => $c->nombre,
                'tipo'               => $c->tipo,
                'naturaleza'         => $c->naturaleza,
                'nivel'              => $c->nivel,
                'permite_movimiento' => $c->permite_movimiento,
                'activo'             => $c->activo,
                'hijas'              => $this->construirArbol($todas, $c->id_cuenta),
            ])
            ->values()
            ->all();
    }

    /**
     * Analiza filas del Excel y separa válidas/erróneas.
     */
    private function analizarImportacion(array $filas, int $idEmpresa): array
    {
        $validas       = [];
        $errores       = [];
        $codigosVistos = [];

        // Códigos existentes en BD
        $existentes = CuentaContable::porEmpresa($idEmpresa)->pluck('codigo')->toArray();

        // Códigos del archivo (para validar que los padres referenciados existan)
        $codigosArchivo = collect($filas)->pluck('codigo')->toArray();

        foreach ($filas as $idx => $row) {
            $linea     = $idx + 2; // +1 por header, +1 porque es 0-indexed
            $erroresFila = [];

            // Duplicado en archivo
            if (in_array($row['codigo'], $codigosVistos)) {
                $erroresFila[] = "Código duplicado en el archivo";
            }
            $codigosVistos[] = $row['codigo'];

            // Padre debe existir (en BD o en archivo)
            if (!empty($row['codigo_padre'])
                && !in_array($row['codigo_padre'], $existentes)
                && !in_array($row['codigo_padre'], $codigosArchivo)) {
                $erroresFila[] = "Código padre '{$row['codigo_padre']}' no existe";
            }

            // No puede ser su propio padre
            if (!empty($row['codigo_padre']) && $row['codigo_padre'] === $row['codigo']) {
                $erroresFila[] = "La cuenta no puede ser su propio padre";
            }

            if (!empty($erroresFila)) {
                $errores[] = ['linea' => $linea, 'codigo' => $row['codigo'], 'errores' => $erroresFila];
            } else {
                $validas[] = $row;
            }
        }

        return [
            'total'       => count($filas),
            'validas'     => $validas,
            'nuevas'      => count(array_filter($validas,
                fn($r) => !in_array($r['codigo'], $existentes))),
            'actualizar'  => count(array_filter($validas,
                fn($r) => in_array($r['codigo'], $existentes))),
            'errores'     => $errores,
        ];
    }
}