<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaActivaController;
use Illuminate\Support\Facades\Route;

// Login, logout, recuperar y cambiar contraseña y verificación en dos pasos:
// las rutas las registra Fortify (config/fortify.php).

Route::redirect('/', '/sistema/dashboard');

Route::middleware('auth')->group(function () {

    // ── Módulos por dominio de negocio ──────────────────────────────
    require __DIR__.'/web/core.php';
    require __DIR__.'/web/catalogos.php';
    require __DIR__.'/web/inventario.php';
    require __DIR__.'/web/comercial.php';
    require __DIR__.'/web/finanzas.php';
    require __DIR__.'/web/rrhh.php';

    // ── Inicio ──────────────────────────────────────────────────────
    Route::get('/sistema/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Cambiar de empresa (cada usuario, entre las empresas a las que tiene acceso)
    Route::post('/sistema/empresa-activa', [EmpresaActivaController::class, 'cambiar'])->name('empresa-activa.cambiar');

    // ── Pantallas del menú que aún no existen (DEBE ir SIEMPRE al final) ──
    Route::get('/sistema/{any}', fn () => redirect()->route('dashboard')
        ->with('aviso', 'Esa pantalla todavía no está disponible.'))
        ->where('any', '.*');
});
