<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/menu
    // Retorna el menú completo del usuario autenticado filtrado por empresa
    // ────────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $usuario   = $request->user();
        $idEmpresa = $usuario->id_empresa;

        // Obtiene los roles del usuario
        $idRoles = $usuario->roles()->pluck('rol.id_rol')->toArray();

        // Trae todos los grupos principales con sus hijos
        $grupos = Menu::with(['hijos' => function ($q) use ($idRoles) {
                        // Filtra hijos por rol del usuario
                        // Si el ítem no tiene roles asignados = visible para todos
                        $q->where('activo', true)
                          ->orderBy('orden')
                          ->where(function ($subQ) use ($idRoles) {
                              $subQ->whereDoesntHave('roles')
                                   ->orWhereHas('roles', fn($r) =>
                                       $r->whereIn('menu_rol.id_rol', $idRoles)
                                   );
                          });
                    }])
                    ->grupos($idEmpresa)
                    ->where(function ($q) use ($idRoles) {
                        // Grupos visibles si no tienen restricción de rol
                        $q->whereDoesntHave('roles')
                          ->orWhereHas('roles', fn($r) =>
                              $r->whereIn('menu_rol.id_rol', $idRoles)
                          );
                    })
                    ->get();

        // Formatea la respuesta para el frontend Vue
        $menuFormateado = $grupos->map(function ($grupo) {
            return [
                'id'     => $grupo->id_menu,
                'nombre' => $grupo->nombre,
                'items'  => $grupo->hijos->map(function ($item) {
                    return [
                        'id'       => $item->id_menu,
                        'nombre'   => $item->nombre,
                        'icono'    => $item->icono ?? 'chevrons-right',
                        'ruta'     => $item->ruta ?? '#',
                        'subitems' => [], // Futuro: tercer nivel
                    ];
                })->values(),
            ];
        })->filter(fn($g) => $g['items']->count() > 0) // Oculta grupos vacíos
          ->values();

        return response()->json([
            'success' => true,
            'data'    => $menuFormateado,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/menu/todos (solo admin — sin filtro de roles)
    // ────────────────────────────────────────────────────────────────────────
    public function todos(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $grupos = Menu::with(['hijos' => fn($q) =>
                        $q->where('activo', true)->orderBy('orden')
                    ])
                    ->grupos($idEmpresa)
                    ->get();

        $menuFormateado = $grupos->map(fn($grupo) => [
            'id'     => $grupo->id_menu,
            'nombre' => $grupo->nombre,
            'items'  => $grupo->hijos->map(fn($item) => [
                'id'       => $item->id_menu,
                'nombre'   => $item->nombre,
                'icono'    => $item->icono ?? 'chevrons-right',
                'ruta'     => $item->ruta ?? '#',
                'activo'   => $item->activo,
                'orden'    => $item->orden,
                'subitems' => [],
            ])->values(),
        ])->values();

        return response()->json([
            'success' => true,
            'data'    => $menuFormateado,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST /api/v1/menu (crear ítem)
    // ────────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre'     => 'required|string|max:100',
            'id_padre'   => 'nullable|exists:menu,id_menu',
            'icono'      => 'nullable|string|max:50',
            'ruta'       => 'nullable|string|max:200',
            'orden'      => 'integer|min:1',
        ]);

        $item = Menu::create([
            'id_empresa' => $request->user()->id_empresa,
            'id_padre'   => $request->id_padre,
            'nombre'     => $request->nombre,
            'icono'      => $request->icono ?? 'chevrons-right',
            'ruta'       => $request->ruta,
            'orden'      => $request->orden ?? 99,
            'activo'     => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ítem de menú creado correctamente.',
            'data'    => $item,
        ], 201);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PUT /api/v1/menu/{id} (actualizar ítem)
    // ────────────────────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $item = Menu::where('id_menu', $id)
                    ->where('id_empresa', $request->user()->id_empresa)
                    ->firstOrFail();

        $item->update($request->only([
            'nombre', 'icono', 'ruta', 'orden', 'activo', 'id_padre'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Ítem actualizado correctamente.',
            'data'    => $item,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // DELETE /api/v1/menu/{id} (desactivar ítem)
    // ────────────────────────────────────────────────────────────────────────
    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = Menu::where('id_menu', $id)
                    ->where('id_empresa', $request->user()->id_empresa)
                    ->firstOrFail();

        $item->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Ítem desactivado correctamente.',
        ]);
    }
}