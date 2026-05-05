<?php
// app/Http/Controllers/Api/V1/Core/CentroCostoController.php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\CentroCosto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CentroCostoController extends Controller
{
    /**
     * Listado de centros de costo de la empresa actual.
     * GET /api/v1/core/centros-costo
     */
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $centros = CentroCosto::porEmpresa($idEmpresa)
            ->when($request->activo !== null, fn($q) => $q->where('activo', $request->boolean('activo')))
            ->orderBy('codigo')
            ->get()
            ->map(fn($c) => [
                'id_centro'   => $c->id_centro,
                'codigo'      => $c->codigo,
                'nombre'      => $c->nombre,
                'descripcion' => $c->descripcion ?? '—',
                'activo'      => $c->activo,
                // Conteo de presupuestos ligados (útil para deshabilitar borrado)
                'presupuestos_count' => $c->presupuestos()->count(),
            ]);

        return response()->json(['success' => true, 'data' => $centros]);
    }

    /**
     * Catálogo simple para selects en otros módulos.
     * GET /api/v1/core/centros-costo/catalogo
     */
    public function catalogo(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $centros = CentroCosto::porEmpresa($idEmpresa)
            ->activos()
            ->orderBy('codigo')
            ->get()
            ->map(fn($c) => [
                'id'   => $c->id_centro,
                'name' => "{$c->codigo} — {$c->nombre}",
            ]);

        return response()->json(['success' => true, 'data' => $centros]);
    }

    /**
     * Detalle de un centro de costo.
     * GET /api/v1/core/centros-costo/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $centro = CentroCosto::porEmpresa($request->user()->id_empresa)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id_centro'   => $centro->id_centro,
                'codigo'      => $centro->codigo,
                'nombre'      => $centro->nombre,
                'descripcion' => $centro->descripcion,
                'activo'      => $centro->activo,
            ],
        ]);
    }

    /**
     * Crear un nuevo centro de costo.
     * POST /api/v1/core/centros-costo
     */
    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $datos = $request->validate([
            'codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('centro_costo', 'codigo')
                    ->where(fn($q) => $q->where('id_empresa', $idEmpresa)),
            ],
            'nombre'      => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:300',
            'activo'      => 'boolean',
        ], [
            'codigo.unique' => 'Ya existe un centro de costo con este código en la empresa.',
        ]);

        $centro = CentroCosto::create(array_merge($datos, [
            'id_empresa' => $idEmpresa,
            'activo'     => $datos['activo'] ?? true,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Centro de costo creado correctamente.',
            'data'    => ['id_centro' => $centro->id_centro],
        ], 201);
    }

    /**
     * Actualizar un centro de costo existente.
     * PUT /api/v1/core/centros-costo/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $centro    = CentroCosto::porEmpresa($idEmpresa)->findOrFail($id);

        $datos = $request->validate([
            'codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('centro_costo', 'codigo')
                    ->where(fn($q) => $q->where('id_empresa', $idEmpresa))
                    ->ignore($id, 'id_centro'),
            ],
            'nombre'      => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:300',
            'activo'      => 'boolean',
        ], [
            'codigo.unique' => 'Ya existe un centro de costo con este código en la empresa.',
        ]);

        $centro->update($datos);

        return response()->json([
            'success' => true,
            'message' => 'Centro de costo actualizado correctamente.',
        ]);
    }

    /**
     * Cambiar estado activo/inactivo (toggle).
     * PATCH /api/v1/core/centros-costo/{id}/toggle
     */
    public function toggle(Request $request, int $id): JsonResponse
    {
        $centro = CentroCosto::porEmpresa($request->user()->id_empresa)->findOrFail($id);
        $centro->update(['activo' => !$centro->activo]);

        return response()->json([
            'success' => true,
            'message' => $centro->activo
                ? 'Centro de costo activado.'
                : 'Centro de costo desactivado.',
            'data' => ['activo' => $centro->activo],
        ]);
    }

    /**
     * Eliminar (solo si no tiene presupuestos asociados).
     * DELETE /api/v1/core/centros-costo/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $centro = CentroCosto::porEmpresa($request->user()->id_empresa)->findOrFail($id);

        // Validación de integridad: no eliminar si tiene presupuestos vinculados
        $tienePresupuestos = $centro->presupuestos()->exists();

        if ($tienePresupuestos) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: este centro tiene presupuestos asociados. Considera desactivarlo.',
            ], 422);
        }

        $centro->delete();

        return response()->json([
            'success' => true,
            'message' => 'Centro de costo eliminado correctamente.',
        ]);
    }
}