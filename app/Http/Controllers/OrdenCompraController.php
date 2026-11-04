<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventario\GuardarOrdenCompraRequest;
use App\Models\Core\CentroCosto;
use App\Models\Core\CuentaContable;
use App\Models\Core\Empresa;
use App\Models\Core\Sucursal;
use App\Models\Inventario\Bodega;
use App\Models\Inventario\DetalleOrdenCompra;
use App\Models\Inventario\OrdenCompra;
use App\Models\Inventario\Producto;
use App\Models\Inventario\Proveedor;
use App\Services\Finanzas\PresupuestoService;
use App\Support\ExportarCsv;
use App\Support\Sedes;
use App\Support\Sistema;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            'ordenes' => $this->filtradas($request)->with(['proveedor', 'bodega', 'sucursal'])
                ->withSum('detalles as pedido', 'cantidad_pedida')->withSum('detalles as recibido', 'cantidad_recibida')
                ->orderByDesc('fecha_emision')->orderByDesc('id_oc')->paginate(25)->withQueryString(),
            'proveedores' => Proveedor::query()->where('id_empresa', $this->idEmpresa($request))->orderBy('razon_social')->get(['id_proveedor', 'razon_social']),
            'estados' => self::ESTADOS,
            'sedes' => Sucursal::paraSelect($this->idEmpresa($request)),
            'filtros' => $request->only(['buscar', 'estado', 'proveedor', 'sucursal']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $ordenes = $this->filtradas($request)->with(['proveedor', 'bodega', 'sucursal'])->orderByDesc('fecha_emision')->cursor();

        return ExportarCsv::descargar('ordenes_compra', ['Número', 'Proveedor', 'Sucursal', 'Bodega', 'Emisión', 'Entrega esperada', 'Moneda', 'Subtotal', 'IVA', 'Total', 'Estado'],
            (function () use ($ordenes) {
                foreach ($ordenes as $oc) {
                    yield [$oc->numero_oc, $oc->proveedor?->razon_social, $oc->sucursal?->nombre, $oc->bodega?->nombre, $oc->fecha_emision?->format('d/m/Y'),
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

    /** Descarga la orden en PDF (dompdf) con el logo, los datos y el color de la empresa. */
    public function pdf(Request $request, int $orden): Response
    {
        $oc = $this->cargar($request, $orden);
        $empresa = Empresa::find($this->idEmpresa($request));

        $pdf = Pdf::loadView('ordenes-compra.pdf', [
            'oc' => $oc,
            'empresa' => $empresa,
            'estados' => self::ESTADOS,
            'logo' => Sistema::logoIncrustado($empresa?->logoDocumentos() ?? Sistema::config()->imgLogo),
            'color' => Sistema::colorPrimario(),
        ])->setPaper('letter');

        // dompdf no resuelve counter(pages) en CSS: el «Página X de Y» se dibuja ya renderizado.
        $pdf->render(); // el del wrapper: marca el PDF como renderizado y download() no lo repite
        $dompdf = $pdf->getDomPDF();
        $dompdf->getCanvas()->page_text(540, 768, 'Página {PAGE_NUM} de {PAGE_COUNT}', $dompdf->getFontMetrics()->getFont('DejaVu Sans'), 6, [0.61, 0.64, 0.69]);

        return $pdf->download('Orden-de-compra-'.Str::slug($oc->numero_oc ?: (string) $oc->id_oc).'.pdf');
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new OrdenCompra([
            'fecha_emision' => now(), 'moneda' => 'GTQ', 'estado' => 'BORRADOR', 'id_sucursal' => Sedes::predeterminada($request->user()),
        ]));
    }

    public function store(GuardarOrdenCompraRequest $request): RedirectResponse
    {
        $datos = $request->datosLimpios();

        $oc = DB::transaction(function () use ($request, $datos) {
            $encabezado = $this->conSede($datos['encabezado']);
            DB::table('empresa')->where('id_empresa', $this->idEmpresa($request))->lockForUpdate()->first(); // un número a la vez
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
        $datos = $request->datosLimpios();

        // El estado se revisa con la orden bloqueada: si otro la aprueba al mismo tiempo,
        // no se cambian las líneas de una orden que ya ejecutó el presupuesto.
        return DB::transaction(function () use ($request, $orden, $datos) {
            $oc = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($orden);
            if ($oc->estado !== 'BORRADOR') {
                return redirect()->route('ordenes-compra.show', $oc->id_oc)->withErrors(['orden' => 'Solo se pueden editar órdenes en borrador.']);
            }
            $encabezado = $this->conSede($datos['encabezado']);
            $encabezado['numero_oc'] ??= $oc->numero_oc;
            $oc->update($encabezado);
            $oc->detalles()->delete();
            $this->guardarLineas($oc, $datos['lineas']);

            return redirect()->route('ordenes-compra.show', $oc->id_oc)->with('status', "Orden {$oc->numero_oc} actualizada.");
        });
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
        return DB::transaction(function () use ($request, $orden) {
            $oc = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($orden);
            if ($oc->estado !== 'BORRADOR') {
                return back()->withErrors(['orden' => 'Solo se pueden eliminar órdenes en borrador. Las demás se cancelan.']);
            }
            $oc->detalles()->delete();
            $oc->delete();

            return redirect()->route('ordenes-compra.index')->with('status', "Orden {$oc->numero_oc} eliminada.");
        });
    }

    private function filtradas(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->where('numero_oc', 'like', "%{$buscar}%"))
            ->when(array_key_exists((string) $request->query('estado'), self::ESTADOS), fn ($q) => $q->where('estado', $request->query('estado')))
            ->when($request->filled('proveedor'), fn ($q) => $q->where('id_proveedor', $request->integer('proveedor')))
            ->tap(fn ($q) => Sedes::filtrar($q, $request->query('sucursal')));
    }

    /** Sin sede elegida, la orden es de la sucursal de su bodega de destino. */
    private function conSede(array $encabezado): array
    {
        if (! $encabezado['id_sucursal'] && $encabezado['id_bodega']) {
            $encabezado['id_sucursal'] = Bodega::query()->whereKey($encabezado['id_bodega'])->value('id_sucursal');
        }

        return $encabezado;
    }

    private function cargar(Request $request, int $orden): OrdenCompra
    {
        return $this->deMiEmpresa($request)
            ->with(['proveedor', 'bodega', 'sucursal', 'creadoPor', 'aprobadoPor', 'detalles.producto.centroDefault', 'detalles.producto.cuentaGasto', 'detalles.centroCosto', 'detalles.cuentaContable'])
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
            'sedes' => Sucursal::paraSelect($idEmpresa, $oc->id_sucursal),
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
