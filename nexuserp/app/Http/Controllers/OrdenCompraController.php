<?php

namespace App\Http\Controllers;

use App\Models\Core\CentroCosto;
use App\Models\Core\CuentaContable;
use App\Models\Core\Empresa;
use App\Models\Inventario\Bodega;
use App\Models\Inventario\DetalleOrdenCompra;
use App\Models\Inventario\OrdenCompra;
use App\Models\Inventario\Producto;
use App\Models\Inventario\Proveedor;
use App\Support\EjecucionPresupuesto;
use App\Support\ExportarCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Órdenes de compra. Permisos: ordenes_compra.ver / crear / editar / aprobar /
 * cancelar / imprimir / exportar.
 *
 * Flujo: BORRADOR → (aprobar) ENVIADA → (recepciones) PARCIAL → RECIBIDA;
 * BORRADOR o ENVIADA sin mercadería recibida → CANCELADA. Aprobar ejecuta el
 * presupuesto y cancelar lo revierte (App\Support\EjecucionPresupuesto).
 */
class OrdenCompraController extends Controller
{
    public const ESTADOS = [
        'BORRADOR' => ['Borrador', 'secondary'],
        'ENVIADA' => ['Aprobada / enviada', 'info'],
        'PARCIAL' => ['Recibida parcial', 'warning'],
        'RECIBIDA' => ['Recibida', 'success'],
        'CANCELADA' => ['Cancelada', 'danger'],
    ];

