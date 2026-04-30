<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Sucursal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $sucursales = Sucursal::with(['pais', 'division', 'municipio'])
            ->where('id_empresa', $idEmpresa)
            ->orderBy('nombre')
            ->get()
            ->map(fn($item) => [
                'id_sucursal'    => $item->id_sucursal,
                'nombre'         => $item->nombre,
                'direccion'      => $item->direccion ?? '—',
                'telefono'       => $item->telefono ?? '—',
                'email'          => $item->email ?? '—',
                'id_pais'        => $item->id_pais,
                'pais'           => $item->pais?->nombre ?? '—',
                'id_division'    => $item->id_division,
                'division'       => $item->division?->nombre ?? '—',
                'id_municipio'   => $item->id_municipio,
                'municipio'      => $item->municipio?->nombre ?? '—',
                'es_casa_matriz' => $item->es_casa_matriz ? 'Sí' : 'No',
                'activo'         => $item->activo ? 'Activo' : 'Inactivo',
            ]);

        return response()->json(['success' => true, 'data' => $sucursales]);
    }

    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'nombre'         => 'required|string|max:150',
            'direccion'      => 'nullable|string|max:300',
            'telefono'       => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:100',
            'id_pais'        => 'nullable|exists:pais,id_pais',
            'id_division'    => 'nullable|exists:division_geografica,id_division',
            'id_municipio'   => 'nullable|exists:municipio,id_municipio',
            'es_casa_matriz' => 'boolean',
            'activo'         => 'boolean',
        ]);

        if ($request->es_casa_matriz) {
            Sucursal::where('id_empresa', $idEmpresa)->update(['es_casa_matriz' => false]);
        }

        $sucursal = Sucursal::create([
            'id_empresa'     => $idEmpresa,
            'id_pais'        => $request->id_pais,
            'id_division'    => $request->id_division,
            'id_municipio'   => $request->id_municipio,
            'nombre'         => $request->nombre,
            'direccion'      => $request->direccion,
            'telefono'       => $request->telefono,
            'email'          => $request->email,
            'es_casa_matriz' => $request->es_casa_matriz ?? false,
            'activo'         => $request->activo ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sucursal creada correctamente.',
            'data'    => ['id_sucursal' => $sucursal->id_sucursal],
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $sucursal = Sucursal::where('id_empresa', $request->user()->id_empresa)
                            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $sucursal]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $sucursal  = Sucursal::where('id_empresa', $idEmpresa)->findOrFail($id);

        $request->validate([
            'nombre'         => 'required|string|max:150',
            'direccion'      => 'nullable|string|max:300',
            'telefono'       => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:100',
            'id_pais'        => 'nullable|exists:pais,id_pais',
            'id_division'    => 'nullable|exists:division_geografica,id_division',
            'id_municipio'   => 'nullable|exists:municipio,id_municipio',
            'es_casa_matriz' => 'boolean',
            'activo'         => 'boolean',
        ]);

        if ($request->es_casa_matriz) {
            Sucursal::where('id_empresa', $idEmpresa)
                    ->where('id_sucursal', '!=', $id)
                    ->update(['es_casa_matriz' => false]);
        }

        $sucursal->update([
            'id_pais'        => $request->id_pais,
            'id_division'    => $request->id_division,
            'id_municipio'   => $request->id_municipio,
            'nombre'         => $request->nombre,
            'direccion'      => $request->direccion,
            'telefono'       => $request->telefono,
            'email'          => $request->email,
            'es_casa_matriz' => $request->es_casa_matriz ?? false,
            'activo'         => $request->activo ?? $sucursal->activo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sucursal actualizada correctamente.',
        ]);
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $sucursal = Sucursal::where('id_empresa', $request->user()->id_empresa)
                            ->findOrFail($id);

        $sucursal->update(['activo' => !$sucursal->activo]);

        return response()->json([
            'success' => true,
            'message' => $sucursal->activo ? 'Sucursal activada.' : 'Sucursal desactivada.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $sucursal = Sucursal::where('id_empresa', $request->user()->id_empresa)
                            ->findOrFail($id);

        if ($sucursal->empleados()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una sucursal con empleados asignados.',
            ], 422);
        }

        $sucursal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sucursal eliminada correctamente.',
        ]);
    }
}