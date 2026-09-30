<?php

namespace App\Http\Controllers;

use App\Models\Core\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestión del menú lateral (solo Administrador): grupos y sus opciones, en
 * dos niveles. Qué rol ve cada opción se define en Roles y permisos.
 */
class GestionMenuController extends Controller
{
    public function index(Request $request): View
    {
        $grupos = $this->deMiEmpresa($request)->whereNull('id_padre')
            ->with(['todosLosHijos' => fn ($q) => $q->withCount('roles')])
            ->orderBy('orden')->orderBy('id_menu')->get();

        return view('menu.index', ['grupos' => $grupos]);
    }

    public function create(Request $request): View
    {
        $idPadre = $request->integer('grupo') ?: null;

        return $this->formulario($request, new Menu(['activo' => true, 'id_padre' => $idPadre, 'icono' => $idPadre ? 'chevrons-right' : null]));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $item = Menu::create($datos + [
            'id_empresa' => $request->user()->id_empresa,
            // Al final de su grupo (o de los grupos).
            'orden' => (int) $this->deMiEmpresa($request)->where('id_padre', $datos['id_padre'])->max('orden') + 1,
        ]);

        return redirect()->route('menu.index')->with('status', ($item->id_padre ? 'Opción' : 'Grupo')." «{$item->nombre}» creado.");
    }

    public function edit(Request $request, int $menu): View
    {
        return $this->formulario($request, $this->deMiEmpresa($request)->findOrFail($menu));
    }

    public function update(Request $request, int $menu): RedirectResponse
    {
        $item = $this->deMiEmpresa($request)->findOrFail($menu);
        $datos = $this->validar($request, $item);

        if ($datos['id_padre'] !== null && $item->todosLosHijos()->exists()) {
            return back()->withErrors(['id_padre' => 'Un grupo con opciones no puede pasar a ser una opción.'])->withInput();
        }
        if ((int) $datos['id_padre'] !== (int) $item->id_padre) {
            // Cambia de grupo: queda al final del nuevo.
            $datos['orden'] = (int) $this->deMiEmpresa($request)->where('id_padre', $datos['id_padre'])->max('orden') + 1;
        }

        $item->update($datos);
        $this->desactivarHijosSiEsGrupo($item);

        return redirect()->route('menu.index')->with('status', "«{$item->nombre}» actualizado.");
    }

    /** Activar / desactivar. Desactivar un grupo desactiva también sus opciones. */
    public function estado(Request $request, int $menu): RedirectResponse
    {
        $item = $this->deMiEmpresa($request)->findOrFail($menu);
        $item->update(['activo' => ! $item->activo]);
        $this->desactivarHijosSiEsGrupo($item);

        return back()->with('status', "«{$item->nombre}» ".($item->activo ? 'activado.' : 'desactivado.'));
    }

    /** Sube o baja un lugar dentro de su grupo. */
    public function mover(Request $request, int $menu): RedirectResponse
    {
        $request->validate(['direccion' => ['required', Rule::in(['subir', 'bajar'])]]);
        $item = $this->deMiEmpresa($request)->findOrFail($menu);

        DB::transaction(function () use ($request, $item) {
            // Se renumeran los hermanos 1..n (el orden podía tener repetidos) y se intercambia.
            $hermanos = $this->deMiEmpresa($request)->where('id_padre', $item->id_padre)
                ->orderBy('orden')->orderBy('id_menu')->pluck('id_menu')->map(fn ($id) => (int) $id)->all();
            $i = array_search($item->id_menu, $hermanos, true);
            $j = $request->input('direccion') === 'subir' ? $i - 1 : $i + 1;
            if ($j >= 0 && $j < count($hermanos)) {
                [$hermanos[$i], $hermanos[$j]] = [$hermanos[$j], $hermanos[$i]];
            }
            foreach ($hermanos as $posicion => $id) {
                Menu::whereKey($id)->update(['orden' => $posicion + 1]);
            }
        });

        return back();
    }

    public function destroy(Request $request, int $menu): RedirectResponse
    {
        $item = $this->deMiEmpresa($request)->findOrFail($menu);
        if ($item->todosLosHijos()->exists()) {
            return back()->withErrors(['menu' => "El grupo «{$item->nombre}» tiene opciones: elimínalas o muévelas primero."]);
        }

        DB::transaction(function () use ($item) {
            DB::table('menu_rol')->where('id_menu', $item->id_menu)->delete();
            $item->delete();
        });

        return redirect()->route('menu.index')->with('status', "«{$item->nombre}» eliminado.");
    }

    private function formulario(Request $request, Menu $item): View
    {
        return view('menu.form', [
            'item' => $item,
            'grupos' => $this->deMiEmpresa($request)->whereNull('id_padre')
                ->when($item->exists, fn ($q) => $q->whereKeyNot($item->id_menu))
                ->orderBy('orden')->get(),
            'iconos' => $this->deMiEmpresa($request)->whereNotNull('icono')->distinct()->orderBy('icono')->pluck('icono')
                ->merge(['home', 'users', 'user', 'briefcase', 'package', 'archive', 'truck', 'shopping-cart', 'file-text', 'dollar-sign',
                    'credit-card', 'bar-chart-2', 'book', 'settings', 'shield', 'layers', 'map-pin', 'clock', 'calendar', 'mail', 'target',
                    'trending-up', 'headphones', 'grid', 'list', 'tag', 'tool', 'database', 'pie-chart', 'clipboard'])
                ->filter(fn ($i) => preg_match('/^[a-z0-9-]+$/', (string) $i))->unique()->sort()->values(),
        ]);
    }

    private function deMiEmpresa(Request $request)
    {
        return Menu::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Menu $item = null): array
    {
        $request->merge([
            'nombre' => trim((string) $request->input('nombre')),
            'ruta' => ($r = trim((string) $request->input('ruta'))) === '' ? null : '/'.ltrim($r, '/'),
            'icono' => ($i = strtolower(trim((string) $request->input('icono')))) === '' ? null : $i,
        ]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            // Solo grupos de la misma empresa (dos niveles: el padre no puede tener padre).
            'id_padre' => ['nullable', 'integer', Rule::exists('menu', 'id_menu')
                ->where('id_empresa', $request->user()->id_empresa)->whereNull('id_padre'),
                ...($item ? [Rule::notIn([$item->id_menu])] : [])],
            'icono' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/'],
            // Solo rutas internas del sistema, p. ej. /sistema/clientes.
            'ruta' => ['nullable', 'string', 'max:200', 'regex:#^/[A-Za-z0-9/_\-]*$#'],
        ], [
            'icono.regex' => 'El ícono es el nombre de un ícono Feather: minúsculas, números y guiones (p. ej. «file-text»).',
            'ruta.regex' => 'La ruta debe ser interna, p. ej. /sistema/clientes (sin espacios ni direcciones externas).',
            'id_padre.exists' => 'El grupo elegido no es válido.',
            'id_padre.not_in' => 'Un grupo no puede estar dentro de sí mismo.',
        ]);

        $datos['id_padre'] = $datos['id_padre'] ?? null;
        $datos['activo'] = $request->boolean('activo');
        if ($datos['id_padre'] === null) {
            // Los grupos solo son títulos: sin ícono ni ruta.
            $datos['icono'] = null;
            $datos['ruta'] = null;
        }

        return $datos;
    }

    private function desactivarHijosSiEsGrupo(Menu $item): void
    {
        if ($item->id_padre === null && ! $item->activo) {
            $item->todosLosHijos()->update(['activo' => false]);
        }
    }
}