    public function index(Request $request): View
    {
        return view('ordenes-compra.index', [
            'ordenes' => $this->filtradas($request)->with(['proveedor', 'bodega'])
                ->withSum('detalles as pedido', 'cantidad_pedida')->withSum('detalles as recibido', 'cantidad_recibida')
                ->orderByDesc('fecha_emision')->orderByDesc('id_oc')->paginate(25)->withQueryString(),
            'proveedores' => Proveedor::query()->where('id_empresa', $request->user()->id_empresa)->orderBy('razon_social')->get(['id_proveedor', 'razon_social']),
            'estados' => self::ESTADOS,
            'filtros' => $request->only(['buscar', 'estado', 'proveedor']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $ordenes = $this->filtradas($request)->with(['proveedor', 'bodega'])->orderByDesc('fecha_emision')->cursor();

        return ExportarCsv::descargar('ordenes_compra', ['Número', 'Proveedor', 'Bodega', 'Emisión', 'Entrega esperada', 'Moneda', 'Subtotal', 'IVA', 'Total', 'Estado'],
            (function () use ($ordenes) {
                foreach ($ordenes as $oc) {
                    yield [$oc->numero_oc, $oc->proveedor?->razon_social, $oc->bodega?->nombre, $oc->fecha_emision?->format('d/m/Y'),
                        $oc->fecha_entrega_esperada?->format('d/m/Y'), $oc->moneda, $oc->subtotal, $oc->iva, $oc->total, self::ESTADOS[$oc->estado][0] ?? $oc->estado];
                }
            })());
    }

    public function show(Request $request, int $orden): View
    {
        return view('ordenes-compra.show', [
            'oc' => $this->cargar($request, $orden),
            'estados' => self::ESTADOS,
            'sobregiros' => session('sobregiros', []),
        ]);
    }

    public function imprimir(Request $request, int $orden): View
    {
        return view('ordenes-compra.imprimir', [
            'oc' => $this->cargar($request, $orden),
            'empresa' => Empresa::find($request->user()->id_empresa),
            'estados' => self::ESTADOS,
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new OrdenCompra([
            'fecha_emision' => now(), 'moneda' => 'GTQ', 'estado' => 'BORRADOR',
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        $oc = DB::transaction(function () use ($request, $datos) {
            $encabezado = $datos['encabezado'];
            $encabezado['numero_oc'] ??= $this->siguienteNumero($request);
            $oc = OrdenCompra::create($encabezado + [
                'id_empresa' => $request->user()->id_empresa,
                'estado' => 'BORRADOR',
                'subtotal' => 0, 'iva' => 0, 'total' => 0,
                'created_by' => $request->user()->id_usuario,
            ]);
            $this->guardarLineas($oc, $datos['lineas']);

            return $oc;
        });

        return redirect()->route('ordenes-compra.show', $oc->id_oc)->with('status', "Orden {$oc->numero_oc} creada en borrador.");
    }

    public function edit(Request $request, int $orden): View|RedirectResponse
    {
        $oc = $this->cargar($request, $orden);
        if ($oc->estado !== 'BORRADOR') {
            return redirect()->route('ordenes-compra.show', $oc->id_oc)->withErrors(['orden' => 'Solo se pueden editar órdenes en borrador.']);
        }

        return $this->formulario($request, $oc);
    }

    public function update(Request $request, int $orden): RedirectResponse
    {
        $oc = $this->deMiEmpresa($request)->findOrFail($orden);
        if ($oc->estado !== 'BORRADOR') {
            return redirect()->route('ordenes-compra.show', $oc->id_oc)->withErrors(['orden' => 'Solo se pueden editar órdenes en borrador.']);
        }
        $datos = $this->validar($request, $oc);

        DB::transaction(function () use ($oc, $datos) {
            $encabezado = $datos['encabezado'];
            $encabezado['numero_oc'] ??= $oc->numero_oc;
            $oc->update($encabezado);
            $oc->detalles()->delete();
            $this->guardarLineas($oc, $datos['lineas']);
        });

        return redirect()->route('ordenes-compra.show', $oc->id_oc)->with('status', "Orden {$oc->numero_oc} actualizada.");
    }

    /** Aprueba (BORRADOR → ENVIADA) y ejecuta el presupuesto. Si se pasa del saldo, pide confirmación. */
    public function aprobar(Request $request, int $orden): RedirectResponse
    {
        return DB::transaction(function () use ($request, $orden) {
            $oc = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($orden);
            if ($oc->estado !== 'BORRADOR') {
                return back()->withErrors(['orden' => 'Solo se pueden aprobar órdenes en borrador.']);
            }

            $sobregiros = EjecucionPresupuesto::sobregiros($oc);
            if ($sobregiros && ! $request->boolean('forzar')) {
                return back()->with('sobregiros', array_map(fn ($s) => [
                    'partida' => $s['presupuesto']->centroCosto?->nombre.' / '.$s['presupuesto']->cuentaContable?->nombre,
                    'requerido' => $s['requerido'], 'disponible' => $s['disponible'], 'sobregiro' => $s['sobregiro'],
                ], $sobregiros));
            }

            $oc->update(['estado' => 'ENVIADA', 'aprobado_por' => $request->user()->id_usuario]);
            EjecucionPresupuesto::registrarOrden($oc);

            return back()->with('status', "Orden {$oc->numero_oc} aprobada".($sobregiros ? ' pasando el saldo del presupuesto.' : '.'));
        });
    }

    /** Cancela (BORRADOR o ENVIADA sin nada recibido) y revierte el presupuesto si ya se había aprobado. */
    public function cancelar(Request $request, int $orden): RedirectResponse
    {
        return DB::transaction(function () use ($request, $orden) {
            $oc = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($orden);
            if (! in_array($oc->estado, ['BORRADOR', 'ENVIADA'], true) || $oc->detalles()->where('cantidad_recibida', '>', 0)->exists()) {
                return back()->withErrors(['orden' => 'Solo se pueden cancelar órdenes en borrador o aprobadas sin mercadería recibida.']);
            }

            $estabaAprobada = $oc->estado === 'ENVIADA';
            $oc->update(['estado' => 'CANCELADA']);
            if ($estabaAprobada) {
                EjecucionPresupuesto::revertirOrden($oc);
            }

            return back()->with('status', "Orden {$oc->numero_oc} cancelada".($estabaAprobada ? ' y revertida del presupuesto.' : '.'));
        });
    }

    public function destroy(Request $request, int $orden): RedirectResponse
    {
        $oc = $this->deMiEmpresa($request)->findOrFail($orden);
        if ($oc->estado !== 'BORRADOR') {
            return back()->withErrors(['orden' => 'Solo se pueden eliminar órdenes en borrador. Las demás se cancelan.']);
        }
        DB::transaction(function () use ($oc) {
            $oc->detalles()->delete();
            $oc->delete();
        });

        return redirect()->route('ordenes-compra.index')->with('status', "Orden {$oc->numero_oc} eliminada.");
    }

    private function filtradas(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->where('numero_oc', 'like', "%{$buscar}%"))
            ->when(array_key_exists((string) $request->query('estado'), self::ESTADOS), fn ($q) => $q->where('estado', $request->query('estado')))
            ->when($request->filled('proveedor'), fn ($q) => $q->where('id_proveedor', $request->integer('proveedor')));
    }

    private function cargar(Request $request, int $orden): OrdenCompra
    {
        return $this->deMiEmpresa($request)
            ->with(['proveedor', 'bodega', 'creadoPor', 'aprobadoPor', 'detalles.producto.centroDefault', 'detalles.producto.cuentaGasto', 'detalles.centroCosto', 'detalles.cuentaContable'])
            ->findOrFail($orden);
    }

    private function formulario(Request $request, OrdenCompra $oc): View
    {
        $idEmpresa = $request->user()->id_empresa;
        $empresa = Empresa::find($idEmpresa);

        return view('ordenes-compra.form', [
            'oc' => $oc,
            'proveedores' => Proveedor::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('razon_social')->get(),
            'bodegas' => Bodega::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('nombre')->get(),
            'productos' => Producto::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('nombre')
                ->get(['id_producto', 'codigo', 'nombre', 'unidad_medida', 'precio_compra', 'id_cuenta_gasto', 'id_centro_default']),
            'centros' => CentroCosto::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('codigo')->get(),
            'cuentas' => CuentaContable::query()->where('id_empresa', $idEmpresa)->where('activo', true)->where('permite_movimiento', true)
                ->whereIn('tipo', ['GASTO', 'COSTO'])->orderBy('codigo')->get(),
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
            'fiscal' => ['tasa' => $empresa?->tasa_iva_decimal ?? 0.12, 'incluido' => (bool) ($empresa?->iva_incluido_en_precio)],
            'siguienteNumero' => $oc->exists ? null : $this->siguienteNumero($request),
        ]);
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return OrdenCompra::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** OC-AAAA-0001, consecutivo por empresa y año. */
    private function siguienteNumero(Request $request): string
    {
        $prefijo = 'OC-'.now()->year.'-';
        $ultimo = $this->deMiEmpresa($request)->where('numero_oc', 'like', $prefijo.'%')->pluck('numero_oc')
            ->map(fn ($n) => (int) substr($n, strlen($prefijo)))->max() ?? 0;

        return $prefijo.str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @return array{encabezado: array<string, mixed>, lineas: list<array<string, mixed>>} */
    private function validar(Request $request, ?OrdenCompra $oc = null): array
    {
        $idEmpresa = $request->user()->id_empresa;
        $request->merge(['numero_oc' => ($n = strtoupper(trim((string) $request->input('numero_oc')))) === '' ? null : $n]);
        // Se descartan las filas vacías del formulario.
        $request->merge(['lineas' => array_values(array_filter((array) $request->input('lineas', []), fn ($l) => ! empty($l['id_producto'])))]);

        $datos = $request->validate([
            'numero_oc' => ['nullable', 'string', 'max:30', Rule::unique('orden_compra', 'numero_oc')->where('id_empresa', $idEmpresa)->ignore($oc?->id_oc, 'id_oc')],
            'id_proveedor' => ['required', 'integer', Rule::exists('proveedor', 'id_proveedor')->where('id_empresa', $idEmpresa)->where('activo', true)->whereNull('deleted_at')],
            'id_bodega' => ['nullable', 'integer', Rule::exists('bodega', 'id_bodega')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'fecha_emision' => ['required', 'date'],
            'fecha_entrega_esperada' => ['nullable', 'date', 'after_or_equal:fecha_emision'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'notas' => ['nullable', 'string', 'max:2000'],
            'lineas' => ['required', 'array', 'min:1', 'max:200'],
            'lineas.*.id_producto' => ['required', 'integer', Rule::exists('producto', 'id_producto')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'lineas.*.descripcion' => ['nullable', 'string', 'max:300'],
            'lineas.*.cantidad_pedida' => ['required', 'numeric', 'min:0.0001', 'max:99999999'],
            'lineas.*.precio_unitario' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'lineas.*.descuento' => ['nullable', 'numeric', 'min:0'],
            'lineas.*.id_centro' => ['nullable', 'integer', Rule::exists('centro_costo', 'id_centro')->where('id_empresa', $idEmpresa)],
            'lineas.*.id_cuenta' => ['nullable', 'integer', Rule::exists('cuenta_contable', 'id_cuenta')->where('id_empresa', $idEmpresa)->where('permite_movimiento', true)],
        ], [
            'numero_oc.unique' => 'Ya existe una orden con ese número.',
            'id_proveedor.exists' => 'El proveedor no es válido o está inactivo.',
            'lineas.required' => 'Agrega al menos un producto.',
            'lineas.*.id_producto.exists' => 'Uno de los productos no es válido o está inactivo.',
            'lineas.*.cantidad_pedida.min' => 'La cantidad debe ser mayor que cero.',
        ]);

        $lineas = [];
        foreach ($datos['lineas'] as $i => $l) {
            $bruto = round((float) $l['cantidad_pedida'] * (float) $l['precio_unitario'], 4);
            $descuento = round((float) ($l['descuento'] ?? 0), 4);
            if ($descuento > $bruto) {
                throw ValidationException::withMessages(["lineas.{$i}.descuento" => 'El descuento de una línea no puede ser mayor que su importe.']);
            }
            $lineas[] = [
                'id_producto' => (int) $l['id_producto'],
                'descripcion' => $l['descripcion'] ?? null,
                'cantidad_pedida' => $l['cantidad_pedida'],
                'precio_unitario' => $l['precio_unitario'],
                'descuento' => $descuento,
                'subtotal' => round($bruto - $descuento, 4),
                'id_centro' => $l['id_centro'] ?? null,
                'id_cuenta' => $l['id_cuenta'] ?? null,
            ];
        }

        return [
            'encabezado' => collect($datos)->only(['numero_oc', 'id_proveedor', 'id_bodega', 'fecha_emision', 'fecha_entrega_esperada', 'moneda', 'notas'])
                ->all() + ['id_bodega' => null, 'fecha_entrega_esperada' => null, 'notas' => null],
            'lineas' => $lineas,
        ];
    }

    private function guardarLineas(OrdenCompra $oc, array $lineas): void
    {
        foreach ($lineas as $l) {
            DetalleOrdenCompra::create($l + ['id_oc' => $oc->id_oc, 'cantidad_recibida' => 0]);
        }
        $oc->recalcularTotales();
    }
}
