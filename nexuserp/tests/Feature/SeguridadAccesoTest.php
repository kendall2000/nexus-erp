<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
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
            $t->dateTime('created_at')->useCurrent();
        });
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

        // Sin tabla ConfiguracionSistema se usan los valores por defecto: 5 intentos.
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
}
