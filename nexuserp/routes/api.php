<?php

use App\Http\Controllers\Api\V1\Core\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Finanzas\FacturaController;
use App\Http\Controllers\Api\V1\Finanzas\PagoController;

// El inicio de sesión es por sesión web (Fortify: POST /login); ya no hay rutas públicas.

// ── Rutas PROTEGIDAS (sesión del navegador vía Sanctum) ───────────────
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get ('auth/me',               [AuthController::class, 'me']);

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
