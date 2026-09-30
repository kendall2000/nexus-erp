<?php

namespace App\Http\Controllers;

use App\Models\Core\Accion;
use App\Models\Core\Modulo;
use App\Models\Core\Permiso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestión de módulos (solo Administrador), como en sistema-inventario: cada
 * módulo es una opción del menú lateral y define sus acciones (permisos
 * «modulo.accion»). Un módulo sin acciones solo lo ve el Administrador.
 */
class ModuloController extends Controller
{
    public function index(): View
    {
        $modulos = Modulo::query()->with(['permisos.accion'])->withCount('hijos')->orderBy('orden')->orderBy('id_modulo')->get();
        $asignaciones = DB::table('rol_permiso')->selectRaw('id_permiso, count(*) n')->groupBy('id_permiso')->pluck('n', 'id_permiso');

        return view('gestion-modulos.index', [
            // Primer nivel agrupado; los hijos se muestran debajo de su padre.
            'grupos' => $modulos->whereNull('id_modulo_padre')->groupBy(fn ($m) => $m->grupo ?: 'General'),
            'hijos' => $modulos->whereNotNull('id_modulo_padre')->groupBy('id_modulo_padre'),
            'asignaciones' => $asignaciones,
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario(new Modulo(['activo' => true, 'grupo' => $request->query('grupo'), 'icono' => 'chevrons-right']));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $modulo = DB::transaction(function () use ($request, $datos) {
            $modulo = Modulo::create($datos + ['orden' => $this->ordenAlFinal($datos)]);
            $this->sincronizarAcciones($request, $modulo);

            return $modulo;
        });

        return redirect()->route('modulos.index')->with('status', "Módulo «{$modulo->nombre}» creado.");
    }

    public function edit(int $modulo): View
    {
        return $this->formulario(Modulo::query()->with('permisos')->findOrFail($modulo));
    }

    public function update(Request $request, int $modulo): RedirectResponse
    {
        $modulo = Modulo::query()->findOrFail($modulo);
        $datos = $this->validar($request, $modulo);

        if ($datos['id_modulo_padre'] !== null && $modulo->hijos()->exists()) {
            return back()->withErrors(['id_modulo_padre' => 'Un módulo con submódulos no puede quedar dentro de otro.'])->withInput();
        }
        if ((int) $datos['id_modulo_padre'] !== (int) $modulo->id_modulo_padre || $datos['grupo'] !== $modulo->grupo) {
            $datos['orden'] = $this->ordenAlFinal($datos);
        }

        DB::transaction(function () use ($request, $modulo, $datos) {
            $modulo->update($datos);
            $this->sincronizarAcciones($request, $modulo);
        });

        return redirect()->route('modulos.index')->with('status', "Módulo «{$modulo->nombre}» actualizado.");
    }

    public function estado(int $modulo): RedirectResponse
    {
        $modulo = Modulo::query()->findOrFail($modulo);
        $modulo->update(['activo' => ! $modulo->activo]);

        return back()->with('status', "Módulo «{$modulo->nombre}» ".($modulo->activo ? 'activado.' : 'desactivado (ya no aparece en el menú ni da permisos).'));
    }

    /** Sube o baja un lugar entre los módulos de su mismo grupo (o de su mismo padre). */
    public function mover(Request $request, int $modulo): RedirectResponse
    {
        $request->validate(['direccion' => ['required', Rule::in(['subir', 'bajar'])]]);
        $modulo = Modulo::query()->findOrFail($modulo);

        DB::transaction(function () use ($request, $modulo) {
            $hermanos = $this->hermanos($modulo)->orderBy('orden')->orderBy('id_modulo')->pluck('id_modulo')->map(fn ($id) => (int) $id)->all();
            $i = array_search($modulo->id_modulo, $hermanos, true);
            $j = $request->input('direccion') === 'subir' ? $i - 1 : $i + 1;
            if ($j >= 0 && $j < count($hermanos)) {
                [$hermanos[$i], $hermanos[$j]] = [$hermanos[$j], $hermanos[$i]];
            }
            // Se conserva la base del grupo (centena) y se renumera 1..n.
            $base = intdiv((int) $modulo->orden, 100) * 100;
            foreach ($hermanos as $posicion => $id) {
                Modulo::whereKey($id)->update(['orden' => $base + $posicion + 1]);
            }
        });

        return back();
    }

    public function destroy(int $modulo): RedirectResponse
    {
        $modulo = Modulo::query()->withCount('hijos')->findOrFail($modulo);
        if ($modulo->hijos_count > 0) {
            return back()->withErrors(['modulo' => "«{$modulo->nombre}» tiene submódulos: elimínalos o muévelos primero."]);
        }

        // Sus permisos y las asignaciones a roles/usuarios se borran con él.
        DB::transaction(function () use ($modulo) {
            $ids = $modulo->permisos()->pluck('id_permiso');
            DB::table('rol_permiso')->whereIn('id_permiso', $ids)->delete();
            DB::table('usuario_permiso')->whereIn('id_permiso', $ids)->delete();
            Permiso::query()->whereIn('id_permiso', $ids)->delete();
            $modulo->delete();
        });

        return redirect()->route('modulos.index')->with('status', "Módulo «{$modulo->nombre}» eliminado.");
    }

    private function formulario(Modulo $modulo): View
    {
        return view('gestion-modulos.form', [
            'modulo' => $modulo,
            'acciones' => Accion::query()->where('activo', true)->orderBy('orden')->get(),
            'marcadas' => $modulo->exists ? $modulo->permisos->pluck('id_accion')->map(fn ($id) => (int) $id)->all() : [],
            'padres' => Modulo::query()->whereNull('id_modulo_padre')
                ->when($modulo->exists, fn ($q) => $q->whereKeyNot($modulo->id_modulo))->orderBy('orden')->get(),
            'grupos' => Modulo::query()->whereNotNull('grupo')->distinct()->orderBy('grupo')->pluck('grupo'),
        ]);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Modulo $modulo = null): array
    {
        $request->merge([
            'codigo' => strtolower(trim((string) $request->input('codigo'))),
            'nombre' => trim((string) $request->input('nombre')),
            'grupo' => ($g = trim((string) $request->input('grupo'))) === '' ? null : $g,
            'ruta' => ($r = trim((string) $request->input('ruta'))) === '' ? null : '/'.ltrim($r, '/'),
            'icono' => ($i = strtolower(trim((string) $request->input('icono')))) === '' ? null : $i,
        ]);

        $datos = $request->validate([
            // El código es parte de los permisos que usan las rutas: no se cambia después de crearlo.
            'codigo' => $modulo ? [] : ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*(\.[a-z0-9_]+)*$/', Rule::unique('modulo', 'codigo')],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'grupo' => ['nullable', 'string', 'max:60'],
            'icono' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/'],
            'ruta' => ['nullable', 'string', 'max:150', 'regex:#^/[A-Za-z0-9/_\-]*$#'],
            'id_modulo_padre' => ['nullable', 'integer', Rule::exists('modulo', 'id_modulo')->whereNull('id_modulo_padre'),
                ...($modulo ? [Rule::notIn([$modulo->id_modulo])] : [])],
            'acciones' => ['array'],
            'acciones.*' => ['integer', Rule::exists('accion', 'id_accion')],
        ], [
            'codigo.regex' => 'El código va en minúsculas, sin espacios ni tildes (p. ej. «bodegas» u «ordenes_compra»).',
            'icono.regex' => 'El ícono es el nombre de un ícono Feather: minúsculas, números y guiones (p. ej. «file-text»).',
            'ruta.regex' => 'La ruta debe ser interna, p. ej. /sistema/clientes (sin espacios ni direcciones externas).',
            'id_modulo_padre.exists' => 'El módulo padre no es válido (debe ser de primer nivel).',
        ]);

        unset($datos['acciones']);
        if ($modulo) {
            unset($datos['codigo']);
        }

        return $datos + ['id_modulo_padre' => null, 'activo' => $request->boolean('activo')];
    }

    /**
     * Crea los permisos de las acciones marcadas y elimina los de las desmarcadas
     * (con sus asignaciones a roles y usuarios).
     */
    private function sincronizarAcciones(Request $request, Modulo $modulo): void
    {
        $marcadas = array_map('intval', $request->input('acciones', []));
        $actuales = $modulo->permisos()->pluck('id_accion')->map(fn ($id) => (int) $id)->all();

        $quitar = $modulo->permisos()->whereIn('id_accion', array_diff($actuales, $marcadas))->pluck('id_permiso');
        DB::table('rol_permiso')->whereIn('id_permiso', $quitar)->delete();
        DB::table('usuario_permiso')->whereIn('id_permiso', $quitar)->delete();
        Permiso::query()->whereIn('id_permiso', $quitar)->delete();

        $nombres = Accion::query()->pluck('nombre', 'id_accion');
        foreach (array_diff($marcadas, $actuales) as $idAccion) {
            Permiso::create([
                'id_modulo' => $modulo->id_modulo, 'id_accion' => $idAccion,
                'descripcion' => $nombres[$idAccion].' '.mb_strtolower($modulo->nombre),
            ]);
        }
    }

    /** Módulos con el mismo padre (o, en primer nivel, el mismo grupo). */
    private function hermanos(Modulo $modulo)
    {
        return Modulo::query()
            ->when($modulo->id_modulo_padre, fn ($q) => $q->where('id_modulo_padre', $modulo->id_modulo_padre),
                fn ($q) => $q->whereNull('id_modulo_padre')->where('grupo', $modulo->grupo));
    }

    /** Siguiente orden al final de su grupo / padre. */
    private function ordenAlFinal(array $datos): int
    {
        $consulta = Modulo::query()->when($datos['id_modulo_padre'] ?? null,
            fn ($q) => $q->where('id_modulo_padre', $datos['id_modulo_padre']),
            fn ($q) => $q->whereNull('id_modulo_padre')->where('grupo', $datos['grupo'] ?? null));
        $max = (int) $consulta->max('orden');

        if ($max === 0) {
            // Grupo nuevo: después de todos los demás.
            $max = (intdiv((int) Modulo::query()->max('orden'), 100) + 1) * 100;
        }

        return $max + 1;
    }
}
