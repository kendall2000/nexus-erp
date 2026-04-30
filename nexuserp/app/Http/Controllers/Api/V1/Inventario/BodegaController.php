<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\Bodega;
use App\Models\Core\Sucursal;
use App\Models\RRHH\Empleado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BodegaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $bodegas = Bodega::with(['sucursal', 'responsable'])
            ->withCount(['stocks as total_productos' => fn($q) => $q->where('cantidad_actual', '>', 0)])
            ->where('id_empresa', $idEmpresa)
            ->orderBy('nombre')
            ->get()
            ->map(fn($item) => [
                'id_bodega'       => $item->id_bodega,
                'nombre'          => $item->nombre,
                'ubicacion'       => $item->ubicacion ?? '—',
                'id_sucursal'     => $item->id_sucursal,
                'sucursal'        => $item->sucursal?->nombre ?? '— Sin sucursal —',
                'responsable_id'  => $item->responsable_id,
                'responsable'     => $item->responsable?->nombre_completo ?? '— Sin asignar —',
                'total_productos' => $item->total_productos,
                'valor_inventario'=> number_format($item->valor_total_inventario, 2),
                'activo'          => $item->activo ? 'Activo' : 'Inactivo',
            ]);

        return response()->json(['success' => true, 'data' => $bodegas]);
    }

    public function catalogos(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $sucursales = Sucursal::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_sucursal as id', 'nombre as name']);

        $empleados = Empleado::where('id_empresa', $idEmpresa)
            ->where('estado', 'ACTIVO')
            ->orderBy('primer_nombre')
            ->orderBy('primer_apellido')
            ->get()
            ->map(fn($e) => [
                'id'   => $e->id_empleado,
                'name' => $e->nombre_completo,   // usa el accessor
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'sucursales' => $sucursales,
                'empleados'  => $empleados,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'nombre'         => 'required|string|max:150',
            'ubicacion'      => 'nullable|string|max:300',
            'id_sucursal'    => 'nullable|exists:sucursal,id_sucursal',
            'responsable_id' => 'nullable|exists:empleado,id_empleado',
            'activo'         => 'boolean',
        ]);

        // Validar que la sucursal pertenezca a la misma empresa
        if ($request->id_sucursal) {
            $sucursalValida = Sucursal::where('id_sucursal', $request->id_sucursal)
                ->where('id_empresa', $idEmpresa)
                ->exists();
            if (!$sucursalValida) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sucursal seleccionada no pertenece a tu empresa.',
                ], 422);
            }
        }

        // Validar que el empleado pertenezca a la misma empresa
        if ($request->responsable_id) {
            $empleadoValido = Empleado::where('id_empleado', $request->responsable_id)
                ->where('id_empresa', $idEmpresa)
                ->exists();
            if (!$empleadoValido) {
                return response()->json([
                    'success' => false,
                    'message' => 'El responsable seleccionado no pertenece a tu empresa.',
                ], 422);
            }
        }

        $bodega = Bodega::create([
            'id_empresa'     => $idEmpresa,
            'id_sucursal'    => $request->id_sucursal,
            'nombre'         => $request->nombre,
            'ubicacion'      => $request->ubicacion,
            'responsable_id' => $request->responsable_id,
            'activo'         => $request->activo ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bodega creada correctamente.',
            'data'    => ['id_bodega' => $bodega->id_bodega],
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $bodega = Bodega::where('id_empresa', $request->user()->id_empresa)
                        ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $bodega]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $bodega = Bodega::where('id_empresa', $idEmpresa)->findOrFail($id);

        $request->validate([
            'nombre'         => 'required|string|max:150',
            'ubicacion'      => 'nullable|string|max:300',
            'id_sucursal'    => 'nullable|exists:sucursal,id_sucursal',
            'responsable_id' => 'nullable|exists:empleado,id_empleado',
            'activo'         => 'boolean',
        ]);

        // Mismas validaciones cross-empresa que en store
        if ($request->id_sucursal) {
            $sucursalValida = Sucursal::where('id_sucursal', $request->id_sucursal)
                ->where('id_empresa', $idEmpresa)
                ->exists();
            if (!$sucursalValida) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sucursal seleccionada no pertenece a tu empresa.',
                ], 422);
            }
        }

        if ($request->responsable_id) {
            $empleadoValido = Empleado::where('id_empleado', $request->responsable_id)
                ->where('id_empresa', $idEmpresa)
                ->exists();
            if (!$empleadoValido) {
                return response()->json([
                    'success' => false,
                    'message' => 'El responsable seleccionado no pertenece a tu empresa.',
                ], 422);
            }
        }

        $bodega->update([
            'id_sucursal'    => $request->id_sucursal,
            'nombre'         => $request->nombre,
            'ubicacion'      => $request->ubicacion,
            'responsable_id' => $request->responsable_id,
            'activo'         => $request->activo ?? $bodega->activo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bodega actualizada correctamente.',
        ]);
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $bodega = Bodega::where('id_empresa', $request->user()->id_empresa)
                        ->findOrFail($id);

        $bodega->update(['activo' => !$bodega->activo]);

        return response()->json([
            'success' => true,
            'message' => $bodega->activo ? 'Bodega activada.' : 'Bodega desactivada.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $bodega = Bodega::where('id_empresa', $request->user()->id_empresa)
                        ->findOrFail($id);

        if ($bodega->stocks()->where('cantidad_actual', '>', 0)->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una bodega con stock disponible.',
            ], 422);
        }

        $bodega->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bodega eliminada correctamente.',
        ]);
    }
}