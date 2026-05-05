<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\RecepcionMercaderia;
use App\Models\Inventario\DetalleRecepcion;
use App\Models\Inventario\OrdenCompra;
use App\Models\Inventario\StockBodega;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecepcionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $recepciones = RecepcionMercaderia::with(['ordenCompra.proveedor', 'bodega'])
            ->where('id_empresa', $idEmpresa)
            ->orderBy('fecha_recepcion', 'desc')
            ->get()
            ->map(fn($r) => [
                'id_recepcion'     => $r->id_recepcion,
                'numero_recepcion' => $r->numero_recepcion,
                'numero_oc'        => $r->ordenCompra?->numero_oc ?? '—',
                'proveedor'        => $r->ordenCompra?->proveedor?->razon_social ?? '—',
                'bodega'           => $r->bodega?->nombre ?? '—',
                'fecha_recepcion'  => $r->fecha_recepcion?->format('d/m/Y'),
                'total_items'      => $r->detalles()->count(),
                'notas'            => $r->notas ?? '—',
            ]);

        return response()->json(['success' => true, 'data' => $recepciones]);
    }

    public function catalogos(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        // Solo OC en estado ENVIADA o PARCIAL (recibibles)
        $ordenes = OrdenCompra::with('proveedor')
            ->where('id_empresa', $idEmpresa)
            ->whereIn('estado', ['ENVIADA', 'PARCIAL'])
            ->orderBy('numero_oc')
            ->get()
            ->map(fn($oc) => [
                'id'        => $oc->id_oc,
                'name'      => $oc->numero_oc . ' — ' . ($oc->proveedor?->razon_social ?? ''),
                'id_bodega' => $oc->id_bodega,
            ]);

        return response()->json([
            'success' => true,
            'data'    => ['ordenes' => $ordenes],
        ]);
    }

    public function lineasPorOC(Request $request, int $idOC): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $oc = OrdenCompra::where('id_empresa', $idEmpresa)
            ->whereIn('estado', ['ENVIADA', 'PARCIAL'])
            ->findOrFail($idOC);

        $lineas = $oc->detalles()->with('producto')->get()->map(fn($d) => [
            'id_linea'            => $d->id_linea,
            'id_producto'         => $d->id_producto,
            'producto_nombre'     => $d->producto?->nombre ?? '—',
            'producto_codigo'     => $d->producto?->codigo ?? '',
            'descripcion'         => $d->descripcion,
            'cantidad_pedida'     => $d->cantidad_pedida,
            'cantidad_recibida'   => $d->cantidad_recibida,
            'pendiente'           => $d->cantidad_pedida - $d->cantidad_recibida,
            'precio_unitario'     => $d->precio_unitario,
            'unidad_medida'       => $d->producto?->unidad_medida ?? '',
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'oc'     => [
                    'id_oc'      => $oc->id_oc,
                    'numero_oc'  => $oc->numero_oc,
                    'id_bodega'  => $oc->id_bodega,
                    'moneda'     => $oc->moneda,
                ],
                'lineas' => $lineas,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'id_oc'            => 'required|exists:orden_compra,id_oc',
            'id_bodega'        => 'required|exists:bodega,id_bodega',
            'numero_recepcion' => [
                'required', 'string', 'max:30',
                Rule::unique('recepcion_mercaderia', 'numero_recepcion')
                    ->where('id_empresa', $idEmpresa),
            ],
            'fecha_recepcion'  => 'required|date',
            'notas'            => 'nullable|string',
            'detalles'                          => 'required|array|min:1',
            'detalles.*.id_linea'               => 'required|exists:detalle_orden_compra,id_linea',
            'detalles.*.id_producto'            => 'required|exists:producto,id_producto',
            'detalles.*.cantidad_recibida'      => 'required|numeric|min:0.0001',
            'detalles.*.costo_unitario'         => 'required|numeric|min:0',
        ]);

        // Validar que la OC pertenezca a la empresa y esté en estado recibible
        $oc = OrdenCompra::where('id_empresa', $idEmpresa)
            ->whereIn('estado', ['ENVIADA', 'PARCIAL'])
            ->findOrFail($request->id_oc);

        return DB::transaction(function () use ($request, $oc, $idEmpresa) {

            // 1. Crear cabecera
            $recepcion = RecepcionMercaderia::create([
                'id_empresa'       => $idEmpresa,
                'id_oc'            => $oc->id_oc,
                'id_bodega'        => $request->id_bodega,
                'numero_recepcion' => $request->numero_recepcion,
                'fecha_recepcion'  => $request->fecha_recepcion,
                'notas'            => $request->notas,
                'created_by'       => $request->user()->id_usuario,
            ]);

            foreach ($request->detalles as $d) {
                $cantidad = $d['cantidad_recibida'];
                $costo    = $d['costo_unitario'];
                $subtotal = round($cantidad * $costo, 4);

                // 2. Crear detalle
                DetalleRecepcion::create([
                    'id_recepcion'     => $recepcion->id_recepcion,
                    'id_linea'         => $d['id_linea'],
                    'id_producto'      => $d['id_producto'],
                    'cantidad_recibida'=> $cantidad,
                    'costo_unitario'   => $costo,
                    'subtotal'         => $subtotal,
                ]);

                // 3. Actualizar cantidad_recibida en detalle_orden_compra
                $lineaOC = \App\Models\Inventario\DetalleOrdenCompra::findOrFail($d['id_linea']);
                $lineaOC->increment('cantidad_recibida', $cantidad);

                // 4. Actualizar stock (costo promedio ponderado)
                $stock = StockBodega::firstOrCreate(
                    ['id_producto' => $d['id_producto'], 'id_bodega' => $request->id_bodega],
                    ['cantidad_actual' => 0, 'costo_promedio' => 0]
                );
                $stock->actualizarCostoPromedio($cantidad, $costo);
            }

            // 5. Actualizar estado de la OC
            $oc->refresh();
            $totalPedido   = $oc->detalles()->sum('cantidad_pedida');
            $totalRecibido = $oc->detalles()->sum('cantidad_recibida');

            $nuevoEstado = $totalRecibido >= $totalPedido ? 'RECIBIDA' : 'PARCIAL';
            $oc->update(['estado' => $nuevoEstado]);

            return response()->json([
                'success' => true,
                'message' => 'Recepción registrada correctamente. OC actualizada a ' . $nuevoEstado . '.',
                'data'    => ['id_recepcion' => $recepcion->id_recepcion],
            ], 201);
        });
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $recepcion = RecepcionMercaderia::with(['ordenCompra.proveedor', 'bodega', 'detalles.producto'])
            ->where('id_empresa', $request->user()->id_empresa)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id_recepcion'     => $recepcion->id_recepcion,
                'numero_recepcion' => $recepcion->numero_recepcion,
                'numero_oc'        => $recepcion->ordenCompra?->numero_oc,
                'proveedor'        => $recepcion->ordenCompra?->proveedor?->razon_social,
                'bodega'           => $recepcion->bodega?->nombre,
                'id_bodega'        => $recepcion->id_bodega,
                'fecha_recepcion'  => $recepcion->fecha_recepcion?->format('Y-m-d'),
                'notas'            => $recepcion->notas,
                'detalles'         => $recepcion->detalles->map(fn($d) => [
                    'id_detalle_rec'   => $d->id_detalle_rec,
                    'id_producto'      => $d->id_producto,
                    'producto_nombre'  => $d->producto?->nombre ?? '—',
                    'producto_codigo'  => $d->producto?->codigo ?? '',
                    'cantidad_recibida'=> $d->cantidad_recibida,
                    'costo_unitario'   => $d->costo_unitario,
                    'subtotal'         => $d->subtotal,
                ]),
            ],
        ]);
    }
}