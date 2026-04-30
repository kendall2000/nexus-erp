<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Rol;
use App\Models\Core\Permiso;
use App\Models\Core\ModuloSistema;
use App\Models\Core\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RolController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/roles
    // ────────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $roles = Rol::with(['permisos.modulo'])
            ->where('id_empresa', $idEmpresa)
            ->orderBy('nombre')
            ->get()
            ->map(fn($r) => [
                'id_rol'          => $r->id_rol,
                'nombre'          => $r->nombre,
                'descripcion'     => $r->descripcion ?? '—',
                'es_rol_sistema'  => $r->es_rol_sistema ? 'Sí' : 'No',
                'total_permisos'  => $r->permisos->count(),
                'total_usuarios'  => $r->usuarios()->count(),
                'activo'          => $r->activo ? 'Activo' : 'Inactivo',
                'created_at'      => $r->created_at?->format('d/m/Y'),
            ]);

        return response()->json(['success' => true, 'data' => $roles]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/roles/{id}
    // ────────────────────────────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $rol = Rol::with(['permisos'])
            ->where('id_empresa', $request->user()->id_empresa)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id_rol'         => $rol->id_rol,
                'nombre'         => $rol->nombre,
                'descripcion'    => $rol->descripcion,
                'es_rol_sistema' => $rol->es_rol_sistema,
                'activo'         => $rol->activo,
                'permisos'       => $rol->permisos->map(fn($p) => [
                    'id_permiso'      => $p->id_permiso,
                    'codigo'          => $p->codigo,
                    'descripcion'     => $p->descripcion,
                    'id_modulo'       => $p->id_modulo,
                    'puede_crear'     => (bool) $p->pivot->puede_crear,
                    'puede_leer'      => (bool) $p->pivot->puede_leer,
                    'puede_editar'    => (bool) $p->pivot->puede_editar,
                    'puede_eliminar'  => (bool) $p->pivot->puede_eliminar,
                    'puede_exportar'  => (bool) $p->pivot->puede_exportar,
                ]),
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST /api/v1/roles
    // ────────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'nombre'      => ['required','string','max:100',
                              Rule::unique('rol','nombre')->where('id_empresa', $idEmpresa)],
            'descripcion' => 'nullable|string|max:300',
        ], [
            'nombre.unique' => 'Ya existe un rol con ese nombre en esta empresa.',
        ]);

        $rol = Rol::create([
            'id_empresa'    => $idEmpresa,
            'nombre'        => $request->nombre,
            'descripcion'   => $request->descripcion,
            'es_rol_sistema'=> false,
            'activo'        => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rol creado correctamente.',
            'data'    => ['id_rol' => $rol->id_rol],
        ], 201);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PUT /api/v1/roles/{id}
    // ────────────────────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $rol       = Rol::where('id_empresa', $idEmpresa)->findOrFail($id);

        if ($rol->es_rol_sistema) {
            return response()->json([
                'success' => false,
                'message' => 'Los roles del sistema no pueden modificarse.',
            ], 403);
        }

        $request->validate([
            'nombre'      => ['required','string','max:100',
                              Rule::unique('rol','nombre')
                                  ->where('id_empresa', $idEmpresa)
                                  ->ignore($id, 'id_rol')],
            'descripcion' => 'nullable|string|max:300',
        ]);

        $rol->update([
            'nombre'      => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado correctamente.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PATCH /api/v1/roles/{id}/toggle
    // ────────────────────────────────────────────────────────────────────────
    public function toggle(Request $request, int $id): JsonResponse
    {
        $rol = Rol::where('id_empresa', $request->user()->id_empresa)->findOrFail($id);

        if ($rol->es_rol_sistema) {
            return response()->json([
                'success' => false,
                'message' => 'Los roles del sistema no pueden desactivarse.',
            ], 403);
        }

        $rol->update(['activo' => !$rol->activo]);

        return response()->json([
            'success' => true,
            'message' => $rol->activo ? 'Rol activado.' : 'Rol desactivado.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // DELETE /api/v1/roles/{id}
    // ────────────────────────────────────────────────────────────────────────
    public function destroy(Request $request, int $id): JsonResponse
    {
        $rol = Rol::where('id_empresa', $request->user()->id_empresa)->findOrFail($id);

        if ($rol->es_rol_sistema) {
            return response()->json([
                'success' => false,
                'message' => 'Los roles del sistema no pueden eliminarse.',
            ], 403);
        }

        if ($rol->usuarios()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un rol que tiene usuarios asignados.',
            ], 422);
        }

        $rol->permisos()->detach();
        $rol->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado correctamente.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST /api/v1/roles/{id}/permisos
    // Sincroniza la matriz de permisos CRUD del rol
    // ────────────────────────────────────────────────────────────────────────
    public function sincronizarPermisos(Request $request, int $id): JsonResponse
    {
        $rol = Rol::where('id_empresa', $request->user()->id_empresa)->findOrFail($id);

        if ($rol->es_rol_sistema) {
            return response()->json([
                'success' => false,
                'message' => 'Los permisos de roles del sistema no pueden modificarse.',
            ], 403);
        }

        // $request->permisos = [
        //   { id_permiso: 1, puede_crear: true, puede_leer: true, ... },
        //   ...
        // ]
        $request->validate([
            'permisos'                  => 'array',
            'permisos.*.id_permiso'     => 'required|exists:permiso,id_permiso',
            'permisos.*.puede_crear'    => 'boolean',
            'permisos.*.puede_leer'     => 'boolean',
            'permisos.*.puede_editar'   => 'boolean',
            'permisos.*.puede_eliminar' => 'boolean',
            'permisos.*.puede_exportar' => 'boolean',
        ]);

        $sync = [];
        foreach ($request->permisos as $p) {
            $sync[$p['id_permiso']] = [
                'puede_crear'    => $p['puede_crear']    ?? false,
                'puede_leer'     => $p['puede_leer']     ?? true,
                'puede_editar'   => $p['puede_editar']   ?? false,
                'puede_eliminar' => $p['puede_eliminar'] ?? false,
                'puede_exportar' => $p['puede_exportar'] ?? false,
            ];
        }

        $rol->permisos()->sync($sync);

        return response()->json([
            'success' => true,
            'message' => 'Permisos actualizados correctamente.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/roles/catalogos
    // Módulos y permisos para la matriz
    // ────────────────────────────────────────────────────────────────────────
    public function catalogos(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        // Grupos del menú
        $grupos = Menu::with(['hijos' => fn($q) =>
                        $q->where('activo', true)->orderBy('orden')
                    ])
                    ->whereNull('id_padre')
                    ->where('id_empresa', $idEmpresa)
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->get()
                    ->map(fn($g) => [
                        'id_grupo' => $g->id_menu,
                        'nombre'   => $g->nombre,
                        'icono'    => $g->icono ?? 'grid',
                        'items'    => $g->hijos->map(fn($i) => [
                            'id_menu' => $i->id_menu,
                            'nombre'  => $i->nombre,
                            'icono'   => $i->icono ?? 'chevrons-right',
                            'ruta'    => $i->ruta,
                        ]),
                    ])
                    ->filter(fn($g) => count($g['items']) > 0)
                    ->values();

        // Módulos con sus permisos
        $modulos = ModuloSistema::with(['permisos'])
                    ->where('activo', true)
                    ->orderBy('orden_menu')
                    ->get()
                    ->map(fn($m) => [
                        'id_modulo' => $m->id_modulo,
                        'nombre'    => $m->nombre,
                        'icono'     => $m->icono,
                        'permisos'  => $m->permisos->map(fn($p) => [
                            'id_permiso'  => $p->id_permiso,
                            'codigo'      => $p->codigo,
                            'descripcion' => $p->descripcion,
                        ]),
                    ])
                    ->filter(fn($m) => $m['permisos']->count() > 0)
                    ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'grupos'  => $grupos,
                'modulos' => $modulos,
            ],
        ]);
    }
    public function permisosMenu(Request $request, int $id): JsonResponse
    {
        $rol = Rol::where('id_empresa', $request->user()->id_empresa)
                ->findOrFail($id);

        $menuIds = DB::table('menu_rol')
                    ->where('id_rol', $id)
                    ->pluck('id_menu')
                    ->toArray();

        return response()->json([
            'success' => true,
            'data'    => $menuIds,
        ]);
    }

    public function sincronizarMenu(Request $request, int $id): JsonResponse
    {
        $rol = Rol::where('id_empresa', $request->user()->id_empresa)
                ->findOrFail($id);

        $request->validate([
            'menu_ids'   => 'array',
            'menu_ids.*' => 'exists:menu,id_menu',
        ]);

        DB::table('menu_rol')->where('id_rol', $id)->delete();

        if (!empty($request->menu_ids)) {
            $insert = array_map(fn($idMenu) => [
                'id_menu' => $idMenu,
                'id_rol'  => $id,
            ], $request->menu_ids);

            DB::table('menu_rol')->insert($insert);
        }

        return response()->json([
            'success' => true,
            'message' => 'Acceso al menú actualizado correctamente.',
        ]);
    }
}