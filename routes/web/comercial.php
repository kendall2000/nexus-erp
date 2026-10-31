<?php

use App\Http\Controllers\CampanaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\OportunidadController;
use App\Http\Controllers\ProspectoController;
use App\Http\Controllers\TicketConfiguracionController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

// Clientes (ficha con contactos y estado de cuenta)
Route::prefix('sistema/clientes')->name('clientes.')->controller(ClienteController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:clientes.ver')->name('index');
    Route::get('exportar', 'exportar')->middleware('permiso:clientes.exportar')->name('exportar');
    Route::get('nuevo', 'create')->middleware('permiso:clientes.crear')->name('create');
    Route::post('/', 'store')->middleware('permiso:clientes.crear')->name('store');
    Route::get('{cliente}', 'show')->whereNumber('cliente')->middleware('permiso:clientes.ver')->name('show');
    Route::get('{cliente}/imprimir', 'imprimir')->whereNumber('cliente')->middleware('permiso:clientes.imprimir')->name('imprimir');
    Route::middleware('permiso:clientes.editar')->group(function () {
        Route::get('{cliente}/editar', 'edit')->whereNumber('cliente')->name('edit');
        Route::put('{cliente}', 'update')->whereNumber('cliente')->name('update');
        Route::patch('{cliente}/estado', 'estado')->whereNumber('cliente')->name('estado');
        Route::post('{cliente}/contactos', 'guardarContacto')->whereNumber('cliente')->name('contactos.store');
        Route::put('{cliente}/contactos/{contacto}', 'guardarContacto')->whereNumber(['cliente', 'contacto'])->name('contactos.update');
        Route::delete('{cliente}/contactos/{contacto}', 'eliminarContacto')->whereNumber(['cliente', 'contacto'])->name('contactos.destroy');
    });
    Route::delete('{cliente}', 'destroy')->whereNumber('cliente')->middleware('permiso:clientes.eliminar')->name('destroy');
});

// Contratos de servicio (sitios del cliente y personal asignado en la ficha)
Route::prefix('sistema/contratos')->name('contratos.')->controller(ContratoController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:contratos.ver')->name('index');
    Route::get('nuevo', 'create')->middleware('permiso:contratos.crear')->name('create');
    Route::post('/', 'store')->middleware('permiso:contratos.crear')->name('store');
    Route::get('{contrato}', 'show')->whereNumber('contrato')->middleware('permiso:contratos.ver')->name('show');
    Route::get('{contrato}/imprimir', 'imprimir')->whereNumber('contrato')->middleware('permiso:contratos.imprimir')->name('imprimir');
    Route::patch('{contrato}/estado/{accion}', 'estado')->whereNumber('contrato')->whereAlpha('accion')->middleware('permiso:contratos.editar|contratos.cerrar|contratos.reabrir')->name('estado');
    Route::middleware('permiso:contratos.editar')->group(function () {
        Route::get('{contrato}/editar', 'edit')->whereNumber('contrato')->name('edit');
        Route::put('{contrato}', 'update')->whereNumber('contrato')->name('update');
        Route::post('{contrato}/sitios', 'guardarSitio')->whereNumber('contrato')->name('sitios.store');
        Route::post('{contrato}/asignaciones', 'asignar')->whereNumber('contrato')->name('asignaciones.store');
        Route::patch('{contrato}/asignaciones/{asignacion}/finalizar', 'finalizarAsignacion')->whereNumber(['contrato', 'asignacion'])->name('asignaciones.finalizar');
    });
});

// Tickets de servicio con SLA
Route::prefix('sistema/tickets/configuracion')->name('tickets.')->controller(TicketConfiguracionController::class)
    ->middleware('permiso:tickets.configurar')->group(function () {
        Route::get('/', 'index')->name('configuracion');
        Route::post('categorias', 'guardarCategoria')->name('categorias.store');
        Route::put('categorias/{categoria}', 'guardarCategoria')->whereNumber('categoria')->name('categorias.update');
        Route::post('slas', 'guardarSla')->name('slas.store');
        Route::put('slas/{sla}', 'guardarSla')->whereNumber('sla')->name('slas.update');
    });

