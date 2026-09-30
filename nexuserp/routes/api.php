<?php

use App\Http\Controllers\Api\V1\Core\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Core\GeografiaController;
use App\Http\Controllers\Api\V1\Clientes\ClienteController;
use App\Http\Controllers\Api\V1\Finanzas\FacturaController;
use App\Http\Controllers\Api\V1\Finanzas\PagoController;
use App\Http\Controllers\Api\V1\Finanzas\PresupuestoController;

// El inicio de sesión es por sesión web (Fortify: POST /login); ya no hay rutas públicas.

// ── Rutas PROTEGIDAS (sesión del navegador vía Sanctum) ───────────────
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get ('auth/me',               [AuthController::class, 'me']);

    // ── Geografía: catálogos de solo lectura para los selects en cascada de Clientes
    //    (la administración está en /sistema/geografia) ────
    Route::get('geografia/divisiones/{idPais}',            [GeografiaController::class, 'divisionesPorPais']);
    Route::get('geografia/municipios/{idDivision}',        [GeografiaController::class, 'municipiosPorDivision']);

    Route::get('geografia/municipio/{id}/cascada', [GeografiaController::class, 'datosParaCascada']);

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

    // ── Presupuesto Anual ────────────────────────────────────────────
    Route::get   ('finanzas/presupuestos/catalogos',     [PresupuestoController::class, 'catalogos']);
    Route::get   ('finanzas/presupuestos/dashboard',     [PresupuestoController::class, 'dashboard']);
    Route::post  ('finanzas/presupuestos/clonar',        [PresupuestoController::class, 'clonar']);
    Route::get   ('finanzas/presupuestos',               [PresupuestoController::class, 'index']);
    Route::post  ('finanzas/presupuestos',               [PresupuestoController::class, 'store']);
    Route::get   ('finanzas/presupuestos/{id}',          [PresupuestoController::class, 'show']);
    Route::put   ('finanzas/presupuestos/{id}',          [PresupuestoController::class, 'update']);
    Route::patch ('finanzas/presupuestos/{id}/aprobar',  [PresupuestoController::class, 'aprobar']);
    Route::patch ('finanzas/presupuestos/{id}/cerrar',   [PresupuestoController::class, 'cerrar']);
    Route::delete('finanzas/presupuestos/{id}',          [PresupuestoController::class, 'destroy']);
});
