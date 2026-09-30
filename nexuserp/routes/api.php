<?php

use App\Http\Controllers\Api\V1\Core\AuthController;
use Illuminate\Support\Facades\Route;

// El inicio de sesión es por sesión web (Fortify: POST /login); ya no hay rutas públicas.

// ── Rutas PROTEGIDAS (sesión del navegador vía Sanctum) ───────────────
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get ('auth/me',               [AuthController::class, 'me']);
});