Route::prefix('sistema/tickets')->name('tickets.')->controller(TicketController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:tickets.ver')->name('index');
    Route::get('nuevo', 'create')->middleware('permiso:tickets.crear')->name('create');
    Route::post('/', 'store')->middleware('permiso:tickets.crear')->name('store');
    Route::get('{ticket}', 'show')->whereNumber('ticket')->middleware('permiso:tickets.ver')->name('show');
    Route::post('{ticket}/responder', 'responder')->whereNumber('ticket')->middleware('permiso:tickets.editar')->name('responder');
    Route::patch('{ticket}/asignar', 'asignar')->whereNumber('ticket')->middleware('permiso:tickets.asignar')->name('asignar');
    Route::patch('{ticket}/cerrar', 'cerrar')->whereNumber('ticket')->middleware('permiso:tickets.cerrar')->name('cerrar');
    Route::patch('{ticket}/reabrir', 'reabrir')->whereNumber('ticket')->middleware('permiso:tickets.reabrir')->name('reabrir');
});

// CRM: prospectos, oportunidades (embudo y propuestas) y campañas
Route::prefix('sistema/prospectos')->name('prospectos.')->controller(ProspectoController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:prospectos.ver')->name('index');
    Route::get('nuevo', 'create')->middleware('permiso:prospectos.crear')->name('create');
    Route::post('/', 'store')->middleware('permiso:prospectos.crear')->name('store');
    Route::get('{prospecto}', 'show')->whereNumber('prospecto')->middleware('permiso:prospectos.ver')->name('show');
    Route::middleware('permiso:prospectos.editar')->group(function () {
        Route::get('{prospecto}/editar', 'edit')->whereNumber('prospecto')->name('edit');
        Route::put('{prospecto}', 'update')->whereNumber('prospecto')->name('update');
        Route::post('{prospecto}/seguimientos', 'seguimiento')->whereNumber('prospecto')->name('seguimiento');
        Route::patch('{prospecto}/descartar', 'descartar')->whereNumber('prospecto')->name('descartar');
        Route::post('{prospecto}/convertir', 'convertir')->whereNumber('prospecto')->name('convertir');
    });
    Route::delete('{prospecto}', 'destroy')->whereNumber('prospecto')->middleware('permiso:prospectos.eliminar')->name('destroy');
});

Route::prefix('sistema/oportunidades')->name('oportunidades.')->controller(OportunidadController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:oportunidades.ver')->name('index');
    Route::get('nueva', 'create')->middleware('permiso:oportunidades.crear')->name('create');
    Route::post('/', 'store')->middleware('permiso:oportunidades.crear')->name('store');
    Route::get('{oportunidad}', 'show')->whereNumber('oportunidad')->middleware('permiso:oportunidades.ver')->name('show');
    Route::middleware('permiso:oportunidades.editar')->group(function () {
        Route::get('{oportunidad}/editar', 'edit')->whereNumber('oportunidad')->name('edit');
        Route::put('{oportunidad}', 'update')->whereNumber('oportunidad')->name('update');
        Route::patch('{oportunidad}/etapa', 'mover')->whereNumber('oportunidad')->name('mover');
        Route::post('{oportunidad}/propuestas', 'guardarPropuesta')->whereNumber('oportunidad')->name('propuestas.store');
        Route::patch('{oportunidad}/propuestas/{propuesta}', 'estadoPropuesta')->whereNumber(['oportunidad', 'propuesta'])->name('propuestas.estado');
    });
});

Route::prefix('sistema/campanas')->name('campanas.')->controller(CampanaController::class)->group(function () {
    Route::get('/', 'index')->middleware('permiso:campanas.ver')->name('index');
    Route::get('nueva', 'create')->middleware('permiso:campanas.crear')->name('create');
    Route::post('/', 'store')->middleware('permiso:campanas.crear')->name('store');
    Route::get('{campana}', 'show')->whereNumber('campana')->middleware('permiso:campanas.ver')->name('show');
    Route::middleware('permiso:campanas.editar')->group(function () {
        Route::get('{campana}/editar', 'edit')->whereNumber('campana')->name('edit');
        Route::put('{campana}', 'update')->whereNumber('campana')->name('update');
        Route::post('{campana}/contactos', 'agregarContactos')->whereNumber('campana')->name('contactos.store');
        Route::patch('{campana}/contactos/{contacto}', 'estadoContacto')->whereNumber(['campana', 'contacto'])->name('contactos.update');
    });
});
