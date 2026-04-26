<?php

use App\Http\Controllers\Api\V1\Core\AuthController;
use App\Http\Controllers\Api\V1\Core\ConfiguracionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Core\MenuController;
use App\Http\Controllers\Api\V1\Core\DashboardController;

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

});