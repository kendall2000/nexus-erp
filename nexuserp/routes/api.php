<?php

use App\Http\Controllers\Api\V1\Core\AuthController;
use App\Http\Controllers\Api\V1\Core\ConfiguracionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Core\MenuController;
use App\Http\Controllers\Api\V1\Core\DashboardController;
use App\Http\Controllers\Api\V1\Core\UsuarioController;
use App\Http\Controllers\Api\V1\Inventario\CategoriaProductoController;
use App\Http\Controllers\Api\V1\Core\RolController;
use App\Http\Controllers\Api\V1\Core\ConfiguracionSistemaController;
use App\Http\Controllers\Api\V1\Core\GestionMenuController;
use App\Http\Controllers\Api\V1\Core\SucursalController;
use App\Http\Controllers\Api\V1\Core\GeografiaController;
use App\Http\Controllers\Api\V1\Inventario\ProductoController;
use App\Http\Controllers\Api\V1\Inventario\BodegaController;
use App\Http\Controllers\Api\V1\Inventario\ProveedorController;
use App\Http\Controllers\Api\V1\Inventario\OrdenCompraController;
use App\Http\Controllers\Api\V1\Inventario\RecepcionController;
use App\Http\Controllers\Api\V1\Clientes\ClienteController;
use App\Http\Controllers\Api\V1\Finanzas\FacturaController;
use App\Http\Controllers\Api\V1\Finanzas\PagoController;

// ── Rutas PÚBLICAS (sin token) ───────────────────────────────
Route::prefix('v1')->group(function () {

    // Auth
    Route::post('auth/login', [AuthController::class, 'login']);

    // Configuración — pública para el login
    Route::get('configuracion/login',   [ConfiguracionController::class, 'login']);
    Route::get('configuracion/general', [ConfiguracionController::class, 'general']);

});

