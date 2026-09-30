<?php

namespace Tests\Concerns;

use App\Models\Core\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * El esquema de Nexus no está en migraciones: aquí se crean en SQLite (memoria)
 * las tablas mínimas que usan las pruebas, más ayudantes para crear usuarios.
 */
trait EsquemaNexus
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
    }

    protected function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $t) {
            $t->increments('id_usuario');
            $t->unsignedInteger('id_empresa')->nullable();
            $t->unsignedInteger('id_sucursal')->nullable();
            $t->string('username', 60);
            $t->string('email', 150)->nullable();
            $t->string('password_hash');
            $t->string('remember_token', 100)->nullable();
            $t->text('two_factor_secret')->nullable();
            $t->text('two_factor_recovery_codes')->nullable();
            $t->timestamp('two_factor_confirmed_at')->nullable();
            $t->string('nombre_completo')->nullable();
            $t->string('avatar_url')->nullable();
            $t->dateTime('ultimo_login')->nullable();
            $t->integer('intentos_fallidos')->default(0);
            $t->dateTime('bloqueado_hasta')->nullable();
            $t->string('token_reset')->nullable();
            $t->dateTime('token_reset_exp')->nullable();
            $t->boolean('activo')->default(true);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('rol', function (Blueprint $t) {
            $t->increments('id_rol');
            $t->unsignedInteger('id_empresa')->nullable();
            $t->string('nombre');
            $t->string('descripcion')->nullable();
            $t->boolean('es_rol_sistema')->default(false);
            $t->boolean('activo')->default(true);
            $t->boolean('requiere_2fa')->default(false);
            $t->dateTime('created_at')->nullable();
        });
        Schema::create('usuario_rol', function (Blueprint $t) {
            $t->unsignedInteger('id_usuario');
            $t->unsignedInteger('id_rol');
            $t->dateTime('fecha_asignacion')->nullable();
            $t->unsignedInteger('asignado_por')->nullable();
        });
        // Permisos (estructura de sistema-inventario): módulo × acción, por rol y extras por usuario.
        Schema::create('modulo', function (Blueprint $t) {
            $t->increments('id_modulo');
            $t->string('codigo')->unique();
            $t->string('nombre');
            $t->string('descripcion')->nullable();
            $t->string('icono')->nullable();
            $t->string('ruta')->nullable();
            $t->unsignedInteger('orden')->default(0);
            $t->unsignedInteger('id_modulo_padre')->nullable();
            $t->string('grupo')->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
        Schema::create('accion', function (Blueprint $t) {
            $t->increments('id_accion');
            $t->string('codigo')->unique();
            $t->string('nombre');
            $t->string('descripcion')->nullable();
            $t->unsignedInteger('orden')->default(0);
            $t->boolean('activo')->default(true);
        });
        Schema::create('permiso', function (Blueprint $t) {
            $t->increments('id_permiso');
            $t->unsignedInteger('id_modulo');
            $t->unsignedInteger('id_accion');
            $t->string('descripcion')->nullable();
            $t->unique(['id_modulo', 'id_accion']);
        });
        Schema::create('rol_permiso', function (Blueprint $t) {
            $t->unsignedInteger('id_rol');
            $t->unsignedInteger('id_permiso');
            $t->dateTime('asignado_at')->useCurrent();
            $t->primary(['id_rol', 'id_permiso']);
        });
        Schema::create('usuario_permiso', function (Blueprint $t) {
            $t->unsignedInteger('id_usuario');
            $t->unsignedInteger('id_permiso');
            $t->unsignedInteger('asignado_por')->nullable();
            $t->dateTime('asignado_at')->useCurrent();
            $t->primary(['id_usuario', 'id_permiso']);
        });
        Schema::create('auditoria_acceso', function (Blueprint $t) {
            $t->bigIncrements('id_auditoria');
            $t->unsignedInteger('id_usuario')->nullable();
            $t->string('username_intento', 60)->nullable();
            $t->string('accion');
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent', 500)->nullable();
            $t->string('detalle')->nullable();
            $t->dateTime('created_at')->useCurrent();
        });
        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->unsignedInteger('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('sucursal', function (Blueprint $t) {
            $t->increments('id_sucursal');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_pais')->nullable();
            $t->unsignedInteger('id_division')->nullable();
            $t->unsignedInteger('id_municipio')->nullable();
            $t->string('nombre');
            $t->string('direccion')->nullable();
            $t->string('telefono')->nullable();
            $t->string('email')->nullable();
            $t->boolean('es_casa_matriz')->default(false);
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
        // Geografía (catálogo compartido).
        Schema::create('pais', function (Blueprint $t) {
            $t->increments('id_pais');
            $t->string('codigo_iso2', 2)->nullable();
            $t->string('codigo_iso3', 3)->nullable();
            $t->string('nombre');
            $t->string('prefijo_tel')->nullable();
            $t->string('moneda_defecto')->nullable();
            $t->boolean('activo')->default(true);
        });
        Schema::create('division_geografica', function (Blueprint $t) {
            $t->increments('id_division');
            $t->unsignedInteger('id_pais');
            $t->string('nombre');
            $t->string('tipo')->nullable();
            $t->boolean('activo')->default(true);
        });
        Schema::create('municipio', function (Blueprint $t) {
            $t->increments('id_municipio');
            $t->unsignedInteger('id_division');
            $t->string('nombre');
            $t->boolean('activo')->default(true);
        });
        Schema::create('empresa', function (Blueprint $t) {
            $t->increments('id_empresa');
            $t->string('nombre_comercial')->nullable();
        });
        DB::table('empresa')->insert(['id_empresa' => 1, 'nombre_comercial' => 'Empresa Demo']);
        foreach (['cliente' => 'id_cliente', 'empleado' => 'id_empleado', 'contrato_servicio' => 'id_contrato', 'ticket' => 'id_ticket'] as $tabla => $llave) {
            Schema::create($tabla, function (Blueprint $t) use ($llave) {
                $t->increments($llave);
                $t->unsignedInteger('id_empresa');
                $t->boolean('activo')->default(true);
                $t->string('estado')->nullable();
                $t->softDeletes();
            });
        }
        Schema::table('cliente', function (Blueprint $t) {
            $t->unsignedInteger('id_industria')->nullable();
            $t->unsignedInteger('id_pais')->nullable();
            $t->unsignedInteger('id_municipio')->nullable();
            $t->string('razon_social')->nullable();
            $t->string('nombre_comercial')->nullable();
            $t->string('nit', 20)->nullable();
            $t->string('tipo_persona')->default('JURIDICA');
            $t->string('email_principal')->nullable();
            $t->string('telefono_principal')->nullable();
            $t->string('sitio_web')->nullable();
            $t->string('direccion_fiscal')->nullable();
            $t->string('segmento')->nullable();
            $t->string('categoria')->nullable();
            $t->string('moneda_facturacion', 3)->default('GTQ');
            $t->unsignedTinyInteger('dias_credito')->default(30);
            $t->decimal('limite_credito', 15, 4)->nullable();
            $t->unsignedInteger('created_by')->nullable();
            $t->unsignedInteger('updated_by')->nullable();
            $t->timestamps();
        });
        Schema::create('contacto_cliente', function (Blueprint $t) {
            $t->increments('id_contacto');
            $t->unsignedInteger('id_cliente');
            $t->string('nombre');
            $t->string('cargo')->nullable();
            $t->string('email')->nullable();
            $t->string('telefono')->nullable();
            $t->string('whatsapp')->nullable();
            $t->boolean('es_contacto_principal')->default(false);
            $t->boolean('recibe_facturas')->default(false);
            $t->boolean('activo')->default(true);
            $t->dateTime('created_at')->useCurrent();
        });
        Schema::create('industria', function (Blueprint $t) {
            $t->increments('id_industria');
            $t->string('nombre');
        });
        Schema::create('factura', function (Blueprint $t) {
            $t->increments('id_factura');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_cliente');
            $t->unsignedInteger('id_contrato')->nullable();
            $t->unsignedInteger('id_serie')->nullable();
            $t->unsignedInteger('numero_factura')->nullable();
            $t->string('tipo')->default('FACTURA');
            $t->date('periodo_servicio_inicio')->nullable();
            $t->date('periodo_servicio_fin')->nullable();
            $t->decimal('subtotal', 15, 4)->default(0);
            $t->decimal('descuento', 15, 4)->default(0);
            $t->decimal('base_imponible', 15, 4)->default(0);
            $t->decimal('iva', 15, 4)->default(0);
            $t->string('uuid_fel')->nullable();
            $t->string('numero_autorizacion_fel')->nullable();
            $t->text('notas')->nullable();
            $t->unsignedInteger('created_by')->nullable();
            $t->unsignedInteger('anulada_por')->nullable();
            $t->dateTime('fecha_anulacion')->nullable();
            $t->string('numero_completo', 30)->nullable();
            $t->date('fecha_emision')->nullable();
            $t->date('fecha_vencimiento')->nullable();
            $t->string('moneda', 3)->default('GTQ');
            $t->decimal('total', 15, 4)->default(0);
            $t->decimal('total_pagado', 15, 4)->default(0);
            $t->decimal('monto_condonado', 15, 4)->default(0);
            $t->unsignedInteger('condonado_por')->nullable();
            $t->dateTime('fecha_condonacion')->nullable();
            $t->decimal('saldo_pendiente', 15, 4)->default(0);
            $t->string('estado')->default('BORRADOR');
            $t->timestamps();
        });
        Schema::create('pago', function (Blueprint $t) {
            $t->increments('id_pago');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_cliente')->nullable();
            $t->unsignedInteger('id_factura')->nullable();
            $t->string('referencia')->nullable();
            $t->string('forma_pago')->default('EFECTIVO');
            $t->decimal('monto', 15, 4)->default(0);
            $t->string('moneda', 3)->default('GTQ');
            $t->date('fecha_pago')->nullable();
            $t->date('fecha_acreditado')->nullable();
            $t->string('banco_origen')->nullable();
            $t->string('comprobante_url')->nullable();
            $t->string('notas')->nullable();
            $t->string('estado')->default('APLICADO');
            $t->unsignedInteger('revertido_por')->nullable();
            $t->dateTime('fecha_reversion')->nullable();
            $t->string('motivo_reversion')->nullable();
            $t->unsignedInteger('created_by')->nullable();
            $t->dateTime('created_at')->useCurrent();
        });
        Schema::create('serie_facturacion', function (Blueprint $t) {
            $t->increments('id_serie');
            $t->unsignedInteger('id_empresa');
            $t->string('codigo_serie', 10);
            $t->string('tipo');
            $t->string('descripcion')->nullable();
            $t->unsignedInteger('ultimo_numero')->default(0);
            $t->boolean('activo')->default(true);
        });
        Schema::create('detalle_factura', function (Blueprint $t) {
            $t->increments('id_linea');
            $t->unsignedInteger('id_factura');
            $t->unsignedInteger('id_tipo_servicio')->nullable();
            $t->string('descripcion', 300);
            $t->decimal('cantidad', 10, 2)->default(1);
            $t->decimal('precio_unitario', 15, 4);
            $t->decimal('descuento', 15, 4)->default(0);
            $t->decimal('subtotal', 15, 4);
            $t->boolean('es_afecto_iva')->default(true);
            $t->unsignedInteger('id_cuenta')->nullable();
            $t->unsignedInteger('id_centro')->nullable();
        });
        Schema::create('linea_negocio', function (Blueprint $t) {
            $t->increments('id_linea');
            $t->unsignedInteger('id_empresa');
            $t->string('nombre');
            $t->text('descripcion')->nullable();
            $t->boolean('activo')->default(true);
        });
        Schema::create('tipo_servicio', function (Blueprint $t) {
            $t->increments('id_tipo_servicio');
            $t->unsignedInteger('id_linea');
            $t->string('nombre');
            $t->text('descripcion')->nullable();
            $t->string('unidad_medida')->default('MES');
            $t->decimal('precio_base', 15, 4)->nullable();
            $t->string('moneda', 3)->default('GTQ');
            $t->boolean('activo')->default(true);
            $t->unsignedInteger('id_cuenta_ingreso')->nullable();
            $t->unsignedInteger('id_centro_default')->nullable();
        });
        Schema::table('ticket', function (Blueprint $t) {
            foreach (['id_cliente', 'id_contrato', 'id_categoria', 'id_asignado_a', 'id_sla', 'sla_primera_respuesta_hrs', 'sla_resolucion_hrs', 'calificacion_cliente', 'created_by', 'updated_by'] as $c) {
                $t->unsignedInteger($c)->nullable();
            }
            foreach (['numero_ticket', 'asunto', 'canal_origen', 'prioridad', 'tipo', 'comentario_calificacion'] as $c) {
                $t->string($c)->nullable();
            }
            $t->text('descripcion')->nullable();
            foreach (['fecha_apertura', 'fecha_limite_respuesta', 'fecha_limite_resolucion', 'fecha_primera_respuesta', 'fecha_resolucion', 'fecha_cierre'] as $c) {
                $t->dateTime($c)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('ticket_comentario', function (Blueprint $t) {
            $t->increments('id_comentario');
            $t->unsignedInteger('id_ticket');
            $t->unsignedInteger('id_autor')->nullable();
            $t->unsignedInteger('id_usuario')->nullable();
            $t->boolean('es_nota_interna')->default(false);
            $t->text('contenido');
            $t->dateTime('created_at')->useCurrent();
        });
        Schema::create('escalacion_ticket', function (Blueprint $t) {
            $t->increments('id_escalacion');
            $t->unsignedInteger('id_ticket');
            $t->unsignedInteger('escalado_por')->nullable();
            $t->unsignedInteger('escalado_a');
            $t->unsignedInteger('id_usuario')->nullable();
            $t->string('motivo', 500);
            $t->unsignedTinyInteger('nivel')->default(1);
            $t->dateTime('created_at')->useCurrent();
        });
        Schema::create('categoria_ticket', function (Blueprint $t) {
            $t->increments('id_categoria');
            $t->unsignedInteger('id_empresa');
            $t->string('nombre');
            $t->string('descripcion')->nullable();
            $t->string('prioridad_default')->default('MEDIA');
            $t->boolean('activo')->default(true);
        });
        Schema::create('sla_config', function (Blueprint $t) {
            $t->increments('id_sla');
            $t->unsignedInteger('id_empresa');
            $t->string('nombre');
            $t->string('prioridad');
            $t->unsignedTinyInteger('tiempo_primera_respuesta_hrs');
            $t->unsignedSmallInteger('tiempo_resolucion_hrs');
            $t->boolean('aplica_fines_semana')->default(false);
            $t->boolean('activo')->default(true);
        });
        Schema::create('evaluacion_satisfaccion', function (Blueprint $t) {
            $t->increments('id_evaluacion');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_cliente');
            $t->unsignedInteger('id_contrato')->nullable();
            $t->unsignedInteger('id_ticket')->nullable();
            $t->string('tipo')->default('CSAT');
            $t->unsignedTinyInteger('puntuacion');
            $t->text('comentarios')->nullable();
            $t->string('canal')->default('EMAIL');
            $t->dateTime('fecha_respuesta')->nullable();
        });
        Schema::table('contrato_servicio', function (Blueprint $t) {
            $t->unsignedInteger('id_cliente')->nullable();
            $t->unsignedInteger('id_vendedor')->nullable();
            $t->string('numero_contrato', 60)->nullable();
            $t->string('nombre_proyecto')->nullable();
            $t->date('fecha_inicio')->nullable();
            $t->date('fecha_fin')->nullable();
            $t->date('fecha_firma')->nullable();
            $t->decimal('valor_mensual', 15, 4)->default(0);
            $t->decimal('valor_total_estimado', 15, 4)->nullable();
            $t->string('moneda', 3)->default('GTQ');
            $t->string('periodicidad_factura')->default('MENSUAL');
            $t->unsignedTinyInteger('dia_facturacion')->nullable();
            $t->string('url_contrato')->nullable();
            $t->text('notas')->nullable();
            $t->unsignedInteger('created_by')->nullable();
            $t->unsignedInteger('updated_by')->nullable();
            $t->timestamps();
        });
        Schema::create('contrato_servicio_detalle', function (Blueprint $t) {
            $t->increments('id_detalle');
            $t->unsignedInteger('id_contrato');
            $t->unsignedInteger('id_tipo_servicio');
            $t->unsignedInteger('id_sitio')->nullable();
            $t->string('descripcion', 300)->nullable();
            $t->decimal('cantidad', 10, 2)->default(1);
            $t->decimal('precio_unitario', 15, 4);
            $t->decimal('descuento_pct', 5, 2)->default(0);
            $t->decimal('subtotal', 15, 4);
        });
        Schema::create('sitio_trabajo', function (Blueprint $t) {
            $t->increments('id_sitio');
            $t->unsignedInteger('id_cliente');
            $t->unsignedInteger('id_municipio')->nullable();
            $t->string('nombre');
            $t->string('direccion');
            $t->decimal('latitud', 10, 7)->nullable();
            $t->decimal('longitud', 10, 7)->nullable();
            $t->string('responsable_cliente')->nullable();
            $t->string('tel_responsable')->nullable();
            $t->boolean('activo')->default(true);
            $t->dateTime('created_at')->useCurrent();
        });
        Schema::create('asignacion_contrato', function (Blueprint $t) {
            $t->increments('id_asignacion');
            $t->unsignedInteger('id_contrato');
            $t->unsignedInteger('id_empleado');
            $t->unsignedInteger('id_sitio')->nullable();
            $t->date('fecha_inicio');
            $t->date('fecha_fin')->nullable();
            $t->string('rol_en_sitio')->nullable();
            $t->string('turno')->nullable();
            $t->boolean('activo')->default(true);
            $t->dateTime('created_at')->useCurrent();
        });
        Schema::table('empleado', function (Blueprint $t) {
            $t->unsignedInteger('id_sucursal')->nullable();
            foreach (['primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'apellido_casada'] as $columna) {
                $t->string($columna)->nullable();
            }
            foreach (['id_cargo', 'id_depto_org', 'id_supervisor', 'id_municipio', 'created_by', 'updated_by'] as $columna) {
                $t->unsignedInteger($columna)->nullable();
            }
            foreach (['dpi_nit', 'nit_personal', 'igss_afiliacion', 'genero', 'estado_civil', 'nacionalidad', 'email_personal', 'email_corporativo',
                'telefono_personal', 'telefono_emergencia', 'contacto_emergencia', 'direccion', 'codigo_empleado', 'motivo_baja', 'foto_url'] as $columna) {
                $t->string($columna)->nullable();
            }
            $t->string('tipo_doc_id')->default('DPI');
            $t->string('tipo_contrato')->default('INDEFINIDO');
            $t->string('modalidad_trabajo')->default('PRESENCIAL');
            $t->date('fecha_nacimiento')->nullable();
            $t->date('fecha_ingreso')->nullable();
            $t->date('fecha_baja')->nullable();
            $t->timestamps();
        });
        Schema::create('departamento_org', function (Blueprint $t) {
            $t->increments('id_depto_org');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_padre')->nullable();
            $t->string('nombre');
            $t->string('codigo')->nullable();
            $t->string('centro_costo')->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
        Schema::create('cargo', function (Blueprint $t) {
            $t->increments('id_cargo');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_depto_org')->nullable();
            $t->string('nombre');
            $t->text('descripcion')->nullable();
            $t->unsignedTinyInteger('nivel_jerarquico')->default(1);
            $t->decimal('salario_min', 15, 4)->nullable();
            $t->decimal('salario_max', 15, 4)->nullable();
            $t->string('moneda', 3)->default('GTQ');
            $t->boolean('requiere_vehiculo')->default(false);
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
        Schema::create('contrato_laboral', function (Blueprint $t) {
            $t->increments('id_contrato');
            $t->unsignedInteger('id_empleado');
            $t->unsignedInteger('id_empresa');
            $t->string('numero_contrato', 50);
            $t->string('tipo');
            $t->date('fecha_inicio');
            $t->date('fecha_fin')->nullable();
            $t->decimal('salario_base', 15, 4);
            $t->string('moneda', 3)->default('GTQ');
            $t->string('jornada')->default('COMPLETA');
            $t->unsignedTinyInteger('horas_semana')->default(44);
            $t->string('url_contrato')->nullable();
            $t->string('estado')->default('VIGENTE');
            $t->unsignedInteger('created_by')->nullable();
            $t->timestamps();
        });
        Schema::create('historial_salarial', function (Blueprint $t) {
            $t->increments('id_historial');
            $t->unsignedInteger('id_empleado');
            $t->unsignedInteger('id_cargo')->nullable();
            $t->decimal('salario_anterior', 15, 4)->nullable();
            $t->decimal('salario_nuevo', 15, 4);
            $t->string('moneda', 3)->default('GTQ');
            $t->string('tipo_cambio');
            $t->date('fecha_efectiva');
            $t->string('motivo')->nullable();
            $t->unsignedInteger('aprobado_por')->nullable();
            $t->unsignedInteger('created_by')->nullable();
            $t->dateTime('created_at')->useCurrent();
        });
        // Inventario
        Schema::create('bodega', function (Blueprint $t) {
            $t->increments('id_bodega');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_sucursal')->nullable();
            $t->string('nombre');
            $t->string('ubicacion')->nullable();
            $t->unsignedInteger('responsable_id')->nullable();
            $t->boolean('activo')->default(true);
        });
        Schema::create('categoria_producto', function (Blueprint $t) {
            $t->increments('id_categoria');
            $t->unsignedInteger('id_padre')->nullable();
            $t->unsignedInteger('id_empresa');
            $t->string('nombre');
            $t->string('descripcion')->nullable();
            $t->boolean('activo')->default(true);
        });
        Schema::create('producto', function (Blueprint $t) {
            $t->increments('id_producto');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_categoria')->nullable();
            $t->string('codigo')->nullable();
            $t->string('nombre');
            $t->text('descripcion')->nullable();
            $t->string('unidad_medida')->default('UND');
            $t->decimal('precio_compra', 14, 4)->nullable();
            $t->unsignedInteger('id_cuenta_gasto')->nullable();
            $t->unsignedInteger('id_centro_default')->nullable();
            $t->decimal('precio_venta', 14, 4)->nullable();
            $t->string('moneda', 3)->default('GTQ');
            $t->decimal('stock_minimo', 14, 2)->default(0);
            $t->decimal('stock_maximo', 14, 2)->nullable();
            $t->boolean('requiere_lote')->default(false);
            $t->boolean('es_perecedero')->default(false);
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
        Schema::create('stock_bodega', function (Blueprint $t) {
            $t->increments('id_stock');
            $t->unsignedInteger('id_producto');
            $t->unsignedInteger('id_bodega');
            $t->decimal('cantidad_actual', 14, 4)->default(0);
            $t->decimal('costo_promedio', 14, 4)->default(0);
            $t->timestamp('updated_at')->nullable();
        });
        Schema::create('moneda', function (Blueprint $t) {
            $t->string('codigo', 3)->primary();
            $t->string('nombre');
            $t->string('simbolo')->nullable();
            $t->boolean('activo')->default(true);
        });
        DB::table('moneda')->insert([['codigo' => 'GTQ', 'nombre' => 'Quetzal', 'simbolo' => 'Q'], ['codigo' => 'USD', 'nombre' => 'Dolar', 'simbolo' => '$']]);
        Schema::create('proveedor', function (Blueprint $t) {
            $t->increments('id_proveedor');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_pais')->nullable();
            $t->string('razon_social');
            $t->string('nombre_comercial')->nullable();
            $t->string('nit')->nullable();
            $t->string('email')->nullable();
            $t->string('telefono')->nullable();
            $t->string('direccion')->nullable();
            $t->string('contacto')->nullable();
            $t->string('tipo_proveedor')->default('BIENES');
            $t->unsignedInteger('dias_credito')->default(0);
            $t->string('moneda_pago', 3)->default('GTQ');
            $t->boolean('activo')->default(true);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('centro_costo', function (Blueprint $t) {
            $t->increments('id_centro');
            $t->unsignedInteger('id_empresa');
            $t->string('codigo');
            $t->string('nombre');
            $t->string('descripcion')->nullable();
            $t->boolean('activo')->default(true);
        });
        Schema::create('cuenta_contable', function (Blueprint $t) {
            $t->increments('id_cuenta');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_padre')->nullable();
            $t->string('codigo');
            $t->string('nombre');
            $t->string('tipo')->default('GASTO');
            $t->string('naturaleza')->nullable();
            $t->unsignedInteger('nivel')->default(1);
            $t->boolean('permite_movimiento')->default(true);
            $t->boolean('activo')->default(true);
        });
        Schema::create('orden_compra', function (Blueprint $t) {
            $t->increments('id_oc');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_proveedor')->nullable();
            $t->unsignedInteger('id_bodega')->nullable();
            $t->string('numero_oc')->nullable();
            $t->date('fecha_emision')->nullable();
            $t->date('fecha_entrega_esperada')->nullable();
            $t->date('fecha_entrega_real')->nullable();
            $t->string('moneda', 3)->default('GTQ');
            $t->decimal('subtotal', 14, 4)->default(0);
            $t->decimal('iva', 14, 4)->default(0);
            $t->decimal('total', 14, 4)->default(0);
            $t->string('estado')->default('BORRADOR');
            $t->text('notas')->nullable();
            $t->unsignedInteger('created_by')->nullable();
            $t->unsignedInteger('aprobado_por')->nullable();
            $t->timestamps();
        });
        Schema::create('detalle_orden_compra', function (Blueprint $t) {
            $t->increments('id_linea');
            $t->unsignedInteger('id_oc');
            $t->unsignedInteger('id_producto');
            $t->unsignedInteger('id_centro')->nullable();
            $t->unsignedInteger('id_cuenta')->nullable();
            $t->string('descripcion')->nullable();
            $t->decimal('cantidad_pedida', 14, 4);
            $t->decimal('cantidad_recibida', 14, 4)->default(0);
            $t->decimal('precio_unitario', 14, 4);
            $t->decimal('descuento', 14, 4)->default(0);
            $t->decimal('subtotal', 14, 4)->default(0);
        });
        Schema::create('detalle_recepcion', function (Blueprint $t) {
            $t->increments('id_detalle_rec');
            $t->unsignedInteger('id_recepcion')->nullable();
            $t->unsignedInteger('id_linea')->nullable();
            $t->unsignedInteger('id_producto');
            $t->decimal('cantidad_recibida', 12, 4)->default(0);
            $t->decimal('costo_unitario', 15, 4)->default(0);
            $t->decimal('subtotal', 15, 4)->default(0);
        });
        Schema::create('presupuesto_anual', function (Blueprint $t) {
            $t->increments('id_presupuesto');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_centro');
            $t->unsignedInteger('id_cuenta');
            $t->unsignedInteger('anio');
            $t->string('moneda', 3)->default('GTQ');
            foreach (['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'] as $mes) {
                $t->decimal("pre_{$mes}", 14, 4)->default(0);
                $t->decimal("eje_{$mes}", 14, 4)->default(0);
            }
            $t->decimal('total_presupuestado', 14, 4)->default(0);
            $t->decimal('total_ejecutado', 14, 4)->default(0);
            $t->string('estado')->default('BORRADOR');
            foreach (['aprobado_por', 'cerrado_por', 'created_by', 'updated_by'] as $c) {
                $t->unsignedInteger($c)->nullable();
            }
            $t->dateTime('fecha_aprobacion')->nullable();
            $t->dateTime('fecha_cierre')->nullable();
            $t->timestamps();
        });
        Schema::table('empresa', function (Blueprint $t) {
            $t->decimal('tasa_iva', 5, 2)->default(12);
            $t->boolean('iva_incluido_en_precio')->default(false);
            $t->string('nombre_legal')->nullable();
            $t->string('nit')->nullable();
        });
        Schema::create('recepcion_mercaderia', function (Blueprint $t) {
            $t->increments('id_recepcion');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_oc')->nullable();
            $t->unsignedInteger('id_bodega')->nullable();
            $t->string('numero_recepcion', 30)->nullable();
            $t->date('fecha_recepcion')->nullable();
            $t->text('notas')->nullable();
            $t->unsignedInteger('created_by')->nullable();
            $t->timestamps();
        });
        Schema::create('movimiento_inventario', function (Blueprint $t) {
            $t->increments('id_movimiento');
            $t->unsignedInteger('id_empresa');
            $t->unsignedInteger('id_producto')->nullable();
            $t->unsignedInteger('id_bodega')->nullable();
            $t->string('tipo_movimiento')->default('ENTRADA');
            $t->decimal('cantidad', 12, 4)->default(0);
            $t->decimal('costo_unitario', 15, 4)->nullable();
            $t->decimal('costo_total', 15, 4)->nullable();
            $t->string('moneda', 3)->default('GTQ');
            $t->string('referencia_tipo')->nullable();
            $t->unsignedInteger('referencia_id')->nullable();
            $t->string('numero_lote')->nullable();
            $t->date('fecha_vencimiento')->nullable();
            $t->string('observaciones', 300)->nullable();
            $t->unsignedInteger('created_by')->default(0);
            $t->dateTime('created_at')->useCurrent();
        });
        Schema::create('ConfiguracionSistema', function (Blueprint $t) {
            $t->increments('idConfig');
            $t->string('tipo');
            $t->string('nombreSistema')->nullable();
            foreach (array (
  0 => 'nombreEmpresa',
  1 => 'slogan',
  2 => 'nit',
  3 => 'telefono',
  4 => 'correoContacto',
  5 => 'direccion',
  6 => 'sitioWeb',
  7 => 'moneda',
  8 => 'monedaCodigo',
  9 => 'zonaHoraria',
  10 => 'formatoFecha',
  11 => 'colorPrimario',
  12 => 'colorSecundario',
  13 => 'colorAccent',
  14 => 'loginTitulo',
  15 => 'loginSubtitulo',
  16 => 'loginMensajeBienve',
  17 => 'loginLabelUsuario',
  18 => 'loginPlaceholderUs',
  19 => 'loginLabelPassword',
  20 => 'loginLabelRecordar',
  21 => 'loginLinkOlvide',
  22 => 'loginTextBoton',
  23 => 'footerTexto',
  24 => 'footerVersion',
  25 => 'emailAsuntoReset',
  26 => 'emailAsuntoBienve',
  27 => 'emailAsuntoCuota',
  28 => 'emailFirma',
  29 => 'imgLogo',
  30 => 'imgLogoOscuro',
  31 => 'imgFavicon',
  32 => 'imgFondoLogin',
  33 => 'imgAvatarDefault',
  34 => 'imgBannerDashboard',
  35 => 'imgLogoEmail',
  36 => 'imgFondoEmail',
  37 => 'imgLogoReporte',
  38 => 'imgFondoError404',
) as $columna) {
                $t->text($columna)->nullable();
            }
            $t->integer('diasMora')->nullable();
            $t->decimal('porcentajeMora', 5, 2)->nullable();
            $t->integer('footerAnio')->nullable();
            $t->unsignedSmallInteger('maxIntentosSesion')->nullable();
            $t->unsignedSmallInteger('bloqueoMinutos')->default(15);
            $t->unsignedSmallInteger('sesionExpiraMin')->nullable();
            $t->unsignedInteger('actualizadoPor')->nullable();
            $t->dateTime('fechaActualizacion')->nullable();
            $t->boolean('estado')->default(true);
        });
        foreach (['login', 'general'] as $tipo) {
            DB::table('ConfiguracionSistema')->insert(['tipo' => $tipo, 'nombreSistema' => 'Nexus ERP', 'maxIntentosSesion' => 5, 'sesionExpiraMin' => 120]);
        }
    }

    protected function crearUsuario(array $datos = [], string $rol = 'Administrador', bool $rolActivo = true): Usuario
    {
        $usuario = Usuario::create(array_merge([
            'id_empresa' => 1,
            'username' => 'admin',
            'email' => 'admin@nexus.test',
            'password_hash' => Hash::make('Clave-Segura-2026'),
            'nombre_completo' => 'Administrador Nexus',
            'activo' => true,
        ], $datos));
        $idRol = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => $rol, 'activo' => $rolActivo]);
        DB::table('usuario_rol')->insert(['id_usuario' => $usuario->id_usuario, 'id_rol' => $idRol]);

        return $usuario;
    }

    protected function auditoria(string $accion): int
    {
        return DB::table('auditoria_acceso')->where('accion', $accion)->count();
    }

    /**
     * Id del permiso «modulo.accion»; crea el módulo, la acción y el permiso si no existen.
     * $modulo: columnas extra del módulo al crearlo (ruta, grupo, icono…).
     */
    protected function permiso(string $codigo, array $modulo = []): int
    {
        $punto = strrpos($codigo, '.');
        [$codModulo, $codAccion] = [substr($codigo, 0, $punto), substr($codigo, $punto + 1)];
        $idModulo = DB::table('modulo')->where('codigo', $codModulo)->value('id_modulo')
            ?? DB::table('modulo')->insertGetId($modulo + ['codigo' => $codModulo, 'nombre' => ucfirst($codModulo), 'activo' => true]);
        $idAccion = DB::table('accion')->where('codigo', $codAccion)->value('id_accion')
            ?? DB::table('accion')->insertGetId(['codigo' => $codAccion, 'nombre' => ucfirst($codAccion)]);

        return DB::table('permiso')->where(['id_modulo' => $idModulo, 'id_accion' => $idAccion])->value('id_permiso')
            ?? DB::table('permiso')->insertGetId(['id_modulo' => $idModulo, 'id_accion' => $idAccion, 'descripcion' => $codigo]);
    }

    /** Da a los roles del usuario los permisos indicados («modulo.accion»). */
    protected function darPermisos(Usuario $usuario, array $codigos): void
    {
        foreach ($usuario->roles()->pluck('rol.id_rol') as $idRol) {
            foreach ($codigos as $codigo) {
                DB::table('rol_permiso')->insertOrIgnore(['id_rol' => $idRol, 'id_permiso' => $this->permiso($codigo)]);
            }
        }
        $usuario->olvidarPermisos();
    }
}
