<?php

use App\Http\Controllers\Api\V1\Core\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Finanzas\PagoController;

// El inicio de sesión es por sesión web (Fortify: POST /login); ya no hay rutas públicas.

// ── Rutas PROTEGIDAS (sesión del navegador vía Sanctum) ───────────────
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get ('auth/me',               [AuthController::class, 'me']);

    // ── Pagos ──────────────────────────────────────────────────────
    Route::prefix('finanzas/pagos')->group(function () {
        Route::get('catalogos', [PagoController::class, 'catalogos']);
        Route::get('',          [PagoController::class, 'index']);
        Route::post('',          [PagoController::class, 'store']);
        Route::get('{id}',      [PagoController::class, 'show']);
        Route::delete('{id}',    [PagoController::class, 'destroy']);
    });
});