// ── Rutas PROTEGIDAS (requieren token Sanctum) ───────────────
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get ('auth/me',               [AuthController::class, 'me']);
    Route::post('auth/logout',           [AuthController::class, 'logout']);
    Route::post('auth/logout-all',       [AuthController::class, 'logoutAll']);
    Route::post('auth/refresh',          [AuthController::class, 'refresh']);
    Route::post('auth/cambiar-password', [AuthController::class, 'cambiarPassword']);

    // Menú dinámico
    Route::get ('menu',       [MenuController::class, 'index']);
    Route::get ('menu/todos', [MenuController::class, 'todos']);
    Route::post('menu',       [MenuController::class, 'store']);
    Route::put ('menu/{id}',  [MenuController::class, 'update']);
    Route::delete('menu/{id}',[MenuController::class, 'destroy']);

    // Dashboard
    Route::get('dashboard/resumen', [DashboardController::class, 'resumen']);

     // Usuarios
    Route::get('usuarios/catalogos',              [UsuarioController::class, 'catalogos']);
    Route::get('usuarios',                        [UsuarioController::class, 'index']);
    Route::post('usuarios',                        [UsuarioController::class, 'store']);
    Route::get('usuarios/{id}',                   [UsuarioController::class, 'show']);
    Route::put('usuarios/{id}',                   [UsuarioController::class, 'update']);
    Route::patch ('usuarios/{id}/toggle',            [UsuarioController::class, 'toggle']);
    Route::patch ('usuarios/{id}/reset-password',    [UsuarioController::class, 'resetPassword']);
    Route::delete('usuarios/{id}',                   [UsuarioController::class, 'destroy']);

    // Roles
    Route::get('roles/catalogos',           [RolController::class, 'catalogos']);
    Route::get('roles',                     [RolController::class, 'index']);
    Route::post('roles',                     [RolController::class, 'store']);
    Route::get('roles/{id}',                [RolController::class, 'show']);
    Route::put('roles/{id}',                [RolController::class, 'update']);
    Route::patch ('roles/{id}/toggle',         [RolController::class, 'toggle']);
    Route::post('roles/{id}/permisos',       [RolController::class, 'sincronizarPermisos']);
    Route::delete('roles/{id}',                [RolController::class, 'destroy']);
    Route::get ('roles/{id}/menu',  [RolController::class, 'permisosMenu']);
    Route::post('roles/{id}/menu',  [RolController::class, 'sincronizarMenu']);

    // Gestión de Configuracion
    Route::get('/core/configuracion', [ConfiguracionSistemaController::class, 'index']);
    Route::post('/core/configuracion', [ConfiguracionSistemaController::class, 'update']);

    // Gestión de Menú
    Route::get ('gestion-menu/catalogos', [GestionMenuController::class, 'catalogos']);
    Route::get ('gestion-menu/arbol',     [GestionMenuController::class, 'arbol']);     // ← nueva
    Route::get ('gestion-menu',           [GestionMenuController::class, 'index']);
    Route::post('gestion-menu',           [GestionMenuController::class, 'store']);
    Route::get ('gestion-menu/{id}',      [GestionMenuController::class, 'show']);
    Route::put ('gestion-menu/{id}',      [GestionMenuController::class, 'update']);
    Route::patch('gestion-menu/{id}/toggle', [GestionMenuController::class, 'toggle']);
    Route::delete('gestion-menu/{id}',    [GestionMenuController::class, 'destroy']);

    // ── Módulo Inventario ──────────────────────────────────────────
    // Bodegas 
    Route::get('inventario/bodegas/catalogos',    [BodegaController::class, 'catalogos']);
    Route::get('inventario/bodegas',              [BodegaController::class, 'index']);
    Route::post('inventario/bodegas',              [BodegaController::class, 'store']);
    Route::get('inventario/bodegas/{id}',         [BodegaController::class, 'show']);
    Route::put('inventario/bodegas/{id}',         [BodegaController::class, 'update']);
    Route::patch ('inventario/bodegas/{id}/toggle',  [BodegaController::class, 'toggle']);
    Route::delete('inventario/bodegas/{id}',         [BodegaController::class, 'destroy']);

    // Productos
    Route::get ('inventario/productos/catalogos',     [ProductoController::class, 'catalogos']); 
    Route::get ('inventario/productos',               [ProductoController::class, 'index']);
    Route::post('inventario/productos',               [ProductoController::class, 'store']);
    Route::get ('inventario/productos/{id}',          [ProductoController::class, 'show']);
    Route::put ('inventario/productos/{id}',          [ProductoController::class, 'update']);
    Route::patch('inventario/productos/{id}/toggle', [ProductoController::class, 'toggle']);
    Route::delete('inventario/productos/{id}',        [ProductoController::class, 'destroy']);

    // ── Inventario: Categorías ─────────────────────────────────────
    Route::prefix('inventario')->group(function () {
        Route::get('categorias/catalogos',     [CategoriaProductoController::class, 'catalogos']);
        Route::get('categorias',               [CategoriaProductoController::class, 'index']);
        Route::post('categorias',               [CategoriaProductoController::class, 'store']);
        Route::get('categorias/{id}',          [CategoriaProductoController::class, 'show']);
        Route::put('categorias/{id}',          [CategoriaProductoController::class, 'update']);
        Route::patch ('categorias/{id}/toggle',   [CategoriaProductoController::class, 'toggle']);
        Route::delete('categorias/{id}',          [CategoriaProductoController::class, 'destroy']);
    });

    // ── Geografía: catálogos read-only para selects en cascada ────
    Route::get('geografia/paises-activos',                 [GeografiaController::class, 'paisesActivos']);
    Route::get('geografia/divisiones/{idPais}',            [GeografiaController::class, 'divisionesPorPais']);
    Route::get('geografia/municipios/{idDivision}',        [GeografiaController::class, 'municipiosPorDivision']);

    // ── Geografía: CRUD países ────────────────────────────────────
    Route::get('geografia/paises',               [GeografiaController::class, 'paises']);
    Route::post('geografia/paises',               [GeografiaController::class, 'storePais']);
    Route::put('geografia/paises/{id}',          [GeografiaController::class, 'updatePais']);
    Route::patch ('geografia/paises/{id}/toggle',   [GeografiaController::class, 'togglePais']);
    Route::delete('geografia/paises/{id}',          [GeografiaController::class, 'destroyPais']);

    // ── Geografía: CRUD divisiones ────────────────────────────────
    Route::get('geografia/divisiones',                  [GeografiaController::class, 'divisiones']);
    Route::post('geografia/divisiones',                  [GeografiaController::class, 'storeDivision']);
    Route::put('geografia/divisiones/{id}',             [GeografiaController::class, 'updateDivision']);
    Route::patch ('geografia/divisiones/{id}/toggle',      [GeografiaController::class, 'toggleDivision']);
    Route::delete('geografia/divisiones/{id}',             [GeografiaController::class, 'destroyDivision']);

    // ── Geografía: CRUD municipios ────────────────────────────────
    Route::get('geografia/municipios',                  [GeografiaController::class, 'municipios']);
    Route::post('geografia/municipios',                  [GeografiaController::class, 'storeMunicipio']);
    Route::put('geografia/municipios/{id}',             [GeografiaController::class, 'updateMunicipio']);
    Route::patch ('geografia/municipios/{id}/toggle',      [GeografiaController::class, 'toggleMunicipio']);
    Route::delete('geografia/municipios/{id}',             [GeografiaController::class, 'destroyMunicipio']);
    Route::get('geografia/municipio/{id}/cascada', [GeografiaController::class, 'datosParaCascada']);

    
    // ── Sucursales ─────────────────────────────────────────────────
    Route::get('sucursales',             [SucursalController::class, 'index']);
    Route::post('sucursales',             [SucursalController::class, 'store']);
    Route::get('sucursales/{id}',        [SucursalController::class, 'show']);
    Route::put('sucursales/{id}',        [SucursalController::class, 'update']);
    Route::patch ('sucursales/{id}/toggle', [SucursalController::class, 'toggle']);
    Route::delete('sucursales/{id}',        [SucursalController::class, 'destroy']);

    // ── Proveedores ────────────────────────────────────────────────
    Route::get('inventario/proveedores/catalogos',    [ProveedorController::class, 'catalogos']);
    Route::get('inventario/proveedores',              [ProveedorController::class, 'index']);
    Route::post('inventario/proveedores',              [ProveedorController::class, 'store']);
    Route::get('inventario/proveedores/{id}',         [ProveedorController::class, 'show']);
    Route::put('inventario/proveedores/{id}',         [ProveedorController::class, 'update']);
    Route::patch ('inventario/proveedores/{id}/toggle',  [ProveedorController::class, 'toggle']);
    Route::delete('inventario/proveedores/{id}',         [ProveedorController::class, 'destroy']);

    // ── Órdenes de Compra ──────────────────────────────────────────
    Route::get('inventario/ordenes-compra/catalogos',     [OrdenCompraController::class, 'catalogos']);
    Route::get('inventario/ordenes-compra',               [OrdenCompraController::class, 'index']);
    Route::post('inventario/ordenes-compra',               [OrdenCompraController::class, 'store']);
    Route::get('inventario/ordenes-compra/{id}',          [OrdenCompraController::class, 'show']);
    Route::put('inventario/ordenes-compra/{id}',          [OrdenCompraController::class, 'update']);
    Route::patch ('inventario/ordenes-compra/{id}/aprobar',  [OrdenCompraController::class, 'aprobar']);
    Route::patch ('inventario/ordenes-compra/{id}/cancelar', [OrdenCompraController::class, 'cancelar']);
    Route::delete('inventario/ordenes-compra/{id}',          [OrdenCompraController::class, 'destroy']);

    // ── Recepciones de Mercadería ──────────────────────────────────
    Route::get('inventario/recepciones/catalogos',        [RecepcionController::class, 'catalogos']);
    Route::get('inventario/recepciones/oc/{idOC}/lineas', [RecepcionController::class, 'lineasPorOC']);
    Route::get('inventario/recepciones',                  [RecepcionController::class, 'index']);
    Route::post('inventario/recepciones',                 [RecepcionController::class, 'store']);
    Route::get('inventario/recepciones/{id}',             [RecepcionController::class, 'show']);

    // ── Clientes ───────────────────────────────────────────────────
    Route::get('clientes/clientes/catalogos',    [ClienteController::class, 'catalogos']);
    Route::get('clientes/clientes',              [ClienteController::class, 'index']);
    Route::post('clientes/clientes',              [ClienteController::class, 'store']);
    Route::get('clientes/clientes/{id}',         [ClienteController::class, 'show']);
    Route::put('clientes/clientes/{id}',         [ClienteController::class, 'update']);
    Route::patch ('clientes/clientes/{id}/toggle',  [ClienteController::class, 'toggle']);
    Route::delete('clientes/clientes/{id}',         [ClienteController::class, 'destroy']);

    // ── Facturas ───────────────────────────────────────────────────
    Route::get   ('finanzas/facturas/catalogos',           [FacturaController::class, 'catalogos']);
    Route::get   ('finanzas/facturas/contrato/{id}/lineas',[FacturaController::class, 'lineasContrato']);
    Route::get   ('finanzas/facturas',                     [FacturaController::class, 'index']);
    Route::post  ('finanzas/facturas',                     [FacturaController::class, 'store']);
    Route::get   ('finanzas/facturas/{id}',                [FacturaController::class, 'show']);
    Route::put   ('finanzas/facturas/{id}',                [FacturaController::class, 'update']);
    Route::patch ('finanzas/facturas/{id}/emitir',         [FacturaController::class, 'emitir']);
    Route::patch ('finanzas/facturas/{id}/anular',         [FacturaController::class, 'anular']);
    Route::patch ('finanzas/facturas/{id}/estado',         [FacturaController::class, 'cambiarEstado']);
    Route::delete('finanzas/facturas/{id}',                [FacturaController::class, 'destroy']);
    
    // ── Pagos ──────────────────────────────────────────────────────
    Route::prefix('finanzas/pagos')->group(function () {
        Route::get('catalogos', [PagoController::class, 'catalogos']);
        Route::get('',          [PagoController::class, 'index']);
        Route::post('',          [PagoController::class, 'store']);
        Route::get('{id}',      [PagoController::class, 'show']);
        Route::delete('{id}',    [PagoController::class, 'destroy']);
    });
});