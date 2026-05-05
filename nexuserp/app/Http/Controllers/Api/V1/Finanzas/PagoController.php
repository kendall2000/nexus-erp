<?php
// app/Http/Controllers/Api/V1/Finanzas/PagoController.php

namespace App\Http\Controllers\Api\V1\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\Finanzas\Factura;
use App\Models\Finanzas\Pago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    /**
     * Listado con filtros opcionales.
     * GET /api/v1/finanzas/pagos
     */
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $pagos = Pago::with([
                'factura:id_factura,numero_completo,estado',
                'cliente:id_cliente,razon_social',
                // 'creadoPor' eliminado temporalmente — columna 'nombre' no existe
            ])
            ->porEmpresa($idEmpresa)
            ->when($request->id_factura,  fn($q, $v) => $q->where('id_factura', $v))
            ->when($request->id_cliente,  fn($q, $v) => $q->porCliente($v))
            ->when($request->forma_pago,  fn($q, $v) => $q->porFormaPago($v))
            ->when(
                $request->fecha_desde && $request->fecha_hasta,
                fn($q) => $q->enPeriodo($request->fecha_desde, $request->fecha_hasta)
            )
            ->orderBy('fecha_pago', 'desc')
            ->get()
            ->map(fn($p) => [
                'id_pago'          => $p->id_pago,
                'factura'          => $p->factura?->numero_completo ?? '—',
                'factura_estado'   => $p->factura?->estado          ?? '—',
                'cliente'          => $p->cliente?->razon_social    ?? '—',
                'referencia'       => $p->referencia                ?? '—',
                'forma_pago'       => $p->forma_pago,
                'banco_origen'     => $p->banco_origen              ?? '—',
                'monto'            => number_format($p->monto, 2),
                'moneda'           => $p->moneda,
                'fecha_pago'       => $p->fecha_pago?->format('d/m/Y'),
                'fecha_acreditado' => $p->fecha_acreditado?->format('d/m/Y') ?? '—',
                'notas'            => $p->notas,
                'creado_por'       => '—', // se llenará una vez sepamos el nombre real
            ]);

        return response()->json(['success' => true, 'data' => $pagos]);
    }


    /**
     * Catálogos para el formulario.
     * GET /api/v1/finanzas/pagos/catalogos
     */
    public function catalogos(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        // Facturas con saldo pendiente — estados correctos del enum
        $facturasPendientes = Factura::where('id_empresa', $idEmpresa)
            ->whereIn('estado', ['EMITIDA', 'ENVIADA', 'PARCIAL', 'VENCIDA'])
            ->where('saldo_pendiente', '>', 0)
            ->with('cliente:id_cliente,razon_social')
            ->orderBy('fecha_vencimiento')
            ->get(['id_factura', 'numero_completo', 'id_cliente',
                'saldo_pendiente', 'moneda', 'fecha_vencimiento'])
            ->map(fn($f) => [
                'id_factura'        => $f->id_factura,
                'id_cliente'        => $f->id_cliente,
                'label'             => "{$f->numero_completo} — {$f->cliente?->razon_social} | Saldo: {$f->moneda} " . number_format($f->saldo_pendiente, 2),
                'saldo_pendiente'   => $f->saldo_pendiente,
                'moneda'            => $f->moneda,
                'fecha_vencimiento' => $f->fecha_vencimiento?->format('d/m/Y'),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'facturas'    => $facturasPendientes,
                'formas_pago' => ['EFECTIVO', 'TRANSFERENCIA', 'CHEQUE', 'TARJETA', 'DEPOSITO'],
                'monedas'     => ['GTQ', 'USD'],
            ],
        ]);
    }

    /**
     * Detalle de un pago.
     * GET /api/v1/finanzas/pagos/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $pago = Pago::with(['factura', 'cliente', 'creadoPor'])
            ->porEmpresa($request->user()->id_empresa)
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $pago]);
    }

    /**
     * Registrar un nuevo pago.
     * POST /api/v1/finanzas/pagos
     */
    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'id_factura'       => 'required|integer',
            'forma_pago'       => 'required|in:EFECTIVO,TRANSFERENCIA,CHEQUE,TARJETA,DEPOSITO',
            'monto'            => 'required|numeric|min:0.01',
            'moneda'           => 'required|string|size:3',
            'fecha_pago'       => 'required|date',
            'fecha_acreditado' => 'nullable|date|after_or_equal:fecha_pago',
            'referencia'       => 'nullable|string|max:100',
            'banco_origen'     => 'nullable|string|max:100',
            'notas'            => 'nullable|string|max:500',
        ]);

        // ── CORRECCIÓN: estados correctos del enum ────────────────
        $factura = Factura::where('id_empresa', $idEmpresa)
            ->whereIn('estado', ['EMITIDA', 'ENVIADA', 'PARCIAL', 'VENCIDA'])
            ->where('saldo_pendiente', '>', 0)
            ->findOrFail($request->id_factura);

        // Regla de negocio: no pagar más del saldo pendiente
        if ((float) $request->monto > (float) $factura->saldo_pendiente) {
            return response()->json([
                'success' => false,
                'message' => "El monto ingresado ({$factura->moneda} " . number_format($request->monto, 2) . ") supera el saldo pendiente ({$factura->moneda} " . number_format($factura->saldo_pendiente, 2) . ").",
                'errors'  => ['monto' => ["No puede superar el saldo pendiente de {$factura->moneda} " . number_format($factura->saldo_pendiente, 2)]],
            ], 422);
        }

        return DB::transaction(function () use ($request, $idEmpresa, $factura) {
            $pago = Pago::create([
                'id_empresa'       => $idEmpresa,
                'id_factura'       => $factura->id_factura,
                'id_cliente'       => $factura->id_cliente,
                'referencia'       => $request->referencia,
                'forma_pago'       => $request->forma_pago,
                'banco_origen'     => $request->banco_origen,
                'monto'            => $request->monto,
                'moneda'           => $request->moneda,
                'fecha_pago'       => $request->fecha_pago,
                'fecha_acreditado' => $request->fecha_acreditado,
                'notas'            => $request->notas,
                'created_by'       => $request->user()->id_usuario,
            ]);

            $factura->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado correctamente.',
                'data'    => [
                    'id_pago'         => $pago->id_pago,
                    'factura_estado'  => $factura->estado,
                    'saldo_pendiente' => number_format($factura->saldo_pendiente, 2),
                ],
            ], 201);
        });
    }

    /**
     * Revertir / Eliminar un pago.
     * DELETE /api/v1/finanzas/pagos/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $pago = Pago::porEmpresa($request->user()->id_empresa)->findOrFail($id);

        return DB::transaction(function () use ($pago) {
            $factura      = $pago->factura;
            $nuevoSaldo   = (float) $factura->saldo_pendiente + (float) $pago->monto;
            $totalFactura = (float) $factura->total;
            $nuevoPagado  = (float) $factura->total_pagado - (float) $pago->monto;

            // ── CORRECCIÓN: estado EMITIDA en vez de PENDIENTE ──
            $nuevoEstado = match (true) {
                $nuevoPagado <= 0           => 'EMITIDA',
                $nuevoSaldo  <= 0           => 'PAGADA',
                default                     => 'PARCIAL',
            };

            $factura->update([
                'total_pagado'    => max(0, $nuevoPagado),
                'saldo_pendiente' => $nuevoSaldo,
                'estado'          => $nuevoEstado,
            ]);

            $pago->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pago revertido correctamente. El saldo de la factura ha sido restaurado.',
            ]);
        });
    }
}