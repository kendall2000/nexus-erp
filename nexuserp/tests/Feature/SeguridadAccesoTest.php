<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use App\Support\Seguridad;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Paso 1: inicio de sesión con Fortify (sesión web), usuario activo, límite
 * de intentos, historial de accesos, permisos y protección de rutas.
 *
 * El esquema de Nexus no está en migraciones: aquí se crean en SQLite (memoria)
 * solo las tablas que usa la seguridad.
 */
class SeguridadAccesoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
    }

    private function crearEsquema(): void
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
        Schema::create('ConfiguracionSistema', function (Blueprint $t) {
            $t->increments('idConfig');
            $t->string('tipo');
            $t->string('nombreSistema')->nullable();
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

    private function crearUsuario(array $datos = [], string $rol = 'Administrador', bool $rolActivo = true): Usuario
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

    private function auditoria(string $accion): int
    {
        return DB::table('auditoria_acceso')->where('accion', $accion)->count();
    }

    public function test_sin_sesion_las_pantallas_redirigen_al_login(): void
    {
        $this->get('/sistema/dashboard')->assertRedirect('/login');
        $this->get('/sistema/productos')->assertRedirect('/login');
        $this->get('/modulos-js/bodegas/index.js')->assertRedirect('/login');
    }

    public function test_sin_sesion_la_api_responde_401_en_json(): void
    {
        $this->getJson('/api/v1/menu')
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_ya_no_existe_el_login_por_token(): void
    {
        $this->crearUsuario();

        $this->postJson('/api/v1/auth/login', ['login' => 'admin', 'password' => 'Clave-Segura-2026'])
            ->assertStatus(404);
    }

    public function test_el_login_muestra_el_formulario_con_encabezados_de_seguridad(): void
    {
        $respuesta = $this->get('/login');

        $respuesta->assertOk()
            ->assertSee('name="login"', false)
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $respuesta->headers->get('Cache-Control'));
    }

    public function test_entra_con_nombre_de_usuario_y_registra_el_acceso(): void
    {
        $usuario = $this->crearUsuario();

        $this->post('/login', ['login' => 'admin', 'password' => 'Clave-Segura-2026'])
            ->assertRedirect('/sistema/dashboard');

        $this->assertAuthenticatedAs($usuario);
        $this->assertSame(1, $this->auditoria('LOGIN_OK'));
        $this->assertNotNull($usuario->fresh()->ultimo_login);
    }

    public function test_entra_con_correo_sin_importar_mayusculas(): void
    {
        $usuario = $this->crearUsuario();

        $this->post('/login', ['login' => 'ADMIN@Nexus.test', 'password' => 'Clave-Segura-2026'])
            ->assertRedirect('/sistema/dashboard');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_contrasena_incorrecta_no_entra_y_se_registra(): void
    {
        $this->crearUsuario();

        $this->from('/login')->post('/login', ['login' => 'admin', 'password' => 'otra'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');

        $this->assertGuest();
        $this->assertSame(1, $this->auditoria('LOGIN_FAIL'));
    }

    public function test_usuario_inactivo_no_entra_aunque_sepa_la_contrasena(): void
    {
        $this->crearUsuario(['activo' => false]);

        $this->from('/login')->post('/login', ['login' => 'admin', 'password' => 'Clave-Segura-2026'])
            ->assertSessionHasErrors(['login' => 'Tu usuario está desactivado. Consulta con el administrador.']);

        $this->assertGuest();
    }

    public function test_usuario_sin_rol_activo_no_entra(): void
    {
        $this->crearUsuario([], 'Ventas', rolActivo: false);

        $this->from('/login')->post('/login', ['login' => 'admin', 'password' => 'Clave-Segura-2026'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_bloquea_tras_demasiados_intentos(): void
    {
        $this->crearUsuario();

        // ConfiguracionSistema de prueba: 5 intentos.
        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', ['login' => 'admin', 'password' => 'mala']);
        }

        $this->from('/login')->post('/login', ['login' => 'admin', 'password' => 'Clave-Segura-2026'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('login'));
    }

    public function test_si_lo_desactivan_con_la_sesion_abierta_lo_saca(): void
    {
        $usuario = $this->crearUsuario();
        $this->actingAs($usuario);
        $usuario->update(['activo' => false]);

        $this->get('/sistema/dashboard')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_cerrar_sesion_registra_la_salida(): void
    {
        $usuario = $this->crearUsuario();

        $this->actingAs($usuario)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
        $this->assertSame(1, $this->auditoria('LOGOUT'));
    }

    public function test_la_api_acepta_la_sesion_del_navegador(): void
    {
        Route::middleware(['api', 'auth:sanctum'])->get('/api/v1/_prueba', fn () => ['ok' => true]);
        $usuario = $this->crearUsuario();

        $this->actingAs($usuario, 'web')
            ->withHeaders(['Referer' => 'http://localhost/sistema/dashboard'])
            ->getJson('/api/v1/_prueba')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_permisos_por_rol_y_administrador(): void
    {
        Route::middleware(['web', 'auth', 'permiso:INV.PRODUCTOS.VER'])->get('/_prueba-permiso', fn () => 'ok');
        Route::middleware(['web', 'auth', 'admin'])->get('/_prueba-admin', fn () => 'ok');

        $admin = $this->crearUsuario();
        $vendedor = $this->crearUsuario(['username' => 'ventas', 'email' => 'ventas@nexus.test'], 'Ventas');
        $bodeguero = $this->crearUsuario(['username' => 'bodega', 'email' => 'bodega@nexus.test'], 'Bodega');
        $idPermiso = DB::table('permiso')->insertGetId(['codigo' => 'INV.PRODUCTOS.VER']);
        $idRolBodega = DB::table('usuario_rol')->where('id_usuario', $bodeguero->id_usuario)->value('id_rol');
        DB::table('rol_permiso')->insert(['id_rol' => $idRolBodega, 'id_permiso' => $idPermiso]);

        $this->assertTrue($admin->puede('CUALQUIER.COSA'));
        $this->assertTrue($bodeguero->puede('INV.PRODUCTOS.VER'));
        $this->assertFalse($vendedor->puede('INV.PRODUCTOS.VER'));

        $this->actingAs($bodeguero)->get('/_prueba-permiso')->assertOk();
        $this->actingAs($vendedor)->get('/_prueba-permiso')->assertForbidden();
        $this->actingAs($bodeguero)->get('/_prueba-admin')->assertForbidden();
        $this->actingAs($admin)->get('/_prueba-admin')->assertOk();
    }

    // ── Paso 2 ──────────────────────────────────────────────────────────────

    public function test_recordar_sesion_guarda_el_token(): void
    {
        $usuario = $this->crearUsuario();

        $this->post('/login', ['login' => 'admin', 'password' => 'Clave-Segura-2026', 'remember' => 'on'])
            ->assertRedirect('/sistema/dashboard');

        $this->assertNotNull($usuario->fresh()->remember_token);
        $this->assertSame('Con «recordar sesión»', DB::table('auditoria_acceso')->where('accion', 'LOGIN_OK')->value('detalle'));
    }

    public function test_con_dos_pasos_pide_el_codigo_y_luego_entra(): void
    {
        $usuario = $this->crearUsuarioConDosPasos();

        $this->post('/login', ['login' => 'admin', 'password' => 'Clave-Segura-2026'])
            ->assertRedirect('/two-factor-challenge');
        $this->assertGuest();

        $this->post('/two-factor-challenge', ['code' => '000000'])->assertRedirect();
        $this->assertGuest();
        $this->assertSame(1, $this->auditoria('LOGIN_FAIL_2FA'));

        $codigo = (new Google2FA)->getCurrentOtp(decrypt($usuario->fresh()->two_factor_secret));
        $this->post('/two-factor-challenge', ['code' => $codigo])->assertRedirect('/sistema/dashboard');
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_rol_que_exige_dos_pasos_obliga_a_activarla(): void
    {
        $usuario = $this->crearUsuario([], 'Cajero');
        DB::table('rol')->update(['requiere_2fa' => true]);

        $this->actingAs($usuario)->get('/sistema/dashboard')->assertRedirect(route('cuenta.seguridad'));
        $this->actingAs($usuario)->getJson('/api/v1/menu')->assertForbidden();
        $this->actingAs($usuario)->get(route('cuenta.seguridad'))
            ->assertOk()
            ->assertSee('Tu rol exige la verificación en dos pasos');
    }

    public function test_confirmar_contrasena_usa_password_hash(): void
    {
        $usuario = $this->crearUsuario();

        $this->actingAs($usuario)->post('/user/confirm-password', ['password' => 'otra'])->assertSessionHasErrors();
        $this->actingAs($usuario)->post('/user/confirm-password', ['password' => 'Clave-Segura-2026'])->assertSessionHasNoErrors();
    }

    public function test_cambiar_contrasena_exige_la_regla_y_cierra_las_demas_sesiones(): void
    {
        $usuario = $this->crearUsuario();
        DB::table('sessions')->insert(['id' => 'otra-sesion', 'user_id' => $usuario->id_usuario, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($usuario)->put('/user/password', [
            'current_password' => 'Clave-Segura-2026', 'password' => 'corta', 'password_confirmation' => 'corta',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->actingAs($usuario)->put('/user/password', [
            'current_password' => 'Clave-Segura-2026', 'password' => 'Nueva-Clave-2027!', 'password_confirmation' => 'Nueva-Clave-2027!',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Nueva-Clave-2027!', $usuario->fresh()->password_hash));
        $this->assertSame(0, DB::table('sessions')->where('id', 'otra-sesion')->count());
        $this->assertSame(1, $this->auditoria('CAMBIO_PASSWORD'));
    }

    public function test_cerrar_las_demas_sesiones_pide_la_contrasena(): void
    {
        $usuario = $this->crearUsuario();
        DB::table('sessions')->insert(['id' => 'otra-sesion', 'user_id' => $usuario->id_usuario, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($usuario)->delete(route('cuenta.sesiones.cerrar'), ['password' => 'mala'])->assertSessionHasErrors('password');
        $this->assertSame(1, DB::table('sessions')->count());

        $this->actingAs($usuario)->delete(route('cuenta.sesiones.cerrar'), ['password' => 'Clave-Segura-2026'])->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('sessions')->where('id', 'otra-sesion')->count());
        $this->assertSame(1, $this->auditoria('SESION_CERRADA'));
    }

    public function test_olvide_mi_contrasena_no_revela_si_el_correo_existe(): void
    {
        Notification::fake();
        $usuario = $this->crearUsuario();

        $conCuenta = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'admin@nexus.test']);
        $sinCuenta = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'nadie@nexus.test']);

        $conCuenta->assertSessionHasNoErrors()->assertSessionHas('status');
        $sinCuenta->assertSessionHasNoErrors()->assertSessionHas('status', $conCuenta->getSession()->get('status'));
        Notification::assertSentTo($usuario, ResetPassword::class);
    }

    public function test_recuperar_contrasena_con_el_enlace(): void
    {
        $usuario = $this->crearUsuario();
        DB::table('sessions')->insert(['id' => 'sesion-vieja', 'user_id' => $usuario->id_usuario, 'payload' => '', 'last_activity' => time()]);
        $token = Password::broker()->createToken($usuario);

        $this->post('/reset-password', [
            'token' => $token, 'email' => 'admin@nexus.test',
            'password' => 'Recuperada-2027!', 'password_confirmation' => 'Recuperada-2027!',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('Recuperada-2027!', $usuario->fresh()->password_hash));
        $this->assertSame(0, DB::table('sessions')->count());
        $this->assertSame(1, $this->auditoria('RESET_PASSWORD'));
    }

    public function test_seguridad_y_accesos_solo_para_el_administrador(): void
    {
        $admin = $this->crearUsuario();
        $vendedor = $this->crearUsuario(['username' => 'ventas', 'email' => 'ventas@nexus.test'], 'Ventas');
        $idRolVentas = DB::table('usuario_rol')->where('id_usuario', $vendedor->id_usuario)->value('id_rol');

        $this->actingAs($vendedor)->get(route('seguridad.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('seguridad.index'))->assertOk()->assertSee('Historial de accesos');

        $this->actingAs($admin)->put(route('seguridad.guardar'), ['max_intentos' => 4, 'bloqueo_minutos' => 30, 'sesion_expira_min' => 90])
            ->assertSessionHasNoErrors();
        $this->assertSame(['intentos' => 4, 'bloqueo' => 30, 'expira' => 90], Seguridad::config());

        $this->actingAs($admin)->put(route('seguridad.roles'), ['requiere_2fa' => [$idRolVentas]]);
        $this->assertTrue((bool) DB::table('rol')->where('id_rol', $idRolVentas)->value('requiere_2fa'));
        $this->assertTrue($vendedor->fresh()->debeActivarDosPasos());
    }

    public function test_usuarios_crea_con_la_regla_de_contrasena(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->postJson('/api/v1/usuarios', [
            'nombre_completo' => 'Nuevo', 'username' => 'nuevo', 'email' => 'nuevo@nexus.test', 'password' => 'corta123',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    private function crearUsuarioConDosPasos(): Usuario
    {
        $usuario = $this->crearUsuario();
        $usuario->forceFill([
            'two_factor_secret' => encrypt((new Google2FA)->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(['codigo-1', 'codigo-2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $usuario;
    }
}
