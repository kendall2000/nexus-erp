<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestionMenuController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/gestion-menu
    // ────────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $items = Menu::with('padre')
            ->where('id_empresa', $idEmpresa)
            ->orderBy('id_padre')
            ->orderBy('orden')
            ->get()
            ->map(fn($item) => [
                'id_menu'   => $item->id_menu,
                'nombre'    => $item->nombre,
                'id_padre'  => $item->id_padre,
                'padre'     => $item->padre?->nombre ?? '— Grupo principal —',
                'icono'     => $item->icono ?? 'chevrons-right',
                'ruta'      => $item->ruta ?? '',
                'orden'     => $item->orden,
                'activo'    => $item->activo ? 'Activo' : 'Inactivo',
                'es_grupo'  => is_null($item->id_padre) ? 'Sí' : 'No',
            ]);

        return response()->json(['success' => true, 'data' => $items]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/gestion-menu/catalogos
    // ────────────────────────────────────────────────────────────────────────
    public function catalogos(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $grupos = Menu::whereNull('id_padre')
            ->where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('orden')
            ->get(['id_menu as id', 'nombre as name']);

        return response()->json([
            'success' => true,
            'data'    => ['grupos' => $grupos],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/gestion-menu/arbol
    // Categorías con sus hijos (para los selects del modal)
    // ────────────────────────────────────────────────────────────────────────
    public function arbol(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $categorias = Menu::with(['hijos' => fn($q) =>
                            $q->where('activo', true)->orderBy('orden')
                        ])
                        ->whereNull('id_padre')
                        ->where('id_empresa', $idEmpresa)
                        ->where('activo', true)
                        ->orderBy('orden')
                        ->get()
                        ->map(fn($cat) => [
                            'id_menu' => $cat->id_menu,
                            'nombre'  => $cat->nombre,
                            'hijos'   => $cat->hijos->map(fn($h) => [
                                'id_menu' => $h->id_menu,
                                'nombre'  => $h->nombre,
                            ])->values(),
                        ]);

        return response()->json(['success' => true, 'data' => $categorias]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/gestion-menu/{id}
    // ────────────────────────────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $item = Menu::where('id_empresa', $request->user()->id_empresa)
                    ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id_menu'  => $item->id_menu,
                'nombre'   => $item->nombre,
                'id_padre' => $item->id_padre,
                'icono'    => $item->icono,
                'ruta'     => $item->ruta,
                'orden'    => $item->orden,
                'activo'   => (bool) $item->activo,
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST /api/v1/gestion-menu
    // Cualquier nivel acepta ruta (opcional o no, según el frontend)
    // ────────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'nombre'   => 'required|string|max:100',
            'id_padre' => 'nullable|exists:menu,id_menu',
            'icono'    => 'nullable|string|max:50',
            'ruta'     => 'nullable|string|max:200',
            'orden'    => 'required|integer|min:1|max:255',
            'activo'   => 'boolean',
        ]);

        $item = Menu::create([
            'id_empresa' => $idEmpresa,
            'id_padre'   => $request->id_padre,
            'nombre'     => $request->nombre,
            'icono'      => $request->icono ?? 'chevrons-right',
            'ruta'       => $request->ruta,
            'orden'      => $request->orden,
            'activo'     => $request->activo ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ítem de menú creado correctamente.',
            'data'    => ['id_menu' => $item->id_menu],
        ], 201);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PUT /api/v1/gestion-menu/{id}
    // ────────────────────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $item = Menu::where('id_empresa', $request->user()->id_empresa)
                    ->findOrFail($id);

        $request->validate([
            'nombre'   => 'required|string|max:100',
            'id_padre' => 'nullable|exists:menu,id_menu',
            'icono'    => 'nullable|string|max:50',
            'ruta'     => 'nullable|string|max:200',
            'orden'    => 'required|integer|min:1|max:255',
            'activo'   => 'boolean',
        ]);

        if ($request->id_padre == $id) {
            return response()->json([
                'success' => false,
                'message' => 'Un ítem no puede ser su propio padre.',
            ], 422);
        }

        $item->update([
            'nombre'   => $request->nombre,
            'id_padre' => $request->id_padre,
            'icono'    => $request->icono ?? 'chevrons-right',
            'ruta'     => $request->ruta,
            'orden'    => $request->orden,
            'activo'   => $request->activo ?? $item->activo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ítem actualizado correctamente.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PATCH /api/v1/gestion-menu/{id}/toggle
    // ────────────────────────────────────────────────────────────────────────
    public function toggle(Request $request, int $id): JsonResponse
    {
        $item = Menu::where('id_empresa', $request->user()->id_empresa)
                    ->findOrFail($id);

        $item->update(['activo' => !$item->activo]);

        // Si se desactiva un grupo padre, desactiva también sus hijos directos
        if (is_null($item->id_padre) && !$item->activo) {
            Menu::where('id_padre', $id)->update(['activo' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => $item->activo ? 'Ítem activado.' : 'Ítem desactivado.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // DELETE /api/v1/gestion-menu/{id}
    // ────────────────────────────────────────────────────────────────────────
    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = Menu::where('id_empresa', $request->user()->id_empresa)
                    ->findOrFail($id);

        if ($item->hijos()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un grupo que tiene ítems hijos. Elimina los ítems primero.',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ítem eliminado correctamente.',
        ]);
    }
}