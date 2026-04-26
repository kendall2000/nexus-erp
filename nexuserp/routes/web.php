<?php
use Illuminate\Support\Facades\Route;

// Login
Route::get('/login', fn() => view('auth.login'))->name('login');

// Dashboard y módulos (protegidos por token en el frontend)
Route::get('/sistema/dashboard', fn() => view('modulos.dashboard.index'));

// Catch-all para futuras vistas
Route::get('/sistema/{any}', fn() => view('modulos.dashboard.index'))
    ->where('any', '.*');