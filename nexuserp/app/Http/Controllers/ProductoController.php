<?php

namespace App\Http\Controllers;

use App\Models\Core\CentroCosto;
use App\Models\Core\CuentaContable;
use App\Models\Inventario\CategoriaProducto;
use App\Models\Inventario\Producto;
use App\Support\ExportarCsv;
use App\Support\Referencias;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Catálogo de productos. Permisos: productos.ver / crear / editar / eliminar / exportar. */
class ProductoController extends Controller
{
    public const UNIDADES = ['UND' => 'Unidad', 'KG' => 'Kilogramo', 'LB' => 'Libra', 'LT' => 'Litro', 'GL' => 'Galón', 'MT' => 'Metro', 'CAJA' => 'Caja', 'PAQ' => 'Paquete', 'SERV' => 'Servicio'];

    public function index(Request $request): View
    {
        $productos = $this->filtrados($request)
            ->with('categoria')
            ->withSum('stocks as existencia', 'cantidad_actual')
            ->orderBy('nombre')
            ->paginate(25)->withQueryString();

        return view('productos.index', [
            'productos' => $productos,
            'categorias' => $this->categorias($request),
            'filtros' => $request->only(['buscar', 'categoria', 'estado']),
            'moneda' => \App\Support\Sistema::config()->moneda ?: 'Q',
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $productos = $this->filtrados($request)->with('categoria')->withSum('stocks as existencia', 'cantidad_actual')->orderBy('nombre')->cursor();

        return ExportarCsv::descargar('productos', ['Código', 'Nombre', 'Categoría', 'Unidad', 'Precio compra', 'Precio venta', 'Moneda', 'Existencia', 'Mínimo', 'Máximo', 'Activo'],
            (function () use ($productos) {
                foreach ($productos as $p) {
                    yield [$p->codigo, $p->nombre, $p->categoria?->nombre, $p->unidad_medida, $p->precio_compra, $p->precio_venta, $p->moneda,
                        (float) $p->existencia, $p->stock_minimo, $p->stock_maximo, $p->activo];
                }
            })());
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new Producto(['activo' => true, 'unidad_medida' => 'UND', 'moneda' => 'GTQ', 'stock_minimo' => 0]));
    }

    public function store(Request $request): RedirectResponse
    {
        $producto = Producto::create($this->validar($request) + ['id_empresa' => $request->user()->id_empresa]);

        return redirect()->route('productos.index')->with('status', "Producto «{$producto->nombre}» creado.");
    }

    public function edit(Request $request, int $producto): View
    {
        $producto = $this->deMiEmpresa($request)->with(['stocks.bodega'])->findOrFail($producto);

        return $this->formulario($request, $producto);
    }

    public function update(Request $request, int $producto): RedirectResponse
    {
        $producto = $this->deMiEmpresa($request)->findOrFail($producto);
        $producto->update($this->validar($request, $producto));

        return redirect()->route('productos.index')->with('status', "Producto «{$producto->nombre}» actualizado.");
    }

    public function estado(Request $request, int $producto): RedirectResponse
    {
        $producto = $this->deMiEmpresa($request)->findOrFail($producto);
        $producto->update(['activo' => ! $producto->activo]);

        return back()->with('status', "Producto «{$producto->nombre}» ".($producto->activo ? 'activado.' : 'desactivado.'));
    }

    public function destroy(Request $request, int $producto): RedirectResponse
    {
        $producto = $this->deMiEmpresa($request)->findOrFail($producto);

        if ($producto->stocks()->where('cantidad_actual', '>', 0)->exists()) {
            return back()->withErrors(['producto' => "No se puede eliminar «{$producto->nombre}»: tiene existencia en bodega."]);
        }
        $uso = Referencias::enUso('id_producto', $producto->id_producto, [
            'detalle_orden_compra' => 'líneas de órdenes de compra', 'detalle_recepcion' => 'recepciones', 'movimiento_inventario' => 'movimientos de inventario',
        ]);
        if ($uso) {
            return back()->withErrors(['producto' => "No se puede eliminar «{$producto->nombre}»: lo usan {$uso}. Puedes desactivarlo."]);
        }

        DB::transaction(function () use ($producto) {
            $producto->stocks()->delete(); // solo quedan filas en cero
            $producto->delete();
        });

        return redirect()->route('productos.index')->with('status', "Producto «{$producto->nombre}» eliminado.");
    }

    private function filtrados(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w->where('nombre', 'like', "%{$buscar}%")->orWhere('codigo', 'like', "%{$buscar}%")))
            ->when($request->filled('categoria'), fn ($q) => $q->where('id_categoria', $request->integer('categoria')))
            ->when($request->query('estado') === 'activos', fn ($q) => $q->where('activo', true))
            ->when($request->query('estado') === 'inactivos', fn ($q) => $q->where('activo', false));
    }

    private function formulario(Request $request, Producto $producto): View
    {
        $idEmpresa = $request->user()->id_empresa;

        return view('productos.form', [
            'producto' => $producto,
            'categorias' => $this->categorias($request, soloActivas: true),
            'unidades' => self::UNIDADES,
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
            'cuentas' => CuentaContable::query()->where('id_empresa', $idEmpresa)->where('activo', true)->where('permite_movimiento', true)
                ->whereIn('tipo', ['GASTO', 'COSTO'])->orderBy('codigo')->get(),
            'centros' => CentroCosto::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('codigo')->get(),
        ]);
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return Producto::query()->where('id_empresa', $request->user()->id_empresa);
    }

    private function categorias(Request $request, bool $soloActivas = false)
    {
        return CategoriaProducto::query()->where('id_empresa', $request->user()->id_empresa)
            ->when($soloActivas, fn ($q) => $q->where('activo', true))->orderBy('nombre')->get();
    }

    /** @return array<string, mixed> Solo los campos del formulario (nunca id_empresa). */
    private function validar(Request $request, ?Producto $producto = null): array
    {
        $idEmpresa = $request->user()->id_empresa;
        $request->merge(['codigo' => strtoupper(trim((string) $request->input('codigo'))), 'nombre' => trim((string) $request->input('nombre'))]);

        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:50', Rule::unique('producto', 'codigo')->where('id_empresa', $idEmpresa)->ignore($producto?->id_producto, 'id_producto')],
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'id_categoria' => ['nullable', 'integer', Rule::exists('categoria_producto', 'id_categoria')->where('id_empresa', $idEmpresa)],
            'unidad_medida' => ['required', Rule::in(array_keys(self::UNIDADES))],
            'precio_compra' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'precio_venta' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'stock_maximo' => ['nullable', 'numeric', 'min:0', 'gte:stock_minimo'],
            'id_cuenta_gasto' => ['nullable', 'integer', Rule::exists('cuenta_contable', 'id_cuenta')->where('id_empresa', $idEmpresa)->where('permite_movimiento', true)],
            'id_centro_default' => ['nullable', 'integer', Rule::exists('centro_costo', 'id_centro')->where('id_empresa', $idEmpresa)],
        ], [
            'codigo.unique' => 'Ya existe un producto con ese código.',
            'stock_maximo.gte' => 'El stock máximo no puede ser menor que el mínimo.',
            'id_categoria.exists' => 'La categoría elegida no es válida.',
        ]);

        return $datos + [
            'id_categoria' => null, 'id_cuenta_gasto' => null, 'id_centro_default' => null,
            'requiere_lote' => $request->boolean('requiere_lote'),
            'es_perecedero' => $request->boolean('es_perecedero'),
            'activo' => $request->boolean('activo'),
        ];
    }
}
