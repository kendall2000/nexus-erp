<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\CategoriaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaProductoController extends Controller
{
    // ────────────────────────────────────────────────────────────
    // GET /api/v1/inventario/categorias
    // ────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $categorias = CategoriaProducto::with('padre')
            ->where('id_empresa', $idEmpresa)
            ->orderBy('nombre')
            ->get()
            ->map(fn($item) => [
                'id_categoria'      => $item->id_categoria,
                'nombre'            => $item->nombre,
                'descripcion'       => $item->descripcion ?? '—',
                'padre'             => $item->padre?->nombre ?? '— Categoría raíz —',
                'id_padre'          => $item->id_padre,
                'total_productos'   => $item->productos()->count(),
                'activo'            => $item->activo ? 'Activo' : 'Inactivo',
            ]);

        return response()->json(['success' => true, 'data' => $categorias]);
    }

    // ────────────────────────────────────────────────────────────
    // GET /api/v1/inventario/categorias/catalogos
    // Retorna las categorías raíz para el select del formulario
    // ────────────────────────────────────────────────────────────
    public function catalogos(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $padres = CategoriaProducto::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_categoria as id', 'nombre as name']);

        return response()->json([
            'success' => true,
            'data'    => ['padres' => $padres],
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // POST /api/v1/inventario/categorias
    // ────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'nombre'      => [
                'required', 'string', 'max:150',
                Rule::unique('categoria_producto', 'nombre')
                    ->where('id_empresa', $idEmpresa)
            ],
            'descripcion' => 'nullable|string|max:300',
            'id_padre'    => 'nullable|exists:categoria_producto,id_categoria',
            'activo'      => 'boolean',
        ], [
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
        ]);

        $categoria = CategoriaProducto::create([
            'id_empresa'  => $idEmpresa,
            'id_padre'    => $request->id_padre,
            'nombre'      => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo'      => $request->activo ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Categoría creada correctamente.',
            'data'    => ['id_categoria' => $categoria->id_categoria],
        ], 201);
    }

    // ────────────────────────────────────────────────────────────
    // GET /api/v1/inventario/categorias/{id}
    // ────────────────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $categoria = CategoriaProducto::where('id_empresa', $request->user()->id_empresa)
                                      ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $categoria]);
    }

    // ────────────────────────────────────────────────────────────
    // PUT /api/v1/inventario/categorias/{id}
    // ────────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $categoria = CategoriaProducto::where('id_empresa', $idEmpresa)->findOrFail($id);

        $request->validate([
            'nombre'      => [
                'required', 'string', 'max:150',
                Rule::unique('categoria_producto', 'nombre')
                    ->where('id_empresa', $idEmpresa)
                    ->ignore($id, 'id_categoria')
            ],
            'descripcion' => 'nullable|string|max:300',
            'id_padre'    => 'nullable|exists:categoria_producto,id_categoria',
            'activo'      => 'boolean',
        ]);

        // Validación de negocio: una categoría no puede ser su propia padre
        if ($request->id_padre == $id) {
            return response()->json([
                'success' => false,
                'message' => 'Una categoría no puede ser su propia padre.',
            ], 422);
        }

        $categoria->update([
            'id_padre'    => $request->id_padre,
            'nombre'      => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo'      => $request->activo ?? $categoria->activo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada correctamente.',
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // PATCH /api/v1/inventario/categorias/{id}/toggle
    // ────────────────────────────────────────────────────────────
    public function toggle(Request $request, int $id): JsonResponse
    {
        $categoria = CategoriaProducto::where('id_empresa', $request->user()->id_empresa)
                                      ->findOrFail($id);

        $categoria->update(['activo' => !$categoria->activo]);

        return response()->json([
            'success' => true,
            'message' => $categoria->activo ? 'Categoría activada.' : 'Categoría desactivada.',
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // DELETE /api/v1/inventario/categorias/{id}
    // ────────────────────────────────────────────────────────────
    public function destroy(Request $request, int $id): JsonResponse
    {
        $categoria = CategoriaProducto::where('id_empresa', $request->user()->id_empresa)
                                      ->findOrFail($id);

        // Protección 1: no eliminar si tiene categorías hijas
        if ($categoria->hijos()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una categoría que tiene subcategorías. Elimina primero las subcategorías.',
            ], 422);
        }

        // Protección 2: no eliminar si tiene productos asociados
        if ($categoria->productos()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una categoría que tiene productos asignados.',
            ], 422);
        }

        $categoria->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada correctamente.',
        ]);
    }
}