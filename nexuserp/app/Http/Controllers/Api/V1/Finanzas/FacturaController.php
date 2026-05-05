<?php

namespace App\Http\Controllers\Api\V1\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\Finanzas\Factura;
use App\Models\Finanzas\DetalleFactura;
use App\Models\Finanzas\SerieFacturacion;
use App\Models\Finanzas\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacturaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $facturas = Factura::with(['cliente', 'serie'])
            ->where('id_empresa', $idEmpresa)
            ->orderBy('fecha_emision', 'desc')
            ->get()
            ->map(fn($f) => [
                'id_factura'        => $f->id_factura,
                'numero_completo'   => $f->numero_completo,
                'tipo'              => $f->tipo,
                'cliente'           => $f->cliente?->razon_social ?? '—',
                'id_cliente'        => $f->id_cliente,
                'fecha_emision'     => $f->fecha_emision?->format('d/m/Y'),
                'fecha_vencimiento' => $f->fecha_vencimiento?->format('d/m/Y'),
                'moneda'            => $f->moneda,
                'total'             => number_format($f->total, 2),
                'total_pagado'      => number_format($f->total_pagado, 2),
                'saldo_pendiente'   => number_format($f->saldo_pendiente, 2),
                'estado'            => $f->estado,
                'antiguedad'        => $f->antiguedad,
            ]);

        return response()->json(['success' => true, 'data' => $facturas]);
    }
    
    public function catalogos(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $clientes = \App\Models\Clientes\Cliente::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('razon_social')
            ->get(['id_cliente as id', 'razon_social as name',
                'moneda_facturacion', 'dias_credito', 'nit']);

        $series = SerieFacturacion::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('tipo')
            ->get()
            ->map(fn($s) => [
                'id'           => $s->id_serie,
                'name'         => $s->codigo_serie . ' — ' . $s->tipo,
                'tipo'         => $s->tipo,
                'codigo_serie' => $s->codigo_serie,
            ]);

        $tiposServicio = DB::table('tipo_servicio as ts')
            ->join('linea_negocio as ln', 'ts.id_linea', '=', 'ln.id_linea')
            ->where('ts.activo', true)
            ->orderBy('ln.nombre')
            ->orderBy('ts.nombre')
            ->get([
                'ts.id_tipo_servicio as id',
                'ts.nombre as name',
                'ts.precio_base',
                'ts.unidad_medida',
                'ts.moneda',
                'ln.nombre as linea',
            ]);

        // Contratos vigentes para vincular a facturas
        $contratos = DB::table('contrato_servicio as cs')
            ->join('cliente as c', 'cs.id_cliente', '=', 'c.id_cliente')
            ->where('cs.id_empresa', $idEmpresa)
            ->whereIn('cs.estado', ['VIGENTE', 'BORRADOR'])
            ->orderBy('cs.numero_contrato')
            ->get([
                'cs.id_contrato as id',
                'cs.numero_contrato',
                'cs.nombre_proyecto',
                'cs.id_cliente',
                'cs.moneda',
                'cs.valor_mensual',
                'cs.periodicidad_factura',
                'c.razon_social as cliente',
            ])
            ->map(fn($c) => [
                'id'                 => $c->id,
                'id_cliente'         => $c->id_cliente,
                'numero_contrato'    => $c->numero_contrato,
                'nombre_proyecto'    => $c->nombre_proyecto,
                'moneda'             => $c->moneda,
                'valor_mensual'      => $c->valor_mensual,
                'periodicidad'       => $c->periodicidad_factura,
                'name'               => $c->numero_contrato . ' — ' . ($c->nombre_proyecto ?? $c->cliente),
            ]);

        $monedas = ['GTQ', 'USD', 'EUR', 'HNL', 'NIO', 'CRC'];
        $tipos   = ['FACTURA', 'CREDITO_FISCAL', 'NOTA_CREDITO', 'NOTA_DEBITO'];
        $estados = ['BORRADOR', 'EMITIDA', 'ENVIADA', 'PARCIAL', 'PAGADA', 'VENCIDA', 'ANULADA'];

        return response()->json([
            'success' => true,
            'data'    => compact('clientes', 'series', 'tiposServicio',
                                'contratos', 'monedas', 'tipos', 'estados'),
        ]);
    }

    /**
     * Carga las líneas de un contrato para pre-llenar la factura.
     * GET /api/v1/finanzas/facturas/contrato/{id}/lineas
     */
    public function lineasContrato(Request $request, int $idContrato): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        // Verificar que el contrato pertenece a la empresa
        $contrato = DB::table('contrato_servicio')
            ->where('id_empresa', $idEmpresa)
            ->where('id_contrato', $idContrato)
            ->first();

        if (!$contrato) {
            return response()->json(['success' => false, 'message' => 'Contrato no encontrado.'], 404);
        }

        $lineas = DB::table('contrato_servicio_detalle as csd')
            ->leftJoin('tipo_servicio as ts', 'csd.id_tipo_servicio', '=', 'ts.id_tipo_servicio')
            ->where('csd.id_contrato', $idContrato)
            ->get([
                'csd.id_tipo_servicio',
                'ts.nombre as servicio_nombre',
                'csd.descripcion',
                'csd.cantidad',
                'csd.precio_unitario',
                'csd.descuento_pct',
                'csd.subtotal',
            ])
            ->map(fn($l) => [
                'id_tipo_servicio' => $l->id_tipo_servicio,
                'descripcion'      => $l->descripcion ?? $l->servicio_nombre ?? '',
                'cantidad'         => (float) $l->cantidad,
                'precio_unitario'  => (float) $l->precio_unitario,
                // Convertir descuento % a monto
                'descuento'        => round(
                    ($l->cantidad * $l->precio_unitario) * ($l->descuento_pct / 100), 4
                ),
                'es_afecto_iva'    => true,
                'subtotal'         => (float) $l->subtotal,
            ]);

        return response()->json(['success' => true, 'data' => $lineas]);
}

    public function show(Request $request, int $id): JsonResponse
    {
        $factura = Factura::with(['cliente', 'serie', 'detalles.tipoServicio', 'pagos'])
            ->where('id_empresa', $request->user()->id_empresa)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id_factura'              => $factura->id_factura,
                'numero_completo'         => $factura->numero_completo,
                'tipo'                    => $factura->tipo,
                'id_cliente'              => $factura->id_cliente,
                'cliente'                 => $factura->cliente?->razon_social,
                'nit_cliente'             => $factura->cliente?->nit,
                'id_serie'                => $factura->id_serie,
                'fecha_emision'           => $factura->fecha_emision?->format('Y-m-d'),
                'fecha_vencimiento'       => $factura->fecha_vencimiento?->format('Y-m-d'),
                'periodo_servicio_inicio' => $factura->periodo_servicio_inicio?->format('Y-m-d'),
                'periodo_servicio_fin'    => $factura->periodo_servicio_fin?->format('Y-m-d'),
                'moneda'                  => $factura->moneda,
                'descuento'               => $factura->descuento,
                'subtotal'                => $factura->subtotal,
                'base_imponible'          => $factura->base_imponible,
                'iva'                     => $factura->iva,
                'total'                   => $factura->total,
                'total_pagado'            => $factura->total_pagado,
                'saldo_pendiente'         => $factura->saldo_pendiente,
                'estado'                  => $factura->estado,
                'notas'                   => $factura->notas,
                'detalles'                => $factura->detalles->map(fn($d) => [
                    'id_linea'         => $d->id_linea,
                    'id_tipo_servicio' => $d->id_tipo_servicio,
                    'descripcion'      => $d->descripcion,
                    'cantidad'         => $d->cantidad,
                    'precio_unitario'  => $d->precio_unitario,
                    'descuento'        => $d->descuento,
                    'subtotal'         => $d->subtotal,
                    'es_afecto_iva'    => $d->es_afecto_iva,
                ]),
                'pagos' => $factura->pagos->map(fn($p) => [
                    'id_pago'    => $p->id_pago,
                    'referencia' => $p->referencia,
                    'forma_pago' => $p->forma_pago,
                    'monto'      => $p->monto,
                    'fecha_pago' => $p->fecha_pago?->format('d/m/Y'),
                ]),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'id_cliente'              => 'required|exists:cliente,id_cliente',
            'id_serie'                => 'required|exists:serie_facturacion,id_serie',
            'tipo'                    => 'required|in:FACTURA,CREDITO_FISCAL,NOTA_CREDITO,NOTA_DEBITO',
            'fecha_emision'           => 'required|date',
            'fecha_vencimiento'       => 'required|date|after_or_equal:fecha_emision',
            'periodo_servicio_inicio' => 'nullable|date',
            'periodo_servicio_fin'    => 'nullable|date',
            'moneda'                  => 'required|string|size:3',
            'descuento'               => 'nullable|numeric|min:0',
            'notas'                   => 'nullable|string',
            'detalles'                           => 'required|array|min:1',
            'detalles.*.descripcion'             => 'required|string|max:300',
            'detalles.*.cantidad'                => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario'         => 'required|numeric|min:0',
            'detalles.*.descuento'               => 'nullable|numeric|min:0',
            'detalles.*.es_afecto_iva'           => 'boolean',
            'detalles.*.id_tipo_servicio'        => 'nullable|exists:tipo_servicio,id_tipo_servicio',
        ]);

        // Validar que cliente y serie pertenezcan a la empresa
        $cliente = Cliente::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->findOrFail($request->id_cliente);

        $serie = SerieFacturacion::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->findOrFail($request->id_serie);

        return DB::transaction(function () use ($request, $idEmpresa, $cliente, $serie) {
            // Generar número correlativo usando los métodos del modelo
            $numero         = $serie->siguienteNumero();
            $numeroCompleto = $serie->formatearNumero($numero);

            // Crear cabecera
            $factura = Factura::create([
                'id_empresa'              => $idEmpresa,
                'id_cliente'              => $request->id_cliente,
                'id_serie'                => $serie->id_serie,
                'numero_factura'          => $numero,
                'numero_completo'         => $numeroCompleto,
                'tipo'                    => $request->tipo,
                'fecha_emision'           => $request->fecha_emision,
                'fecha_vencimiento'       => $request->fecha_vencimiento,
                'periodo_servicio_inicio' => $request->periodo_servicio_inicio,
                'periodo_servicio_fin'    => $request->periodo_servicio_fin,
                'moneda'                  => $request->moneda,
                'descuento'               => $request->descuento ?? 0,
                'subtotal'                => 0,
                'base_imponible'          => 0,
                'iva'                     => 0,
                'total'                   => 0,
                'total_pagado'            => 0,
                'saldo_pendiente'         => 0,
                'estado'                  => 'BORRADOR',
                'notas'                   => $request->notas,
                'created_by'              => $request->user()->id_usuario,
            ]);

            // Crear líneas
            foreach ($request->detalles as $d) {
                $cantidad  = $d['cantidad'];
                $precio    = $d['precio_unitario'];
                $descLinea = $d['descuento'] ?? 0;
                $subtotal  = round(($cantidad * $precio) - $descLinea, 4);

                DetalleFactura::create([
                    'id_factura'       => $factura->id_factura,
                    'id_tipo_servicio' => $d['id_tipo_servicio'] ?? null,
                    'descripcion'      => $d['descripcion'],
                    'cantidad'         => $cantidad,
                    'precio_unitario'  => $precio,
                    'descuento'        => $descLinea,
                    'subtotal'         => $subtotal,
                    'es_afecto_iva'    => $d['es_afecto_iva'] ?? true,
                ]);
            }

            // Recalcular totales usando el método del modelo
            $this->recalcularTotales($factura);

            return response()->json([
                'success' => true,
                'message' => 'Factura creada correctamente.',
                'data'    => [
                    'id_factura'      => $factura->id_factura,
                    'numero_completo' => $factura->numero_completo,
                ],
            ], 201);
        });
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $factura   = Factura::where('id_empresa', $idEmpresa)->findOrFail($id);

        if ($factura->estado !== 'BORRADOR') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden editar facturas en BORRADOR.',
            ], 422);
        }

        $request->validate([
            'id_cliente'              => 'required|exists:cliente,id_cliente',
            'fecha_emision'           => 'required|date',
            'fecha_vencimiento'       => 'required|date',
            'periodo_servicio_inicio' => 'nullable|date',
            'periodo_servicio_fin'    => 'nullable|date',
            'moneda'                  => 'required|string|size:3',
            'descuento'               => 'nullable|numeric|min:0',
            'notas'                   => 'nullable|string',
            'detalles'                           => 'required|array|min:1',
            'detalles.*.descripcion'             => 'required|string|max:300',
            'detalles.*.cantidad'                => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario'         => 'required|numeric|min:0',
            'detalles.*.descuento'               => 'nullable|numeric|min:0',
            'detalles.*.es_afecto_iva'           => 'boolean',
            'detalles.*.id_tipo_servicio'        => 'nullable|exists:tipo_servicio,id_tipo_servicio',
        ]);

        return DB::transaction(function () use ($request, $factura) {
            $factura->update([
                'id_cliente'              => $request->id_cliente,
                'fecha_emision'           => $request->fecha_emision,
                'fecha_vencimiento'       => $request->fecha_vencimiento,
                'periodo_servicio_inicio' => $request->periodo_servicio_inicio,
                'periodo_servicio_fin'    => $request->periodo_servicio_fin,
                'moneda'                  => $request->moneda,
                'descuento'               => $request->descuento ?? 0,
                'notas'                   => $request->notas,
            ]);

            // Reemplazar detalles
            $factura->detalles()->delete();

            foreach ($request->detalles as $d) {
                $subtotal = round(
                    ($d['cantidad'] * $d['precio_unitario']) - ($d['descuento'] ?? 0),
                    4
                );

                DetalleFactura::create([
                    'id_factura'       => $factura->id_factura,
                    'id_tipo_servicio' => $d['id_tipo_servicio'] ?? null,
                    'descripcion'      => $d['descripcion'],
                    'cantidad'         => $d['cantidad'],
                    'precio_unitario'  => $d['precio_unitario'],
                    'descuento'        => $d['descuento'] ?? 0,
                    'subtotal'         => $subtotal,
                    'es_afecto_iva'    => $d['es_afecto_iva'] ?? true,
                ]);
            }

            $this->recalcularTotales($factura);

            return response()->json([
                'success' => true,
                'message' => 'Factura actualizada correctamente.',
            ]);
        });
    }

    public function emitir(Request $request, int $id): JsonResponse
    {
        $factura = Factura::where('id_empresa', $request->user()->id_empresa)
                          ->findOrFail($id);

        if ($factura->estado !== 'BORRADOR') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden emitir facturas en BORRADOR.',
            ], 422);
        }

        $factura->update(['estado' => 'EMITIDA']);

        return response()->json([
            'success' => true,
            'message' => 'Factura emitida correctamente.',
        ]);
    }

    public function anular(Request $request, int $id): JsonResponse
    {
        $factura = Factura::where('id_empresa', $request->user()->id_empresa)
                          ->findOrFail($id);

        if (in_array($factura->estado, ['ANULADA', 'PAGADA'])) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede anular una factura en estado ' . $factura->estado . '.',
            ], 422);
        }

        // Usar el método del modelo Factura
        $factura->anular($request->user()->id_usuario);

        return response()->json([
            'success' => true,
            'message' => 'Factura anulada correctamente.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $factura = Factura::where('id_empresa', $request->user()->id_empresa)
                          ->findOrFail($id);

        if ($factura->estado !== 'BORRADOR') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden eliminar facturas en BORRADOR.',
            ], 422);
        }

        $factura->detalles()->delete();
        $factura->delete();

        return response()->json([
            'success' => true,
            'message' => 'Factura eliminada correctamente.',
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // HELPER PRIVADO
    // ════════════════════════════════════════════════════════════
    private function recalcularTotales(Factura $factura): void
    {
        $factura->refresh();
        $subtotal      = $factura->detalles()->sum('subtotal');
        $descuento     = $factura->descuento ?? 0;
        $baseImponible = $subtotal - $descuento;
        $iva           = round($baseImponible * 0.12, 4);
        $total         = $baseImponible + $iva;

        $factura->update([
            'subtotal'        => $subtotal,
            'base_imponible'  => $baseImponible,
            'iva'             => $iva,
            'total'           => $total,
            'saldo_pendiente' => $total - $factura->total_pagado,
        ]);
    }
}