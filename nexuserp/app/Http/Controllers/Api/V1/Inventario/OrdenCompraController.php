<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\OrdenCompra;
use App\Models\Inventario\DetalleOrdenCompra;
use App\Models\Inventario\Proveedor;
use App\Models\Inventario\Bodega;
use App\Models\Inventario\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrdenCompraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $ordenes = OrdenCompra::with(['proveedor', 'bodega'])
            ->where('id_empresa', $idEmpresa)
            ->orderBy('fecha_emision', 'desc')
            ->get()
            ->map(fn($oc) => [
                'id_oc'                  => $oc->id_oc,
                'numero_oc'              => $oc->numero_oc,
                'proveedor'              => $oc->proveedor?->razon_social ?? '—',
                'bodega'                 => $oc->bodega?->nombre ?? '— Sin bodega —',
                'fecha_emision'          => $oc->fecha_emision?->format('d/m/Y'),
                'fecha_entrega_esperada' => $oc->fecha_entrega_esperada?->format('d/m/Y') ?? '—',
                'moneda'                 => $oc->moneda,
                'total'                  => number_format($oc->total, 2),
                'estado'                 => $oc->estado,
                'porcentaje_recepcion'   => $oc->porcentaje_recepcion . '%',
                'activo'                 => $oc->estado !== 'CANCELADA' ? 'Activo' : 'Inactivo',
            ]);

        return response()->json(['success' => true, 'data' => $ordenes]);
    }

    public function catalogos(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $proveedores = Proveedor::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('razon_social')
            ->get(['id_proveedor as id', 'razon_social as name', 'moneda_pago']);

        $bodegas = Bodega::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_bodega as id', 'nombre as name']);

        $productos = Producto::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_producto as id', 'codigo', 'nombre as name', 'precio_compra', 'unidad_medida']);

        $monedas = ['GTQ', 'USD', 'EUR', 'MXN', 'HNL', 'NIO', 'CRC'];
        $estados = ['BORRADOR', 'ENVIADA', 'PARCIAL', 'RECIBIDA', 'CANCELADA'];

        return response()->json([
            'success' => true,
            'data'    => [
                'proveedores' => $proveedores,
                'bodegas'     => $bodegas,
                'productos'   => $productos,
                'monedas'     => $monedas,
                'estados'     => $estados,
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $oc = OrdenCompra::with(['proveedor', 'bodega', 'detalles.producto'])
            ->where('id_empresa', $request->user()->id_empresa)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id_oc'                  => $oc->id_oc,
                'numero_oc'              => $oc->numero_oc,
                'id_proveedor'           => $oc->id_proveedor,
                'id_bodega'              => $oc->id_bodega,
                'fecha_emision'          => $oc->fecha_emision?->format('Y-m-d'),
                'fecha_entrega_esperada' => $oc->fecha_entrega_esperada?->format('Y-m-d'),
                'fecha_entrega_real'     => $oc->fecha_entrega_real?->format('Y-m-d'),
                'moneda'                 => $oc->moneda,
                'subtotal'               => $oc->subtotal,
                'iva'                    => $oc->iva,
                'total'                  => $oc->total,
                'estado'                 => $oc->estado,
                'notas'                  => $oc->notas,
                'detalles'               => $oc->detalles->map(fn($d) => [
                    'id_linea'          => $d->id_linea,
                    'id_producto'       => $d->id_producto,
                    'producto_nombre'   => $d->producto?->nombre ?? '—',
                    'producto_codigo'   => $d->producto?->codigo ?? '',
                    'descripcion'       => $d->descripcion,
                    'cantidad_pedida'   => $d->cantidad_pedida,
                    'cantidad_recibida' => $d->cantidad_recibida,
                    'precio_unitario'   => $d->precio_unitario,
                    'descuento'         => $d->descuento,
                    'subtotal'          => $d->subtotal,
                ]),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'numero_oc'              => [
                'required', 'string', 'max:30',
                Rule::unique('orden_compra', 'numero_oc')->where('id_empresa', $idEmpresa),
            ],
            'id_proveedor'           => 'required|exists:proveedor,id_proveedor',
            'id_bodega'              => 'nullable|exists:bodega,id_bodega',
            'fecha_emision'          => 'required|date',
            'fecha_entrega_esperada' => 'nullable|date|after_or_equal:fecha_emision',
            'moneda'                 => 'required|string|size:3',
            'estado'                 => 'required|in:BORRADOR,ENVIADA,PARCIAL,RECIBIDA,CANCELADA',
            'notas'                  => 'nullable|string',
            'detalles'                       => 'required|array|min:1',
            'detalles.*.id_producto'         => 'required|exists:producto,id_producto',
            'detalles.*.descripcion'         => 'nullable|string|max:300',
            'detalles.*.cantidad_pedida'     => 'required|numeric|min:0.0001',
            'detalles.*.precio_unitario'     => 'required|numeric|min:0',
            'detalles.*.descuento'           => 'nullable|numeric|min:0',
        ]);

        // Validar que proveedor y bodega pertenezcan a la empresa
        $this->validarPertenenciaEmpresa($request, $idEmpresa);

        return DB::transaction(function () use ($request, $idEmpresa) {
            // 1. Crear cabecera
            $oc = OrdenCompra::create([
                'id_empresa'             => $idEmpresa,
                'id_proveedor'           => $request->id_proveedor,
                'id_bodega'              => $request->id_bodega,
                'numero_oc'              => $request->numero_oc,
                'fecha_emision'          => $request->fecha_emision,
                'fecha_entrega_esperada' => $request->fecha_entrega_esperada,
                'moneda'                 => $request->moneda,
                'estado'                 => $request->estado,
                'notas'                  => $request->notas,
                'subtotal'               => 0,
                'iva'                    => 0,
                'total'                  => 0,
                'created_by'             => $request->user()->id_usuario,
            ]);

            // 2. Crear líneas
            foreach ($request->detalles as $d) {
                $cantidad  = $d['cantidad_pedida'];
                $precio    = $d['precio_unitario'];
                $descuento = $d['descuento'] ?? 0;
                $subtotal  = round(($cantidad * $precio) - $descuento, 4);

                DetalleOrdenCompra::create([
                    'id_oc'             => $oc->id_oc,
                    'id_producto'       => $d['id_producto'],
                    'descripcion'       => $d['descripcion'] ?? null,
                    'cantidad_pedida'   => $cantidad,
                    'cantidad_recibida' => 0,
                    'precio_unitario'   => $precio,
                    'descuento'         => $descuento,
                    'subtotal'          => $subtotal,
                ]);
            }

            // 3. Recalcular totales
            $oc->recalcularTotales();

            return response()->json([
                'success' => true,
                'message' => 'Orden de compra creada correctamente.',
                'data'    => ['id_oc' => $oc->id_oc],
            ], 201);
        });
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $oc = OrdenCompra::where('id_empresa', $idEmpresa)->findOrFail($id);

        // Solo BORRADOR se puede editar libremente
        if (!in_array($oc->estado, ['BORRADOR', 'ENVIADA'])) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede editar una orden en estado ' . $oc->estado . '.',
            ], 422);
        }

        $request->validate([
            'numero_oc'              => [
                'required', 'string', 'max:30',
                Rule::unique('orden_compra', 'numero_oc')
                    ->where('id_empresa', $idEmpresa)
                    ->ignore($id, 'id_oc'),
            ],
            'id_proveedor'           => 'required|exists:proveedor,id_proveedor',
            'id_bodega'              => 'nullable|exists:bodega,id_bodega',
            'fecha_emision'          => 'required|date',
            'fecha_entrega_esperada' => 'nullable|date|after_or_equal:fecha_emision',
            'moneda'                 => 'required|string|size:3',
            'estado'                 => 'required|in:BORRADOR,ENVIADA,PARCIAL,RECIBIDA,CANCELADA',
            'notas'                  => 'nullable|string',
            'detalles'                       => 'required|array|min:1',
            'detalles.*.id_producto'         => 'required|exists:producto,id_producto',
            'detalles.*.cantidad_pedida'     => 'required|numeric|min:0.0001',
            'detalles.*.precio_unitario'     => 'required|numeric|min:0',
        ]);

        $this->validarPertenenciaEmpresa($request, $idEmpresa);

        return DB::transaction(function () use ($request, $oc) {
            $oc->update([
                'id_proveedor'           => $request->id_proveedor,
                'id_bodega'              => $request->id_bodega,
                'numero_oc'              => $request->numero_oc,
                'fecha_emision'          => $request->fecha_emision,
                'fecha_entrega_esperada' => $request->fecha_entrega_esperada,
                'moneda'                 => $request->moneda,
                'estado'                 => $request->estado,
                'notas'                  => $request->notas,
            ]);

            // Reemplazar detalles (estrategia: borrar y recrear)
            $oc->detalles()->delete();

            foreach ($request->detalles as $d) {
                $cantidad  = $d['cantidad_pedida'];
                $precio    = $d['precio_unitario'];
                $descuento = $d['descuento'] ?? 0;
                $subtotal  = round(($cantidad * $precio) - $descuento, 4);

                DetalleOrdenCompra::create([
                    'id_oc'             => $oc->id_oc,
                    'id_producto'       => $d['id_producto'],
                    'descripcion'       => $d['descripcion'] ?? null,
                    'cantidad_pedida'   => $cantidad,
                    'cantidad_recibida' => 0,
                    'precio_unitario'   => $precio,
                    'descuento'         => $descuento,
                    'subtotal'          => $subtotal,
                ]);
            }

            $oc->recalcularTotales();

            return response()->json([
                'success' => true,
                'message' => 'Orden de compra actualizada correctamente.',
            ]);
        });
    }

    public function aprobar(Request $request, int $id): JsonResponse
    {
        $oc = OrdenCompra::where('id_empresa', $request->user()->id_empresa)
                         ->findOrFail($id);

        if ($oc->estado !== 'BORRADOR') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden aprobar órdenes en BORRADOR.',
            ], 422);
        }

        $oc->update([
            'estado'       => 'ENVIADA',
            'aprobado_por' => $request->user()->id_usuario,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Orden aprobada y enviada al proveedor.',
        ]);
    }

    public function cancelar(Request $request, int $id): JsonResponse
    {
        $oc = OrdenCompra::where('id_empresa', $request->user()->id_empresa)
                         ->findOrFail($id);

        if (in_array($oc->estado, ['RECIBIDA', 'CANCELADA'])) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cancelar una orden en estado ' . $oc->estado . '.',
            ], 422);
        }

        $oc->update(['estado' => 'CANCELADA']);

        return response()->json([
            'success' => true,
            'message' => 'Orden cancelada correctamente.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $oc = OrdenCompra::where('id_empresa', $request->user()->id_empresa)
                         ->findOrFail($id);

        if ($oc->estado !== 'BORRADOR') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden eliminar órdenes en BORRADOR.',
            ], 422);
        }

        $oc->detalles()->delete();
        $oc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Orden eliminada correctamente.',
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════
    private function validarPertenenciaEmpresa(Request $request, int $idEmpresa): void
    {
        $proveedorOk = Proveedor::where('id_proveedor', $request->id_proveedor)
            ->where('id_empresa', $idEmpresa)->exists();
        if (!$proveedorOk) {
            abort(response()->json([
                'success' => false,
                'message' => 'El proveedor no pertenece a tu empresa.',
            ], 422));
        }

        if ($request->id_bodega) {
            $bodegaOk = Bodega::where('id_bodega', $request->id_bodega)
                ->where('id_empresa', $idEmpresa)->exists();
            if (!$bodegaOk) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'La bodega no pertenece a tu empresa.',
                ], 422));
            }
        }
    }
}