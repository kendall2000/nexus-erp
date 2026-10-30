<?php

namespace App\Http\Controllers;

use App\Models\Inventario\CategoriaProducto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Categorías de productos en árbol (categoría → subcategorías).
 * Permisos: INV.CATEGORIAS.VER / .GESTIONAR.
 */
class CategoriaController extends Controller
{
    public function index(Request $request): View
    {
        $todas = $this->deMiEmpresa($request)->withCount(['productos', 'hijos'])->orderBy('nombre')->get();

        return view('categorias.index', ['arbol' => $this->aplanarArbol($todas), 'total' => $todas->count()]);
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new CategoriaProducto(['activo' => true, 'id_padre' => $request->integer('padre') ?: null]));
    }

    public function store(Request $request): RedirectResponse
    {
        $categoria = CategoriaProducto::create($this->validar($request) + ['id_empresa' => $request->user()->id_empresa]);

        return redirect()->route('categorias.index')->with('status', "Categoría «{$categoria->nombre}» creada.");
    }

    public function edit(Request $request, int $categoria): View
    {
        return $this->formulario($request, $this->deMiEmpresa($request)->findOrFail($categoria));
    }

    public function update(Request $request, int $categoria): RedirectResponse
    {
        $categoria = $this->deMiEmpresa($request)->findOrFail($categoria);
        $categoria->update($this->validar($request, $categoria));

        return redirect()->route('categorias.index')->with('status', "Categoría «{$categoria->nombre}» actualizada.");
    }

    public function estado(Request $request, int $categoria): RedirectResponse
    {
        $categoria = $this->deMiEmpresa($request)->findOrFail($categoria);
        $categoria->update(['activo' => ! $categoria->activo]);

        return back()->with('status', "Categoría «{$categoria->nombre}» ".($categoria->activo ? 'activada.' : 'desactivada.'));
    }

    public function destroy(Request $request, int $categoria): RedirectResponse
    {
        $categoria = $this->deMiEmpresa($request)->withCount(['hijos', 'productos'])->findOrFail($categoria);
        if ($categoria->hijos_count > 0) {
            return back()->withErrors(['categoria' => "«{$categoria->nombre}» tiene subcategorías: elimínalas o muévelas primero."]);
        }
        if ($categoria->productos_count > 0) {
            return back()->withErrors(['categoria' => "«{$categoria->nombre}» tiene {$categoria->productos_count} productos: cámbialos de categoría o desactívala."]);
        }
        $categoria->delete();

        return redirect()->route('categorias.index')->with('status', "Categoría «{$categoria->nombre}» eliminada.");
    }

    private function formulario(Request $request, CategoriaProducto $categoria): View
    {
        $todas = $this->deMiEmpresa($request)->orderBy('nombre')->get();
        // No puede quedar dentro de sí misma ni de una de sus subcategorías.
        $excluir = $categoria->exists ? [$categoria->id_categoria, ...$this->descendientes($todas, $categoria->id_categoria)] : [];

        return view('categorias.form', [
            'categoria' => $categoria,
            'padres' => $this->aplanarArbol($todas)->reject(fn ($f) => in_array($f['categoria']->id_categoria, $excluir, true)),
        ]);
    }

    private function deMiEmpresa(Request $request)
    {
        return CategoriaProducto::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?CategoriaProducto $categoria = null): array
    {
        $idEmpresa = $request->user()->id_empresa;
        $request->merge(['nombre' => trim((string) $request->input('nombre'))]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150',
                Rule::unique('categoria_producto', 'nombre')->where('id_empresa', $idEmpresa)->ignore($categoria?->id_categoria, 'id_categoria')],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'id_padre' => ['nullable', 'integer', Rule::exists('categoria_producto', 'id_categoria')->where('id_empresa', $idEmpresa)],
        ], [
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
            'id_padre.exists' => 'La categoría padre no es válida.',
        ]);
        $datos['id_padre'] = isset($datos['id_padre']) ? (int) $datos['id_padre'] : null;

        if ($categoria && $datos['id_padre'] !== null) {
            $todas = $this->deMiEmpresa($request)->get(['id_categoria', 'id_padre']);
            if (in_array($datos['id_padre'], [$categoria->id_categoria, ...$this->descendientes($todas, $categoria->id_categoria)], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'id_padre' => 'Una categoría no puede quedar dentro de sí misma ni de una de sus subcategorías.',
                ]);
            }
        }

        return $datos + ['activo' => $request->boolean('activo')];
    }

    /** @return list<int> ids de todas las subcategorías (a cualquier profundidad; tolera ciclos en los datos). */
    private function descendientes(Collection $todas, int $id): array
    {
        $encontrados = [];
        $pendientes = [$id];
        while ($pendientes) {
            $actual = array_shift($pendientes);
            foreach ($todas as $c) {
                $hijo = (int) $c->id_categoria;
                if ((int) $c->id_padre === $actual && $hijo !== $id && ! in_array($hijo, $encontrados, true)) {
                    $encontrados[] = $hijo;
                    $pendientes[] = $hijo;
                }
            }
        }

        return $encontrados;
    }

    /**
     * Árbol en orden para mostrar: raíces y debajo sus subcategorías, con su nivel.
     * Categorías huérfanas (padre inexistente) se muestran como raíces.
     *
     * @return Collection<int, array{categoria: CategoriaProducto, nivel: int}>
     */
    private function aplanarArbol(Collection $todas): Collection
    {
        $ids = $todas->pluck('id_categoria')->map(fn ($i) => (int) $i)->all();
        $porPadre = $todas->groupBy(fn ($c) => $c->id_padre && in_array((int) $c->id_padre, $ids, true) ? (int) $c->id_padre : 0);
        $salida = collect();
        $visitar = function (int $padre, int $nivel) use (&$visitar, $porPadre, $salida) {
            foreach ($porPadre->get($padre, collect()) as $c) {
                $salida->push(['categoria' => $c, 'nivel' => $nivel]);
                if ($nivel < 20) { // corta ciclos que ya existan en los datos
                    $visitar((int) $c->id_categoria, $nivel + 1);
                }
            }
        };
        $visitar(0, 0);

        // Categorías que quedaron en un ciclo (A dentro de B y B dentro de A) no se
        // alcanzan desde las raíces: se muestran al final para poder corregirlas.
        $vistas = $salida->map(fn ($f) => (int) $f['categoria']->id_categoria)->all();
        foreach ($todas as $c) {
            if (! in_array((int) $c->id_categoria, $vistas, true)) {
                $salida->push(['categoria' => $c, 'nivel' => 0]);
            }
        }

        return $salida;
    }
}
