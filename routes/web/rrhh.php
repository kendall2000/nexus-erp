<?php

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\OrganizacionController;
use App\Http\Controllers\PrestacionController;
use App\Http\Controllers\RotativoController;
use Illuminate\Support\Facades\Route;

// Empleados (contrato laboral e historial salarial en la ficha)
Route::prefix('sistema/empleados')->name('empleados.')->controller(EmpleadoController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:empleados.ver')->name('index');
    Route::get('exportar', 'exportar')->middleware('permiso:empleados.exportar')->name('exportar');
    Route::get('nuevo', 'create')->middleware('permiso:empleados.crear')->name('create');
    Route::post('/', 'store')->middleware('permiso:empleados.crear')->name('store');
    Route::get('{empleado}', 'show')->whereNumber('empleado')->middleware('permiso:empleados.ver')->name('show');
    Route::middleware('permiso:empleados.editar')->group(function () {
        Route::get('{empleado}/editar', 'edit')->whereNumber('empleado')->name('edit');
        Route::put('{empleado}', 'update')->whereNumber('empleado')->name('update');
        Route::patch('{empleado}/baja', 'baja')->whereNumber('empleado')->name('baja');
        Route::patch('{empleado}/reactivar', 'reactivar')->whereNumber('empleado')->name('reactivar');
        Route::post('{empleado}/contrato', 'guardarContrato')->whereNumber('empleado')->name('contrato');
        Route::post('{empleado}/salario', 'cambiarSalario')->whereNumber('empleado')->name('salario');
        Route::post('{empleado}/documentos', 'guardarDocumento')->whereNumber('empleado')->name('documentos.store');
        Route::delete('{empleado}/documentos/{documento}', 'eliminarDocumento')->whereNumber(['empleado', 'documento'])->name('documentos.destroy');
    });
    Route::delete('{empleado}', 'destroy')->whereNumber('empleado')->middleware('permiso:empleados.eliminar')->name('destroy');
});

// Departamentos y cargos
Route::prefix('sistema/empleados/organizacion')->name('organizacion.')->controller(OrganizacionController::class)
    ->middleware('permiso:empleados.configurar')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('departamentos', 'guardarDepartamento')->name('departamentos.store');
        Route::put('departamentos/{departamento}', 'guardarDepartamento')->whereNumber('departamento')->name('departamentos.update');
        Route::delete('departamentos/{departamento}', 'eliminarDepartamento')->whereNumber('departamento')->name('departamentos.destroy');
        Route::post('cargos', 'guardarCargo')->name('cargos.store');
        Route::put('cargos/{cargo}', 'guardarCargo')->whereNumber('cargo')->name('cargos.update');
        Route::delete('cargos/{cargo}', 'eliminarCargo')->whereNumber('cargo')->name('cargos.destroy');
    });

// Nómina (periodos, cálculo, préstamos y boletas)
Route::prefix('sistema/nomina')->name('nomina.')->controller(NominaController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:nomina.ver')->name('index');
    Route::get('{periodo}', 'show')->whereNumber('periodo')->middleware('permiso:nomina.ver')->name('show');
    Route::get('{periodo}/boletas', 'imprimir')->whereNumber('periodo')->middleware('permiso:nomina.imprimir')->name('imprimir');
    Route::middleware('permiso:nomina.procesar')->group(function () {
        Route::post('/', 'store')->name('store');
        Route::patch('{periodo}/procesar', 'procesar')->whereNumber('periodo')->name('procesar');
        Route::patch('{periodo}/detalles/{detalle}', 'ajustar')->whereNumber(['periodo', 'detalle'])->name('ajustar');
        Route::delete('{periodo}/conceptos/{linea}', 'quitarConcepto')->whereNumber(['periodo', 'linea'])->name('conceptos.destroy');
        Route::patch('{periodo}/pagar', 'pagar')->whereNumber('periodo')->name('pagar');
        Route::post('prestamos', 'guardarPrestamo')->name('prestamos.store');
    });
    Route::patch('{periodo}/cerrar', 'cerrar')->whereNumber('periodo')->middleware('permiso:nomina.cerrar')->name('cerrar');
    Route::patch('{periodo}/reabrir', 'reabrir')->whereNumber('periodo')->middleware('permiso:nomina.reabrir')->name('reabrir');
});

// Asistencia diaria y solicitudes de ausencia
Route::prefix('sistema/asistencia')->name('asistencia.')->controller(AsistenciaController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:asistencia.ver')->name('index');
    Route::post('/', 'guardar')->middleware('permiso:asistencia.editar')->name('guardar');
    Route::post('solicitudes', 'solicitar')->middleware('permiso:asistencia.editar')->name('solicitudes.store');
    Route::patch('solicitudes/{solicitud}/{decision}', 'resolver')->whereNumber('solicitud')->whereIn('decision', ['aprobar', 'rechazar'])
        ->middleware('permiso:asistencia.aprobar')->name('solicitudes.resolver');
});

// Prestaciones laborales: aguinaldo, bono 14 y liquidaciones
Route::prefix('sistema/prestaciones')->name('prestaciones.')->controller(PrestacionController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:prestaciones.ver')->name('index');
    Route::get('exportar', 'exportar')->middleware('permiso:prestaciones.exportar')->name('exportar');
    Route::middleware('permiso:prestaciones.procesar')->group(function () {
        Route::post('calcular', 'calcular')->name('calcular');
        Route::post('liquidar', 'liquidar')->name('liquidar');
        Route::patch('lote/pagar', 'pagarLote')->name('lote.pagar');
        Route::patch('{prestacion}/pagar', 'pagar')->whereNumber('prestacion')->name('pagar');
        Route::delete('{prestacion}', 'destroy')->whereNumber('prestacion')->name('destroy');
    });
    Route::middleware('permiso:prestaciones.aprobar')->group(function () {
        Route::patch('lote/aprobar', 'aprobarLote')->name('lote.aprobar');
        Route::patch('{prestacion}/aprobar', 'aprobar')->whereNumber('prestacion')->name('aprobar');
    });
});

// Personal rotativo: coberturas pagadas por día (usa los permisos de Asistencia)
Route::prefix('sistema/asistencia/rotativos')->name('rotativos.')->controller(RotativoController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:asistencia.ver')->name('index');
    Route::post('/', 'store')->middleware('permiso:asistencia.editar')->name('store');
    Route::patch('{cobertura}/anular', 'anular')->whereNumber('cobertura')->middleware('permiso:asistencia.editar')->name('anular');
});
