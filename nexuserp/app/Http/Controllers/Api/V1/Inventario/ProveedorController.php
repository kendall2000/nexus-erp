<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\Proveedor;
use App\Models\Core\Pais;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $proveedores = Proveedor::with('pais')
            ->where('id_empresa', $idEmpresa)
            ->orderBy('razon_social')
            ->get()
            ->map(fn($item) => [
                'id_proveedor'     => $item->id_proveedor,
                'razon_social'     => $item->razon_social,
                'nombre_comercial' => $item->nombre_comercial ?? '—',
                'nit'              => $item->nit ?? '—',
                'email'            => $item->email ?? '—',
                'telefono'         => $item->telefono ?? '—',
                'pais'             => $item->pais?->nombre ?? '—',
                'tipo_proveedor'   => $item->tipo_proveedor,
                'dias_credito'     => $item->dias_credito,
                'moneda_pago'      => $item->moneda_pago,
                'activo'           => $item->activo ? 'Activo' : 'Inactivo',
            ]);

        return response()->json(['success' => true, 'data' => $proveedores]);
    }

    public function catalogos(): JsonResponse
    {
        $paises = Pais::where('activo', true)
            ->orderBy('nombre')
            ->get(['id_pais as id', 'nombre as name']);

        $monedas = ['GTQ', 'USD', 'EUR', 'MXN', 'HNL', 'NIO', 'CRC', 'SVC'];
        $tipos   = ['BIENES', 'SERVICIOS', 'AMBOS'];

        return response()->json([
            'success' => true,
            'data'    => [
                'paises'  => $paises,
                'monedas' => $monedas,
                'tipos'   => $tipos,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'id_pais'          => 'required|exists:pais,id_pais',
            'razon_social'     => 'required|string|max:250',
            'nombre_comercial' => 'nullable|string|max:150',
            'nit'              => [
                'nullable', 'string', 'max:20',
                Rule::unique('proveedor', 'nit')
                    ->where('id_empresa', $idEmpresa)
                    ->whereNull('deleted_at'),
            ],
            'email'            => 'nullable|email|max:150',
            'telefono'         => 'nullable|string|max:20',
            'direccion'        => 'nullable|string|max:300',
            'contacto'         => 'nullable|string|max:200',
            'tipo_proveedor'   => 'required|in:BIENES,SERVICIOS,AMBOS',
            'dias_credito'     => 'required|integer|min:0|max:255',
            'moneda_pago'      => 'required|string|size:3',
            'activo'           => 'boolean',
        ]);

        $proveedor = Proveedor::create(array_merge(
            $request->all(),
            ['id_empresa' => $idEmpresa, 'activo' => $request->activo ?? true]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Proveedor creado correctamente.',
            'data'    => ['id_proveedor' => $proveedor->id_proveedor],
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $proveedor = Proveedor::where('id_empresa', $request->user()->id_empresa)
                              ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $proveedor]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $proveedor = Proveedor::where('id_empresa', $idEmpresa)->findOrFail($id);

        $request->validate([
            'id_pais'          => 'required|exists:pais,id_pais',
            'razon_social'     => 'required|string|max:250',
            'nombre_comercial' => 'nullable|string|max:150',
            'nit'              => [
                'nullable', 'string', 'max:20',
                Rule::unique('proveedor', 'nit')
                    ->where('id_empresa', $idEmpresa)
                    ->whereNull('deleted_at')
                    ->ignore($id, 'id_proveedor'),
            ],
            'email'            => 'nullable|email|max:150',
            'telefono'         => 'nullable|string|max:20',
            'direccion'        => 'nullable|string|max:300',
            'contacto'         => 'nullable|string|max:200',
            'tipo_proveedor'   => 'required|in:BIENES,SERVICIOS,AMBOS',
            'dias_credito'     => 'required|integer|min:0|max:255',
            'moneda_pago'      => 'required|string|size:3',
            'activo'           => 'boolean',
        ]);

        $proveedor->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Proveedor actualizado correctamente.',
        ]);
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $proveedor = Proveedor::where('id_empresa', $request->user()->id_empresa)
                              ->findOrFail($id);

        $proveedor->update(['activo' => !$proveedor->activo]);

        return response()->json([
            'success' => true,
            'message' => $proveedor->activo ? 'Proveedor activado.' : 'Proveedor desactivado.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $proveedor = Proveedor::where('id_empresa', $request->user()->id_empresa)
                              ->findOrFail($id);

        // Validar que no tenga órdenes de compra vigentes
        if ($proveedor->ordenesCompra()->whereIn('estado', ['ENVIADA', 'PARCIAL'])->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un proveedor con órdenes de compra vigentes.',
            ], 422);
        }

        $proveedor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proveedor eliminado correctamente.',
        ]);
    }
}