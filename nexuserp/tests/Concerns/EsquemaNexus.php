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
        Schema::create('modulo_sistema', function (Blueprint $t) {
            $t->increments('id_modulo');
            $t->string('codigo')->nullable();
            $t->string('nombre');
            $t->string('icono')->nullable();
            $t->integer('orden_menu')->default(0);
            $t->boolean('activo')->default(true);
        });
        Schema::create('permiso', function (Blueprint $t) {
            $t->increments('id_permiso');
            $t->unsignedInteger('id_modulo')->nullable();
            $t->string('codigo');
            $t->string('descripcion')->nullable();
        });
        Schema::create('rol_permiso', function (Blueprint $t) {
            $t->unsignedInteger('id_rol');
            $t->unsignedInteger('id_permiso');
            foreach (['crear', 'leer', 'editar', 'eliminar', 'exportar'] as $accion) {
                $t->boolean("puede_{$accion}")->default(false);
            }
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
        // Menú lateral (Gestión de menú) y tablas que cuenta el dashboard.
        Schema::create('menu', function (Blueprint $t) {
            $t->increments('id_menu');
            $t->unsignedInteger('id_empresa')->nullable();
            $t->unsignedInteger('id_padre')->nullable();
            $t->string('nombre');
            $t->string('icono')->nullable();
            $t->string('ruta')->nullable();
            $t->integer('orden')->default(0);
            $t->boolean('activo')->default(true);
        });
        Schema::create('menu_rol', function (Blueprint $t) {
            $t->unsignedInteger('id_menu');
            $t->unsignedInteger('id_rol');
        });
        // Tokens de Sanctum (se siguen revocando por si quedó alguno antiguo sin expirar).
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->string('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
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

    /** Da a los roles del usuario los permisos indicados (crea los códigos si no existen). */
    protected function darPermisos(Usuario $usuario, array $codigos): void
    {
        foreach ($usuario->roles()->pluck('rol.id_rol') as $idRol) {
            foreach ($codigos as $codigo) {
                $idPermiso = DB::table('permiso')->where('codigo', $codigo)->value('id_permiso')
                    ?? DB::table('permiso')->insertGetId(['codigo' => $codigo]);
                DB::table('rol_permiso')->insert(['id_rol' => $idRol, 'id_permiso' => $idPermiso]);
            }
        }
    }
}
