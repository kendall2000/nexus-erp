<?php

namespace App\Http\Controllers;

use App\Models\Core\Empresa;
use App\Models\Inventario\Bodega;
use App\Models\Inventario\DetalleRecepcion;
use App\Models\Inventario\MovimientoInventario;
use App\Models\Inventario\OrdenCompra;
use App\Models\Inventario\RecepcionMercaderia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Recepciones de mercadería. Permisos: recepciones.ver / crear / imprimir.
 *
 * Solo se recibe de órdenes aprobadas (ENVIADA) o recibidas en parte (PARCIAL) y
 * nunca más de lo pendiente. Cada línea recibida deja una ENTRADA en el kardex
 * (movimiento_inventario), que a su vez actualiza stock_bodega y el costo promedio.
 * Las recepciones no se editan ni se anulan: el kardex es inmutable.
 */
class RecepcionController extends Controller
{
    /** Estados de la orden que aceptan recepciones. */
    public const RECIBIBLES = ['ENVIADA', 'PARCIAL'];

    public function index(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;
        $buscar = trim((string) $request->query('buscar'));

        return view('recepciones.index', [
            'recepciones' => RecepcionMercaderia::query()->where('id_empresa', $idEmpresa)
                ->with(['ordenCompra.proveedor', 'bodega'])->withCount('detalles')->withSum('detalles as total', 'subtotal')
                ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q->where('numero_recepcion', 'like', "%{$buscar}%")
                    ->orWhereHas('ordenCompra', fn ($q) => $q->where('numero_oc', 'like', "%{$buscar}%"))))
                ->when($request->filled('bodega'), fn ($q) => $q->where('id_bodega', $request->integer('bodega')))
                ->orderByDesc('fecha_recepcion')->orderByDesc('id_recepcion')->paginate(25)->withQueryString(),
            'bodegas' => Bodega::query()->where('id_empresa', $idEmpresa)->orderBy('nombre')->get(['id_bodega', 'nombre']),
            'pendientes' => $this->recibibles($request)->count(),
            'filtros' => $request->only(['buscar', 'bodega']),
        ]);
    }

    /** Paso 1: elegir la orden (?oc=). Paso 2: indicar lo que llegó de cada línea. */
    public function create(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;
        $oc = $request->filled('oc')
            ? $this->recibibles($request)->with(['proveedor', 'bodega', 'detalles.producto'])->find($request->integer('oc'))
            : null;

        return view('recepciones.form', [
            'oc' => $oc,
            'ordenes' => $this->recibibles($request)->with('proveedor')->orderBy('numero_oc')->get(),
            'bodegas' => Bodega::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('nombre')->get(),
            'siguienteNumero' => $this->siguienteNumero($request),
            'ocInvalida' => $request->filled('oc') && ! $oc,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $request->merge(['numero_recepcion' => ($n = strtoupper(trim((string) $request->input('numero_recepcion')))) === '' ? null : $n]);

        $datos = $request->validate([
            'id_oc' => ['required', 'integer'],
            'numero_recepcion' => ['nullable', 'string', 'max:30', Rule::unique('recepcion_mercaderia', 'numero_recepcion')->where('id_empresa', $idEmpresa)],
            'id_bodega' => ['required', 'integer', Rule::exists('bodega', 'id_bodega')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'fecha_recepcion' => ['required', 'date', 'before_or_equal:today'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'lineas' => ['required', 'array', 'max:200'],
            'lineas.*.cantidad' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'lineas.*.costo' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ], [
            'numero_recepcion.unique' => 'Ya existe una recepción con ese número.',
            'id_bodega.exists' => 'La bodega no es válida o está inactiva.',
            'fecha_recepcion.before_or_equal' => 'La fecha de recepción no puede ser futura.',
        ]);

        $recepcion = DB::transaction(function () use ($request, $datos, $idEmpresa) {
            // Se bloquea la orden para que dos recepciones simultáneas no reciban dos veces lo pendiente.
            $oc = $this->recibibles($request)->lockForUpdate()->with('detalles')->find($datos['id_oc']);
            if (! $oc) {
                throw ValidationException::withMessages(['id_oc' => 'La orden no existe o ya no admite recepciones (debe estar aprobada o recibida en parte).']);
            }
            if ($oc->fecha_emision && Carbon::parse($datos['fecha_recepcion'])->lt($oc->fecha_emision)) {
                throw ValidationException::withMessages(['fecha_recepcion' => 'La fecha de recepción no puede ser anterior a la emisión de la orden.']);
            }

            // La línea, el producto y lo pendiente salen de la orden, no del formulario.
            $recibir = [];
            foreach ($datos['lineas'] as $idLinea => $l) {
                $cantidad = round((float) ($l['cantidad'] ?? 0), 4);
                if ($cantidad <= 0) {
                    continue;
                }
                $linea = $oc->detalles->firstWhere('id_linea', (int) $idLinea);
                if (! $linea) {
                    throw ValidationException::withMessages(['lineas' => 'Una de las líneas no pertenece a la orden.']);
                }
                $pendiente = round((float) $linea->cantidad_pedida - (float) $linea->cantidad_recibida, 4);
                if ($cantidad > $pendiente) {
                    throw ValidationException::withMessages(["lineas.{$idLinea}.cantidad" => 'No se puede recibir más de lo pendiente ('.rtrim(rtrim(number_format($pendiente, 4), '0'), '.').').']);
                }
                $recibir[] = [$linea, $cantidad, round((float) ($l['costo'] ?? $this->costoLinea($linea)), 4)];
            }
            if (! $recibir) {
                throw ValidationException::withMessages(['lineas' => 'Indica la cantidad recibida de al menos un producto.']);
            }

            $recepcion = RecepcionMercaderia::create([
                'id_empresa' => $idEmpresa,
                'id_oc' => $oc->id_oc,
                'id_bodega' => $datos['id_bodega'],
                'numero_recepcion' => $datos['numero_recepcion'] ?? $this->siguienteNumero($request),
                'fecha_recepcion' => $datos['fecha_recepcion'],
                'notas' => $datos['notas'] ?? null,
                'created_by' => $request->user()->id_usuario,
            ]);

            foreach ($recibir as [$linea, $cantidad, $costo]) {
                DetalleRecepcion::create([
                    'id_recepcion' => $recepcion->id_recepcion,
                    'id_linea' => $linea->id_linea,
                    'id_producto' => $linea->id_producto,
                    'cantidad_recibida' => $cantidad,
                    'costo_unitario' => $costo,
                    'subtotal' => round($cantidad * $costo, 4),
                ]);
                $linea->increment('cantidad_recibida', $cantidad);

                // Kardex: la ENTRADA actualiza stock_bodega y el costo promedio (hook del modelo).
                MovimientoInventario::create([
                    'id_empresa' => $idEmpresa,
                    'id_producto' => $linea->id_producto,
                    'id_bodega' => $recepcion->id_bodega,
                    'tipo_movimiento' => 'ENTRADA',
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costo,
                    'moneda' => $oc->moneda,
                    'referencia_tipo' => 'COMPRA',
                    'referencia_id' => $recepcion->id_recepcion,
                    'observaciones' => "Recepción {$recepcion->numero_recepcion} · OC {$oc->numero_oc}",
                    'created_by' => $request->user()->id_usuario,
                ]);
            }

            $completa = $oc->detalles->every(fn ($d) => (float) $d->cantidad_recibida >= (float) $d->cantidad_pedida - 0.0001);
            $oc->update([
                'estado' => $completa ? 'RECIBIDA' : 'PARCIAL',
                'fecha_entrega_real' => $completa ? $datos['fecha_recepcion'] : null,
            ]);

            return $recepcion;
        });

        return redirect()->route('recepciones.show', $recepcion->id_recepcion)
            ->with('status', "Recepción {$recepcion->numero_recepcion} registrada. La orden quedó ".($recepcion->ordenCompra->estado === 'RECIBIDA' ? 'recibida completa.' : 'recibida en parte.'));
    }

    public function show(Request $request, int $recepcion): View
    {
        $r = $this->cargar($request, $recepcion);

        return view('recepciones.show', [
            'r' => $r,
            'movimientos' => MovimientoInventario::query()->where('id_empresa', $r->id_empresa)
                ->where('referencia_tipo', 'COMPRA')->where('referencia_id', $r->id_recepcion)->with('producto')->get(),
        ]);
    }

    public function imprimir(Request $request, int $recepcion): View
    {
        return view('recepciones.imprimir', [
            'r' => $this->cargar($request, $recepcion),
            'empresa' => Empresa::find($request->user()->id_empresa),
        ]);
    }

    /** Costo sugerido: precio de la línea ya con su descuento. */
    public static function costoLinea($linea): float
    {
        return (float) $linea->cantidad_pedida > 0 ? round((float) $linea->subtotal / (float) $linea->cantidad_pedida, 4) : (float) $linea->precio_unitario;
    }

    private function cargar(Request $request, int $recepcion): RecepcionMercaderia
    {
        return RecepcionMercaderia::query()->where('id_empresa', $request->user()->id_empresa)
            ->with(['ordenCompra.proveedor', 'bodega', 'creadoPor', 'detalles.producto', 'detalles.lineaOC'])
            ->findOrFail($recepcion);
    }

    private function recibibles(Request $request): Builder
    {
        return OrdenCompra::query()->where('id_empresa', $request->user()->id_empresa)->whereIn('estado', self::RECIBIBLES);
    }

    /** REC-AAAA-0001, consecutivo por empresa y año. */
    private function siguienteNumero(Request $request): string
    {
        $prefijo = 'REC-'.now()->year.'-';
        $ultimo = RecepcionMercaderia::query()->where('id_empresa', $request->user()->id_empresa)
            ->where('numero_recepcion', 'like', $prefijo.'%')->pluck('numero_recepcion')
            ->map(fn ($n) => (int) substr($n, strlen($prefijo)))->max() ?? 0;

        return $prefijo.str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }
}
