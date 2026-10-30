<?php

namespace App\Http\Controllers;

use App\Models\Core\CentroCosto;
use App\Support\ExportarCsv;
use App\Support\Referencias;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Centros de costo. Permisos: centros_costo.ver / crear / editar / eliminar / exportar. */
class CentroCostoController extends Controller
{
    /** Dónde se usa un centro (no se puede eliminar si aparece aquí). */
    private const USOS = [
        'presupuesto_anual' => 'presupuestos',
        'detalle_orden_compra' => 'líneas de órdenes de compra',
        'detalle_factura' => 'líneas de facturas',
        'producto.id_centro_default' => 'productos',
        'tipo_servicio.id_centro_default' => 'tipos de servicio',
    ];

    public function index(Request $request): View
    {
        return view('centros-costo.index', [
            'centros' => $this->filtrados($request)->withCount('presupuestos')->orderBy('codigo')->get(),
            'filtros' => $request->only(['buscar', 'estado']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $centros = $this->filtrados($request)->orderBy('codigo')->get();

        return ExportarCsv::descargar('centros_costo', ['Código', 'Nombre', 'Descripción', 'Activo'],
            $centros->map(fn ($c) => [$c->codigo, $c->nombre, $c->descripcion, $c->activo]));
    }

    public function create(): View
    {
        return view('centros-costo.form', ['centro' => new CentroCosto(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $centro = CentroCosto::create($this->validar($request) + ['id_empresa' => $request->user()->id_empresa]);

        return redirect()->route('centros-costo.index')->with('status', "Centro de costo «{$centro->etiqueta}» creado.");
    }

    public function edit(Request $request, int $centro): View
    {
        return view('centros-costo.form', ['centro' => $this->deMiEmpresa($request)->findOrFail($centro)]);
    }

    public function update(Request $request, int $centro): RedirectResponse
    {
        $centro = $this->deMiEmpresa($request)->findOrFail($centro);
        $centro->update($this->validar($request, $centro));

        return redirect()->route('centros-costo.index')->with('status', "Centro de costo «{$centro->etiqueta}» actualizado.");
    }

    public function estado(Request $request, int $centro): RedirectResponse
    {
        $centro = $this->deMiEmpresa($request)->findOrFail($centro);
        $centro->update(['activo' => ! $centro->activo]);

        return back()->with('status', "Centro de costo «{$centro->etiqueta}» ".($centro->activo ? 'activado.' : 'desactivado.'));
    }

    public function destroy(Request $request, int $centro): RedirectResponse
    {
        $centro = $this->deMiEmpresa($request)->findOrFail($centro);
        if ($uso = Referencias::enUso('id_centro', $centro->id_centro, self::USOS)) {
            return back()->withErrors(['centro' => "No se puede eliminar «{$centro->etiqueta}»: lo usan {$uso}. Puedes desactivarlo."]);
        }
        $centro->delete();

        return redirect()->route('centros-costo.index')->with('status', "Centro de costo «{$centro->etiqueta}» eliminado.");
    }

    private function filtrados(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q->where('codigo', 'like', "%{$buscar}%")->orWhere('nombre', 'like', "%{$buscar}%")))
            ->when(in_array($request->query('estado'), ['activos', 'inactivos'], true), fn ($q) => $q->where('activo', $request->query('estado') === 'activos'));
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return CentroCosto::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?CentroCosto $centro = null): array
    {
        $request->merge(['codigo' => strtoupper(trim((string) $request->input('codigo')))]);

        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/',
                Rule::unique('centro_costo', 'codigo')->where('id_empresa', $request->user()->id_empresa)->ignore($centro?->id_centro, 'id_centro')],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:300'],
        ], [
            'codigo.unique' => 'Ya existe un centro de costo con ese código.',
            'codigo.regex' => 'El código solo puede tener letras, números, punto, guion y guion bajo.',
        ]);

        return $datos + ['descripcion' => null, 'activo' => $request->boolean('activo')];
    }
}
