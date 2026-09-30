<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permisos con la estructura de sistema-inventario (se rehacen desde cero: los
 * datos anteriores eran de prueba, autorizado por el usuario el 2026-09-30).
 *
 *  - modulo          = opción del menú lateral (grupo, ícono, ruta, orden, padre).
 *  - accion          = catálogo de acciones (ver, crear, editar…).
 *  - permiso         = módulo × acción. Código: «modulo.accion» (p. ej. «bodegas.crear»).
 *  - rol_permiso     = permisos de cada rol.
 *  - usuario_permiso = permisos extra de un usuario, además de los de su rol.
 *
 * Se eliminan las tablas anteriores: modulo_sistema, permiso, rol_permiso, menu y menu_rol.
 * Un módulo sin acciones es una pantalla solo del Administrador.
 */
return new class extends Migration
{
    private const ACCIONES = [
        'ver' => ['Ver', 'Ver y listar.'],
        'crear' => ['Crear', 'Crear registros nuevos.'],
        'editar' => ['Editar', 'Modificar, activar y desactivar registros.'],
        'eliminar' => ['Eliminar', 'Eliminar registros.'],
        'exportar' => ['Exportar', 'Exportar a Excel o PDF.'],
        'aprobar' => ['Aprobar', 'Aprobar (órdenes de compra, presupuestos…).'],
        'cancelar' => ['Cancelar', 'Cancelar documentos.'],
        'anular' => ['Anular', 'Anular documentos emitidos.'],
        'asignar' => ['Asignar', 'Asignar a otra persona.'],
        'cerrar' => ['Cerrar', 'Cerrar (tickets, periodos…).'],
        'procesar' => ['Procesar', 'Procesar (nómina…).'],
    ];

    private const CRUD = ['ver', 'crear', 'editar', 'eliminar'];

    /** grupo => [código => [nombre, ícono, ruta, acciones]] (en orden del menú) */
    private function modulos(): array
    {
        return [
            'CRM' => [
                'clientes' => ['Clientes', 'briefcase', '/sistema/clientes', [...self::CRUD, 'exportar']],
                'contratos' => ['Contratos', 'file-text', '/sistema/contratos', ['ver', 'crear', 'editar']],
                'prospectos' => ['Prospectos', 'target', '/sistema/prospectos', self::CRUD],
                'oportunidades' => ['Oportunidades', 'trending-up', '/sistema/oportunidades', ['ver', 'crear', 'editar']],
                'tickets' => ['Tickets', 'headphones', '/sistema/tickets', ['ver', 'crear', 'asignar', 'cerrar']],
                'campanas' => ['Campañas', 'mail', '/sistema/campanas', ['ver', 'crear']],
            ],
            'Recursos humanos' => [
                'empleados' => ['Empleados', 'users', '/sistema/empleados', self::CRUD],
                'nomina' => ['Nómina', 'dollar-sign', '/sistema/nomina', ['ver', 'procesar']],
                'asistencia' => ['Asistencia', 'clock', '/sistema/asistencia', ['ver', 'editar']],
            ],
            'Inventario' => [
                'productos' => ['Productos', 'package', '/sistema/productos', self::CRUD],
                'categorias' => ['Categorías', 'tag', '/sistema/categorias', self::CRUD],
                'bodegas' => ['Bodegas', 'archive', '/sistema/bodegas', self::CRUD],
                'movimientos' => ['Movimientos de inventario', 'repeat', null, ['ver', 'crear']],
            ],
            'Compras' => [
                'proveedores' => ['Proveedores', 'truck', '/sistema/proveedores', self::CRUD],
                'ordenes_compra' => ['Órdenes de compra', 'shopping-cart', '/sistema/ordenes-compra', ['ver', 'crear', 'editar', 'aprobar', 'cancelar']],
                'recepciones' => ['Recepciones', 'inbox', '/sistema/recepciones', ['ver', 'crear']],
            ],
            'Finanzas' => [
                'facturas' => ['Facturas', 'file-text', '/sistema/facturas', ['ver', 'crear', 'editar', 'anular']],
                'pagos' => ['Pagos', 'credit-card', '/sistema/pagos', ['ver', 'crear', 'eliminar']],
                'presupuesto' => ['Presupuesto', 'bar-chart-2', '/sistema/presupuesto', ['ver', 'crear', 'editar', 'aprobar']],
                'centros_costo' => ['Centros de costo', 'crosshair', '/sistema/centros-costo', self::CRUD],
                'cuentas_contables' => ['Cuentas contables', 'book', '/sistema/cuentas-contables', self::CRUD],
            ],
            'Configuración' => [
                'usuarios' => ['Usuarios', 'user', '/sistema/usuarios', self::CRUD],
                'roles' => ['Roles y permisos', 'shield', '/sistema/roles', self::CRUD],
                'sucursales' => ['Sucursales', 'layers', '/sistema/sucursales', []],
                'geografia' => ['Geografía', 'map-pin', '/sistema/geografia', []],
                'sistema' => ['Configuración del sistema', 'settings', '/sistema/configuracion', []],
                'modulos' => ['Módulos', 'grid', '/sistema/modulos', []],
            ],
        ];
    }

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['rol_permiso', 'permiso', 'modulo_sistema', 'menu_rol', 'menu'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('modulo', function (Blueprint $table) {
            $table->smallIncrements('id_modulo');
            $table->string('codigo', 50)->unique()->comment('Parte «modulo» del código de permiso: bodegas, ordenes_compra…');
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->string('icono', 50)->nullable()->comment('Ícono Feather');
            $table->string('ruta', 150)->nullable()->comment('Ruta en el menú lateral; vacía = no aparece en el menú');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->unsignedSmallInteger('id_modulo_padre')->nullable();
            $table->string('grupo', 60)->nullable()->comment('Encabezado del menú lateral (módulos de primer nivel)');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->foreign('id_modulo_padre')->references('id_modulo')->on('modulo')->restrictOnDelete();
            $table->index(['id_modulo_padre', 'orden'], 'idx_modulo_padre_orden');
        });

        Schema::create('accion', function (Blueprint $table) {
            $table->smallIncrements('id_accion');
            $table->string('codigo', 30)->unique()->comment('ver, crear, editar…');
            $table->string('nombre', 50);
            $table->string('descripcion', 255)->nullable();
            $table->unsignedTinyInteger('orden')->default(0)->comment('Orden de la columna en la matriz de permisos');
            $table->boolean('activo')->default(true);
        });

        Schema::create('permiso', function (Blueprint $table) {
            $table->smallIncrements('id_permiso');
            $table->unsignedSmallInteger('id_modulo');
            $table->unsignedSmallInteger('id_accion');
            $table->string('descripcion', 255)->nullable();
            $table->unique(['id_modulo', 'id_accion'], 'uq_permiso_modulo_accion');
            $table->foreign('id_modulo')->references('id_modulo')->on('modulo')->cascadeOnDelete();
            $table->foreign('id_accion')->references('id_accion')->on('accion')->restrictOnDelete();
        });

        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->unsignedSmallInteger('id_rol');
            $table->unsignedSmallInteger('id_permiso');
            $table->dateTime('asignado_at')->useCurrent();
            $table->primary(['id_rol', 'id_permiso']);
            $table->foreign('id_rol')->references('id_rol')->on('rol')->cascadeOnDelete();
            $table->foreign('id_permiso')->references('id_permiso')->on('permiso')->cascadeOnDelete();
        });

        Schema::create('usuario_permiso', function (Blueprint $table) {
            $table->unsignedInteger('id_usuario');
            $table->unsignedSmallInteger('id_permiso');
            $table->unsignedInteger('asignado_por')->nullable();
            $table->dateTime('asignado_at')->useCurrent();
            $table->primary(['id_usuario', 'id_permiso']);
            $table->index('id_permiso');
            $table->foreign('id_usuario')->references('id_usuario')->on('usuario')->cascadeOnDelete();
            $table->foreign('id_permiso')->references('id_permiso')->on('permiso')->cascadeOnDelete();
        });

        // ── Catálogo inicial ─────────────────────────────────────────────────
        $orden = 1;
        foreach (self::ACCIONES as $codigo => [$nombre, $descripcion]) {
            DB::table('accion')->insert(['codigo' => $codigo, 'nombre' => $nombre, 'descripcion' => $descripcion, 'orden' => $orden++]);
        }
        $acciones = DB::table('accion')->pluck('id_accion', 'codigo');

        $grupoOrden = 0;
        foreach ($this->modulos() as $grupo => $modulos) {
            $grupoOrden++;
            $i = 0;
            foreach ($modulos as $codigo => [$nombre, $icono, $ruta, $accionesModulo]) {
                $idModulo = DB::table('modulo')->insertGetId([
                    'codigo' => $codigo, 'nombre' => $nombre, 'icono' => $icono, 'ruta' => $ruta,
                    'grupo' => $grupo, 'orden' => $grupoOrden * 100 + ++$i, 'activo' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                foreach ($accionesModulo as $accion) {
                    DB::table('permiso')->insert([
                        'id_modulo' => $idModulo, 'id_accion' => $acciones[$accion],
                        'descripcion' => self::ACCIONES[$accion][0].' '.mb_strtolower($nombre),
                    ]);
                }
            }
        }
    }

    /** Reversa: quita las tablas nuevas y deja las anteriores vacías (sus datos eran de prueba). */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['usuario_permiso', 'rol_permiso', 'permiso', 'accion', 'modulo'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('modulo_sistema', function (Blueprint $table) {
            $table->smallIncrements('id_modulo');
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);
            $table->string('icono', 100)->nullable();
            $table->string('ruta_base', 200)->nullable();
            $table->unsignedTinyInteger('orden_menu')->default(0);
            $table->boolean('activo')->default(true);
        });
        Schema::create('permiso', function (Blueprint $table) {
            $table->smallIncrements('id_permiso');
            $table->unsignedSmallInteger('id_modulo');
            $table->string('codigo', 100)->unique();
            $table->string('descripcion', 200)->nullable();
            $table->foreign('id_modulo')->references('id_modulo')->on('modulo_sistema');
        });
        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->unsignedSmallInteger('id_rol');
            $table->unsignedSmallInteger('id_permiso');
            foreach (['crear', 'leer', 'editar', 'eliminar', 'exportar'] as $accion) {
                $table->boolean("puede_{$accion}")->default(false);
            }
            $table->primary(['id_rol', 'id_permiso']);
            $table->foreign('id_permiso')->references('id_permiso')->on('permiso');
        });
        Schema::create('menu', function (Blueprint $table) {
            $table->increments('id_menu');
            $table->unsignedInteger('id_empresa')->nullable();
            $table->unsignedInteger('id_padre')->nullable();
            $table->string('nombre', 100);
            $table->string('icono', 50)->nullable();
            $table->string('ruta', 200)->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->foreign('id_padre')->references('id_menu')->on('menu');
        });
        Schema::create('menu_rol', function (Blueprint $table) {
            $table->unsignedInteger('id_menu');
            $table->unsignedSmallInteger('id_rol');
            $table->foreign('id_menu')->references('id_menu')->on('menu');
        });
    }
};
