<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventario\GuardarOrdenCompraRequest;
use App\Models\Core\CentroCosto;
use App\Models\Core\CuentaContable;
use App\Models\Core\Empresa;
use App\Models\Inventario\Bodega;
use App\Models\Inventario\DetalleOrdenCompra;
use App\Models\Inventario\OrdenCompra;
use App\Models\Inventario\Producto;
use App\Models\Inventario\Proveedor;
use App\Services\Finanzas\PresupuestoService;
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
            'proveedores' => Proveedor::query()->where('id_empresa', $this->idEmpresa($request))->orderBy('razon_social')->get(['id_proveedor', 'razon_social']),
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
        $oc = $this->cargar($request, $orden);
        $oc->load(['recepciones' => fn ($q) => $q->orderBy('fecha_recepcion')->orderBy('id_recepcion')]);

        return view('ordenes-compra.show', [
            'oc' => $oc,
            'estados' => self::ESTADOS,
            'sobregiros' => session('sobregiros', []),
        ]);
    }

    public function imprimir(Request $request, int $orden): View
    {
        return view('ordenes-compra.imprimir', [
            'oc' => $this->cargar($request, $orden),
            'empresa' => Empresa::find($this->idEmpresa($request)),
            'estados' => self::ESTADOS,
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new OrdenCompra([
            'fecha_emision' => now(), 'moneda' => 'GTQ', 'estado' => 'BORRADOR',
        ]));
    }

    public function store(GuardarOrdenCompraRequest $request): RedirectResponse
    {
        $datos = $request->datosLimpios();

        $oc = DB::transaction(function () use ($request, $datos) {
            $encabezado = $datos['encabezado'];
            $encabezado['numero_oc'] ??= $this->siguienteNumero($request);
            $oc = OrdenCompra::create($encabezado + [
                'id_empresa' => $this->idEmpresa($request),
                'estado' => 'BORRADOR',
                'subtotal' => 0, 'iva' => 0, 'total' => 0,
                'created_by' => $this->idUsuario($request),
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

    public function update(GuardarOrdenCompraRequest $request, int $orden): RedirectResponse
    {
        $oc = $this->deMiEmpresa($request)->findOrFail($orden);
        if ($oc->estado !== 'BORRADOR') {
            return redirect()->route('ordenes-compra.show', $oc->id_oc)->withErrors(['orden' => 'Solo se pueden editar órdenes en borrador.']);
        }
        $datos = $request->datosLimpios();

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
    public function aprobar(Request $request, int $orden, PresupuestoService $presupuestoService): RedirectResponse
    {
        return DB::transaction(function () use ($request, $orden, $presupuestoService) {
            $oc = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($orden);
            if ($oc->estado !== 'BORRADOR') {
                return back()->withErrors(['orden' => 'Solo se pueden aprobar órdenes en borrador.']);
            }

            $sobregiros = $presupuestoService->validarSobregiros($oc);
            if ($sobregiros && ! $request->boolean('forzar')) {
                return back()->with('sobregiros', array_map(fn ($s) => [
                    'partida' => $s['presupuesto']->centroCosto?->nombre.' / '.$s['presupuesto']->cuentaContable?->nombre,
                    'requerido' => $s['requerido'], 'disponible' => $s['disponible'], 'sobregiro' => $s['sobregiro'],
                ], $sobregiros));
            }

            $oc->update(['estado' => 'ENVIADA', 'aprobado_por' => $this->idUsuario($request)]);
            $presupuestoService->registrarOrden($oc);

            return back()->with('status', "Orden {$oc->numero_oc} aprobada".($sobregiros ? ' pasando el saldo del presupuesto.' : '.'));
        });
    }

    /** Cancela (BORRADOR o ENVIADA sin nada recibido) y revierte el presupuesto si ya se había aprobado. */
    public function cancelar(Request $request, int $orden, PresupuestoService $presupuestoService): RedirectResponse
    {
        return DB::transaction(function () use ($request, $orden, $presupuestoService) {
            $oc = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($orden);
            if (! in_array($oc->estado, ['BORRADOR', 'ENVIADA'], true) || $oc->detalles()->where('cantidad_recibida', '>', 0)->exists()) {
                return back()->withErrors(['orden' => 'Solo se pueden cancelar órdenes en borrador o aprobadas sin mercadería recibida.']);
            }

            $estabaAprobada = $oc->estado === 'ENVIADA';
            $oc->update(['estado' => 'CANCELADA']);
            if ($estabaAprobada) {
                $presupuestoService->revertirOrden($oc);
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
        $idEmpresa = $this->idEmpresa($request);
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
        return $this->consultaEmpresa(OrdenCompra::class, $request);
    }

    /** OC-AAAA-0001, consecutivo por empresa y año. */
    private function siguienteNumero(Request $request): string
    {
        $prefijo = 'OC-'.now()->year.'-';
        $ultimo = $this->deMiEmpresa($request)->where('numero_oc', 'like', $prefijo.'%')->pluck('numero_oc')
            ->map(fn ($n) => (int) substr($n, strlen($prefijo)))->max() ?? 0;

        return $prefijo.str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }

    private function guardarLineas(OrdenCompra $oc, array $lineas): void
    {
        foreach ($lineas as $l) {
            DetalleOrdenCompra::create($l + ['id_oc' => $oc->id_oc, 'cantidad_recibida' => 0]);
        }
        $oc->recalcularTotales();
    }
}
