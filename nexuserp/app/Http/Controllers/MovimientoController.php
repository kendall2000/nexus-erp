<?php

namespace App\Http\Controllers;

use App\Models\Inventario\Bodega;
use App\Models\Inventario\MovimientoInventario;
use App\Models\Inventario\Producto;
use App\Models\Inventario\StockBodega;
use App\Support\Kardex;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Kardex (movimientos de inventario). Permisos: movimientos.ver / crear.
 * Los movimientos no se editan ni se borran: un error se corrige con otro movimiento.
 */
class MovimientoController extends Controller
{
    public const TIPOS = ['ENTRADA' => 'Entradas', 'SALIDA' => 'Salidas', 'BAJA' => 'Bajas', 'TRASLADO' => 'Traslados'];

    public function index(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;
        $producto = $request->filled('producto') ? Producto::query()->where('id_empresa', $idEmpresa)->find($request->integer('producto')) : null;
        $bodega = $request->filled('bodega') ? Bodega::query()->where('id_empresa', $idEmpresa)->find($request->integer('bodega')) : null;
        // Con producto y bodega (y sin filtro de tipo) el listado es un kardex: orden cronológico con saldo acumulado.
        $esKardex = $producto && $bodega && ! $request->filled('tipo');

        $consulta = $this->filtrados($request)->with(['producto', 'bodega', 'creadoPor']);
        $movimientos = $esKardex
            ? $consulta->orderBy('created_at')->orderBy('id_movimiento')->paginate(50)->withQueryString()
            : $consulta->orderByDesc('created_at')->orderByDesc('id_movimiento')->paginate(50)->withQueryString();

        $saldoInicial = null;
        if ($esKardex) {
            // Saldo antes de la primera fila de la página: movimientos anteriores al rango y de páginas previas.
            $primero = $movimientos->first();
            $saldoInicial = $primero ? (float) $this->deMiEmpresa($request)->where('id_producto', $producto->id_producto)->where('id_bodega', $bodega->id_bodega)
                ->where(fn ($q) => $q->where('created_at', '<', $primero->created_at)
                    ->orWhere(fn ($q) => $q->where('created_at', $primero->created_at)->where('id_movimiento', '<', $primero->id_movimiento)))
                ->selectRaw("COALESCE(SUM(CASE WHEN tipo_movimiento IN ('ENTRADA','DEVOLUCION') THEN cantidad WHEN tipo_movimiento IN ('SALIDA','BAJA') THEN -cantidad ELSE 0 END), 0) AS saldo")
                ->value('saldo') : 0.0;
        }

        return view('movimientos.index', [
            'movimientos' => $movimientos,
            'esKardex' => $esKardex,
            'saldoInicial' => $saldoInicial,
            'producto' => $producto,
            'bodega' => $bodega,
            'existencia' => $esKardex ? StockBodega::query()->where(['id_producto' => $producto->id_producto, 'id_bodega' => $bodega->id_bodega])->first() : null,
            'productos' => Producto::query()->where('id_empresa', $idEmpresa)->orderBy('nombre')->get(['id_producto', 'codigo', 'nombre']),
            'bodegas' => Bodega::query()->where('id_empresa', $idEmpresa)->orderBy('nombre')->get(['id_bodega', 'nombre']),
            'tipos' => self::TIPOS,
            'filtros' => $request->only(['producto', 'bodega', 'tipo', 'desde', 'hasta']),
        ]);
    }

    public function create(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;
        $bodegas = Bodega::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('nombre')->get();
        $existencias = StockBodega::query()->whereIn('id_bodega', $bodegas->pluck('id_bodega'))->get(['id_producto', 'id_bodega', 'cantidad_actual', 'costo_promedio'])
            ->mapWithKeys(fn ($s) => ["{$s->id_producto}-{$s->id_bodega}" => ['cantidad' => (float) $s->cantidad_actual, 'costo' => (float) $s->costo_promedio]]);

        return view('movimientos.form', [
            'tipos' => Kardex::MANUALES,
            'productos' => Producto::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('nombre')
                ->get(['id_producto', 'codigo', 'nombre', 'unidad_medida', 'requiere_lote', 'es_perecedero']),
            'bodegas' => $bodegas,
            'existencias' => $existencias,
            'elegido' => $request->only(['producto', 'bodega']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $bodegaValida = Rule::exists('bodega', 'id_bodega')->where('id_empresa', $idEmpresa)->where('activo', true);
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(Kardex::MANUALES))],
            'id_producto' => ['required', 'integer', Rule::exists('producto', 'id_producto')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'id_bodega' => ['required', 'integer', $bodegaValida],
            'id_bodega_destino' => ['nullable', 'required_if:tipo,TRASLADO', 'integer', 'different:id_bodega', $bodegaValida],
            'cantidad' => ['required', 'numeric', 'min:0.0001', 'max:99999999'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'numero_lote' => ['nullable', 'string', 'max:50'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'motivo' => ['required', 'string', 'min:5', 'max:250'],
        ], [
            'id_bodega_destino.required_if' => 'Elige la bodega a la que se traslada.',
            'id_bodega_destino.different' => 'La bodega destino debe ser distinta de la de origen.',
            'motivo.required' => 'Indica el motivo del movimiento.',
        ]);

        // Entradas de productos con lote o perecederos: lote y vencimiento obligatorios.
        $producto = Producto::query()->findOrFail($datos['id_producto']);
        if ($datos['tipo'] === 'AJUSTE_ENTRADA') {
            $request->validate([
                'numero_lote' => [Rule::requiredIf((bool) $producto->requiere_lote)],
                'fecha_vencimiento' => [Rule::requiredIf((bool) $producto->es_perecedero), 'nullable', 'date', 'after:today'],
            ], ['numero_lote.required' => 'Este producto se controla por lote.', 'fecha_vencimiento.required' => 'Este producto es perecedero: indica el vencimiento.']);
        }

        $movimientos = DB::transaction(fn () => Kardex::registrar($idEmpresa, $request->user()->id_usuario, $producto->moneda ?: 'GTQ', $datos));
        $etiqueta = Kardex::MANUALES[$datos['tipo']][0];

        return redirect()->route('movimientos.index', ['producto' => $producto->id_producto, 'bodega' => $datos['id_bodega']])
            ->with('status', "{$etiqueta} de {$producto->nombre} registrada".(count($movimientos) > 1 ? ' (salida del origen y entrada en el destino).' : '.'));
    }

    private function filtrados(Request $request): Builder
    {
        $fecha = function (mixed $v): ?string {
            try {
                return is_string($v) && $v !== '' ? Carbon::parse($v)->toDateString() : null;
            } catch (\Throwable) {
                return null;
            }
        };
        $tipo = (string) $request->query('tipo');

        return $this->deMiEmpresa($request)
            ->when($request->filled('producto'), fn ($q) => $q->where('id_producto', $request->integer('producto')))
            ->when($request->filled('bodega'), fn ($q) => $q->where('id_bodega', $request->integer('bodega')))
            ->when($tipo === 'TRASLADO', fn ($q) => $q->where('referencia_tipo', 'TRASLADO'))
            ->when(in_array($tipo, ['ENTRADA', 'SALIDA', 'BAJA'], true), fn ($q) => $q->where('tipo_movimiento', $tipo)
                ->where(fn ($q) => $q->whereNull('referencia_tipo')->orWhere('referencia_tipo', '!=', 'TRASLADO')))
            ->when($fecha($request->query('desde')), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($fecha($request->query('hasta')), fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return MovimientoInventario::query()->where('id_empresa', $request->user()->id_empresa);
    }
}
