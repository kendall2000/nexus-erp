<?php

namespace App\Http\Controllers;

use App\Models\Core\LineaNegocio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Líneas de negocio (agrupan los tipos de servicio). Permisos: lineas_negocio.ver / crear / editar / eliminar. */
class LineaNegocioController extends Controller
{
    public function index(Request $request): View
    {
        return view('lineas-negocio.index', [
            'lineas' => $this->deMiEmpresa($request)->withCount('tiposServicio')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('lineas-negocio.form', ['linea' => new LineaNegocio(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $linea = LineaNegocio::create($this->validar($request) + ['id_empresa' => $request->user()->id_empresa]);

        return redirect()->route('lineas-negocio.index')->with('status', "Línea «{$linea->nombre}» creada.");
    }

    public function edit(Request $request, int $linea): View
    {
        return view('lineas-negocio.form', ['linea' => $this->deMiEmpresa($request)->findOrFail($linea)]);
    }

    public function update(Request $request, int $linea): RedirectResponse
    {
        $linea = $this->deMiEmpresa($request)->findOrFail($linea);
        $linea->update($this->validar($request, $linea));

        return redirect()->route('lineas-negocio.index')->with('status', "Línea «{$linea->nombre}» actualizada.");
    }

    public function estado(Request $request, int $linea): RedirectResponse
    {
        $linea = $this->deMiEmpresa($request)->findOrFail($linea);
        $linea->update(['activo' => ! $linea->activo]);

        return back()->with('status', "Línea «{$linea->nombre}» ".($linea->activo ? 'activada.' : 'desactivada.'));
    }

    public function destroy(Request $request, int $linea): RedirectResponse
    {
        $linea = $this->deMiEmpresa($request)->withCount('tiposServicio')->findOrFail($linea);
        if ($linea->tipos_servicio_count) {
            return back()->withErrors(['linea' => "No se puede eliminar «{$linea->nombre}»: tiene {$linea->tipos_servicio_count} tipos de servicio. Puedes desactivarla."]);
        }
        $linea->delete();

        return redirect()->route('lineas-negocio.index')->with('status', "Línea «{$linea->nombre}» eliminada.");
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return LineaNegocio::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?LineaNegocio $linea = null): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('linea_negocio', 'nombre')->where('id_empresa', $request->user()->id_empresa)->ignore($linea?->id_linea, 'id_linea')],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ], ['nombre.unique' => 'Ya existe una línea con ese nombre.']);

        return $datos + ['descripcion' => null, 'activo' => $request->boolean('activo')];
    }
}
