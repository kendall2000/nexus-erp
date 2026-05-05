<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\OrdenCompra;
use App\Models\Inventario\DetalleOrdenCompra;
use App\Models\Inventario\Proveedor;
use App\Models\Inventario\Bodega;
use App\Models\Inventario\Producto;
use App\Models\Core\Empresa;
use App\Models\Finanzas\PresupuestoAnual;
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
        $empresa   = Empresa::findOrFail($idEmpresa);

        $proveedores = Proveedor::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('razon_social')
            ->get(['id_proveedor as id', 'razon_social as name', 'moneda_pago']);

        $bodegas = Bodega::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_bodega as id', 'nombre as name']);

        // Productos ahora con sus defaults de centro/cuenta
        $productos = Producto::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_producto as id', 'codigo', 'nombre as name',
                   'precio_compra', 'unidad_medida',
                   'id_cuenta_gasto', 'id_centro_default']);

        // Centros de costo activos
        $centros = DB::table('centro_costo')
            ->where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->orderBy('codigo')
            ->get(['id_centro as id', DB::raw("CONCAT(codigo, ' — ', nombre) as name")]);

        // Cuentas de GASTO/COSTO que permitan movimiento (no INGRESO como en facturas)
        $cuentas = DB::table('cuenta_contable')
            ->where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->where('permite_movimiento', true)
            ->whereIn('tipo', ['GASTO', 'COSTO'])
            ->orderBy('codigo')
            ->get(['id_cuenta as id', DB::raw("CONCAT(codigo, ' — ', nombre) as name"), 'tipo']);

        return response()->json([
            'success' => true,
            'data'    => [
                'proveedores' => $proveedores,
                'bodegas'     => $bodegas,
                'productos'   => $productos,
                'centros'     => $centros,
                'cuentas'     => $cuentas,
                'monedas'     => ['GTQ', 'USD', 'EUR', 'MXN', 'HNL', 'NIO', 'CRC'],
                'estados'     => ['BORRADOR', 'ENVIADA', 'PARCIAL', 'RECIBIDA', 'CANCELADA'],
                'config_fiscal' => [
                    'tasa_iva'               => (float) $empresa->tasa_iva,
                    'tasa_iva_decimal'       => $empresa->tasa_iva_decimal,
                    'iva_incluido_en_precio' => (bool) $empresa->iva_incluido_en_precio,
                ],
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
                    'id_centro'         => $d->id_centro,
                    'id_cuenta'         => $d->id_cuenta,
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
            'numero_oc' => [
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
            'detalles.*.id_centro'           => 'nullable|exists:centro_costo,id_centro',
            'detalles.*.id_cuenta'           => 'nullable|exists:cuenta_contable,id_cuenta',
            'detalles.*.descripcion'         => 'nullable|string|max:300',
            'detalles.*.cantidad_pedida'     => 'required|numeric|min:0.0001',
            'detalles.*.precio_unitario'     => 'required|numeric|min:0',
            'detalles.*.descuento'           => 'nullable|numeric|min:0',
        ]);

        $this->validarPertenenciaEmpresa($request, $idEmpresa);

        return DB::transaction(function () use ($request, $idEmpresa) {
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
                'subtotal'               => 0, 'iva' => 0, 'total' => 0,
                'created_by'             => $request->user()->id_usuario,
            ]);

            foreach ($request->detalles as $d) {
                $cantidad  = $d['cantidad_pedida'];
                $precio    = $d['precio_unitario'];
                $descuento = $d['descuento'] ?? 0;
                $subtotal  = round(($cantidad * $precio) - $descuento, 4);

                DetalleOrdenCompra::create([
                    'id_oc'             => $oc->id_oc,
                    'id_producto'       => $d['id_producto'],
                    'id_centro'         => $d['id_centro'] ?? null,
                    'id_cuenta'         => $d['id_cuenta'] ?? null,
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
                'message' => 'Orden de compra creada correctamente.',
                'data'    => ['id_oc' => $oc->id_oc],
            ], 201);
        });
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $oc = OrdenCompra::where('id_empresa', $idEmpresa)->findOrFail($id);

        if (!in_array($oc->estado, ['BORRADOR'])) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden editar órdenes en BORRADOR. Una vez aprobada no se puede modificar.',
            ], 422);
        }

        $request->validate([
            'numero_oc' => [
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
            'estado'                 => 'required|in:BORRADOR',
            'notas'                  => 'nullable|string',
            'detalles'                       => 'required|array|min:1',
            'detalles.*.id_producto'         => 'required|exists:producto,id_producto',
            'detalles.*.id_centro'           => 'nullable|exists:centro_costo,id_centro',
            'detalles.*.id_cuenta'           => 'nullable|exists:cuenta_contable,id_cuenta',
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
                'notas'                  => $request->notas,
            ]);

            $oc->detalles()->delete();

            foreach ($request->detalles as $d) {
                $cantidad  = $d['cantidad_pedida'];
                $precio    = $d['precio_unitario'];
                $descuento = $d['descuento'] ?? 0;
                $subtotal  = round(($cantidad * $precio) - $descuento, 4);

                DetalleOrdenCompra::create([
                    'id_oc'             => $oc->id_oc,
                    'id_producto'       => $d['id_producto'],
                    'id_centro'         => $d['id_centro'] ?? null,
                    'id_cuenta'         => $d['id_cuenta'] ?? null,
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

    /**
     * Aprobar OC (BORRADOR → ENVIADA).
     * Aquí dispara el hook que actualiza presupuesto.
     * Antes de aprobar, valida saldo presupuestal disponible.
     */
    public function aprobar(Request $request, int $id): JsonResponse
    {
        $oc = OrdenCompra::with('detalles.producto')
            ->where('id_empresa', $request->user()->id_empresa)
            ->findOrFail($id);

        if ($oc->estado !== 'BORRADOR') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden aprobar órdenes en BORRADOR.',
            ], 422);
        }

        // ── Validar saldo presupuestal antes de aprobar ──
        $validacion = $this->validarSaldoPresupuestal($oc, (bool) $request->input('forzar', false));
        if (!$validacion['ok']) {
            return response()->json([
                'success'         => false,
                'message'         => $validacion['mensaje'],
                'requiere_forzar' => true,
                'detalles'        => $validacion['detalles'],
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
    // HELPERS PRIVADOS
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

    /**
     * Valida que cada línea tenga saldo presupuestal disponible.
     * Si todo OK retorna ['ok' => true]. Si hay sobregiro, retorna detalles.
     */
    private function validarSaldoPresupuestal(OrdenCompra $oc, bool $forzar): array
    {
        $empresa     = Empresa::find($oc->id_empresa);
        $tasaIva     = $empresa ? $empresa->tasa_iva_decimal : 0.12;
        $ivaIncluido = $empresa ? (bool) $empresa->iva_incluido_en_precio : false;
        $anio        = $oc->fecha_emision->year;
        $mes         = $oc->fecha_emision->month;
        $nombreMes   = PresupuestoAnual::MESES[$mes] ?? null;

        $sobregiros = [];

        // Agrupar montos por (centro + cuenta)
        $agregados = [];
        foreach ($oc->detalles as $linea) {
            $idCentro = $linea->centro_efectivo;
            $idCuenta = $linea->cuenta_efectiva;
            if (!$idCentro || !$idCuenta) continue;

            $subtotal  = (float) $linea->subtotal;
            $montoNeto = $ivaIncluido ? round($subtotal / (1 + $tasaIva), 4) : $subtotal;

            $key = "{$idCentro}-{$idCuenta}";
            $agregados[$key] = ($agregados[$key] ?? 0) + $montoNeto;
        }

        foreach ($agregados as $key => $montoOC) {
            [$idCentro, $idCuenta] = explode('-', $key);

            $presupuesto = PresupuestoAnual::where('id_empresa', $oc->id_empresa)
                ->where('id_centro', $idCentro)
                ->where('id_cuenta', $idCuenta)
                ->where('anio', $anio)
                ->where('estado', 'APROBADO')
                ->first();

            if (!$presupuesto) continue; // sin presupuesto = no se valida

            $disponible = (float) $presupuesto->saldo_disponible;

            if ($montoOC > $disponible) {
                $sobregiros[] = [
                    'centro'     => $idCentro,
                    'cuenta'     => $idCuenta,
                    'requerido'  => round($montoOC, 2),
                    'disponible' => round($disponible, 2),
                    'sobregiro'  => round($montoOC - $disponible, 2),
                ];
            }
        }

        if (empty($sobregiros) || $forzar) {
            return ['ok' => true];
        }

        return [
            'ok'       => false,
            'mensaje'  => 'La OC excede el saldo disponible en el presupuesto. ¿Deseas aprobar de todos modos?',
            'detalles' => $sobregiros,
        ];
    }
}