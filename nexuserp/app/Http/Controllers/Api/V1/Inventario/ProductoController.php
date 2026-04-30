<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\Producto;
use App\Models\Inventario\CategoriaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $productos = Producto::with(['categoria'])
            ->where('id_empresa', $idEmpresa)
            ->orderBy('nombre')
            ->get()
            ->map(fn($item) => [
                'id_producto'   => $item->id_producto,
                'codigo'        => $item->codigo,
                'nombre'        => $item->nombre,
                'categoria'     => $item->categoria?->nombre ?? '— Sin Categoría —',
                'unidad_medida' => $item->unidad_medida,
                'precio_venta'  => $item->precio_venta,
                'stock_minimo'  => $item->stock_minimo,
                'activo'        => $item->activo ? 'Activo' : 'Inactivo',
            ]);

        return response()->json(['success' => true, 'data' => $productos]);
    }

    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'id_categoria'  => 'nullable|exists:categoria_producto,id_categoria',
            'codigo'        => ['required', 'string', 'max:50', Rule::unique('producto')->where('id_empresa', $idEmpresa)],
            'nombre'        => 'required|string|max:200',
            'unidad_medida' => 'required|string|max:30',
            'precio_compra' => 'nullable|numeric',
            'precio_venta'  => 'nullable|numeric',
            'stock_minimo'  => 'required|numeric',
            'stock_maximo'  => 'nullable|numeric',
            'moneda'        => 'required|string|size:3',
        ]);

        $producto = Producto::create(array_merge($request->all(), [
            'id_empresa' => $idEmpresa,
            'activo'     => true
        ]));

        return response()->json(['success' => true, 'message' => 'Producto creado.', 'id' => $producto->id_producto]);
    }

    public function catalogos(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $categorias = CategoriaProducto::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_categoria as id', 'nombre as name']);

        return response()->json([
            'success' => true,
            'data'    => [
                'categorias' => $categorias,
                'unidades'   => ['UND', 'LT', 'KG', 'MT', 'CAJA'] // Unidades fijas según tu SQL
            ],
        ]);
    }
    

    public function show(Request $request, int $id): JsonResponse
    {
        $producto = Producto::where('id_empresa', $request->user()->id_empresa)
                            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $producto,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $producto = Producto::where('id_empresa', $idEmpresa)->findOrFail($id);

        $request->validate([
            'id_categoria'  => 'nullable|integer',
            'codigo'        => [
                'required', 'string', 'max:50',
                Rule::unique('producto', 'codigo')
                    ->where('id_empresa', $idEmpresa)
                    ->ignore($producto->id_producto, 'id_producto')
            ],
            'nombre'        => 'required|string|max:200',
            'descripcion'   => 'nullable|string',
            'unidad_medida' => 'required|string|max:30',
            'precio_compra' => 'nullable|numeric|min:0',
            'precio_venta'  => 'nullable|numeric|min:0',
            'moneda'        => 'required|string|size:3',
            'stock_minimo'  => 'required|numeric|min:0',
            'stock_maximo'  => 'nullable|numeric|min:0',
            'requiere_lote' => 'boolean',
            'es_perecedero' => 'boolean',
            'activo'        => 'boolean',
        ]);

        $producto->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
        ]);
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $producto = Producto::where('id_empresa', $request->user()->id_empresa)->findOrFail($id);
        $producto->update(['activo' => !$producto->activo]);

        return response()->json([
            'success' => true,
            'message' => $producto->activo ? 'Producto activado.' : 'Producto desactivado.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $producto = Producto::where('id_empresa', $request->user()->id_empresa)->findOrFail($id);
        $producto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}