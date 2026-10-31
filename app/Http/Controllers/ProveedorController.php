<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventario\GuardarProveedorRequest;
use App\Models\Core\Pais;
use App\Models\Inventario\Proveedor;
use App\Support\ExportarCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Proveedores. Permisos: proveedores.ver / crear / editar / eliminar / exportar. */
class ProveedorController extends Controller
{
    public const TIPOS = ['BIENES' => 'Bienes', 'SERVICIOS' => 'Servicios', 'AMBOS' => 'Bienes y servicios'];

    /** Estados de orden de compra que impiden eliminar al proveedor. */
    private const ORDENES_ABIERTAS = ['BORRADOR', 'ENVIADA', 'PARCIAL'];

    public function index(Request $request): View
    {
        return view('proveedores.index', [
            'proveedores' => $this->filtrados($request)->with('pais')
                ->withCount(['ordenesCompra as ordenes_abiertas' => fn ($q) => $q->whereIn('estado', self::ORDENES_ABIERTAS)])
                ->orderBy('razon_social')->paginate(25)->withQueryString(),
            'tipos' => self::TIPOS,
            'filtros' => $request->only(['buscar', 'tipo', 'estado']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $proveedores = $this->filtrados($request)->with('pais')->orderBy('razon_social')->cursor();

        return ExportarCsv::descargar('proveedores', ['Razón social', 'Nombre comercial', 'NIT', 'Tipo', 'Correo', 'Teléfono', 'Contacto', 'País', 'Días de crédito', 'Moneda', 'Activo'],
            (function () use ($proveedores) {
                foreach ($proveedores as $p) {
                    yield [$p->razon_social, $p->nombre_comercial, $p->nit, self::TIPOS[$p->tipo_proveedor] ?? $p->tipo_proveedor, $p->email,
                        $p->telefono, $p->contacto, $p->pais?->nombre, $p->dias_credito, $p->moneda_pago, $p->activo];
                }
            })());
    }

    public function create(): View
    {
        return $this->formulario(new Proveedor(['activo' => true, 'tipo_proveedor' => 'BIENES', 'dias_credito' => 0, 'moneda_pago' => 'GTQ']));
    }

    public function store(GuardarProveedorRequest $request): RedirectResponse
    {
        $proveedor = Proveedor::create($request->datosLimpios() + ['id_empresa' => $this->idEmpresa($request)]);

        return redirect()->route('proveedores.index')->with('status', "Proveedor «{$proveedor->razon_social}» creado.");
    }

    public function edit(Request $request, int $proveedor): View
    {
        return $this->formulario($this->deMiEmpresa($request)->findOrFail($proveedor));
    }

    public function update(GuardarProveedorRequest $request, int $proveedor): RedirectResponse
    {
        $proveedor = $this->deMiEmpresa($request)->findOrFail($proveedor);
        $proveedor->update($request->datosLimpios());

        return redirect()->route('proveedores.index')->with('status', "Proveedor «{$proveedor->razon_social}» actualizado.");
    }

    public function estado(Request $request, int $proveedor): RedirectResponse
    {
        $proveedor = $this->deMiEmpresa($request)->findOrFail($proveedor);
        $proveedor->update(['activo' => ! $proveedor->activo]);

        return back()->with('status', "Proveedor «{$proveedor->razon_social}» ".($proveedor->activo ? 'activado.' : 'desactivado.'));
    }

    /** Eliminación lógica (queda en la base con deleted_at; sus órdenes siguen viéndolo). */
    public function destroy(Request $request, int $proveedor): RedirectResponse
    {
        $proveedor = $this->deMiEmpresa($request)->findOrFail($proveedor);
        $abiertas = $proveedor->ordenesCompra()->whereIn('estado', self::ORDENES_ABIERTAS)->count();
        if ($abiertas > 0) {
            return back()->withErrors(['proveedor' => "No se puede eliminar «{$proveedor->razon_social}»: tiene {$abiertas} ".($abiertas === 1 ? 'orden de compra abierta' : 'órdenes de compra abiertas').'.']);
        }
        $proveedor->delete();

        return redirect()->route('proveedores.index')->with('status', "Proveedor «{$proveedor->razon_social}» eliminado.");
    }

    private function filtrados(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w->where('razon_social', 'like', "%{$buscar}%")
                ->orWhere('nombre_comercial', 'like', "%{$buscar}%")->orWhere('nit', 'like', "%{$buscar}%")))
            ->when(array_key_exists((string) $request->query('tipo'), self::TIPOS), fn ($q) => $q->where('tipo_proveedor', $request->query('tipo')))
            ->when($request->query('estado') === 'activos', fn ($q) => $q->where('activo', true))
            ->when($request->query('estado') === 'inactivos', fn ($q) => $q->where('activo', false));
    }

    private function formulario(Proveedor $proveedor): View
    {
        return view('proveedores.form', [
            'proveedor' => $proveedor,
            'tipos' => self::TIPOS,
            'paises' => Pais::query()->where('activo', true)->orderBy('nombre')->get(),
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
        ]);
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return $this->consultaEmpresa(Proveedor::class, $request);
    }
}
