<?php

use App\Http\Controllers\BodegaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CentroCostoController;
use App\Http\Controllers\CuentaContableController;
use App\Http\Controllers\LineaNegocioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\SerieFacturacionController;
use App\Http\Controllers\TipoServicioController;
use Illuminate\Support\Facades\Route;

// Catálogos: url => [controlador, parámetro, ruta «nuevo», ¿exporta?]. El permiso es la url con «_» (centros-costo → centros_costo.ver).
$catalogos = [
    'bodegas' => [BodegaController::class, 'bodega', 'nueva', false],
    'categorias' => [CategoriaController::class, 'categoria', 'nueva', false],
    'productos' => [ProductoController::class, 'producto', 'nuevo', true],
    'proveedores' => [ProveedorController::class, 'proveedor', 'nuevo', true],
    'centros-costo' => [CentroCostoController::class, 'centro', 'nuevo', true],
    'cuentas-contables' => [CuentaContableController::class, 'cuenta', 'nueva', true],
    'lineas-negocio' => [LineaNegocioController::class, 'linea', 'nueva', false],
    'tipos-servicio' => [TipoServicioController::class, 'servicio', 'nuevo', true],
    'series-facturacion' => [SerieFacturacionController::class, 'serie', 'nueva', false],
];

// Importar el plan de cuentas (va antes del bucle para que «importar» no choque con {cuenta}).
Route::prefix('sistema/cuentas-contables')->name('cuentas-contables.')->controller(CuentaContableController::class)
    ->middleware(['permiso:cuentas_contables.crear', 'permiso:cuentas_contables.editar'])->group(function () {
        Route::get('plantilla', 'plantilla')->name('plantilla');
        Route::get('importar', 'importarForm')->name('importar');
        Route::post('importar', 'importarPrevia')->middleware('throttle:20,1')->name('importar.previa');
        Route::post('importar/confirmar', 'importarConfirmar')->name('importar.confirmar');
        Route::post('importar/cancelar', 'importarCancelar')->name('importar.cancelar');
    });

foreach ($catalogos as $url => [$controlador, $parametro, $nuevo, $exporta]) {
    $modulo = str_replace('-', '_', $url);
    Route::prefix("sistema/{$url}")->name("{$url}.")->controller($controlador)->group(function () use ($modulo, $parametro, $nuevo, $exporta) {
        Route::get('/', 'index')->middleware("permiso:{$modulo}.ver")->name('index');
        if ($exporta) {
            Route::get('exportar', 'exportar')->middleware("permiso:{$modulo}.exportar")->name('exportar');
        }
        Route::get($nuevo, 'create')->middleware("permiso:{$modulo}.crear")->name('create');
        Route::post('/', 'store')->middleware("permiso:{$modulo}.crear")->name('store');
        Route::middleware("permiso:{$modulo}.editar")->group(function () use ($parametro) {
            Route::get("{{$parametro}}/editar", 'edit')->whereNumber($parametro)->name('edit');
            Route::put("{{$parametro}}", 'update')->whereNumber($parametro)->name('update');
            Route::patch("{{$parametro}}/estado", 'estado')->whereNumber($parametro)->name('estado');
        });
        Route::delete("{{$parametro}}", 'destroy')->whereNumber($parametro)->middleware("permiso:{$modulo}.eliminar")->name('destroy');
    });
}
