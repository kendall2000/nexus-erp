<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use App\Support\Seguridad;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use PragmaRX\Google2FA\Google2FA;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/**
 * Seguridad (pasos 1 y 2) y layout (3a): inicio de sesión con Fortify, usuario activo, límite
 * de intentos, historial, permisos, 2 pasos, contraseñas, menú lateral y dashboard.
 */
class SeguridadAccesoTest extends TestCase
{
    use EsquemaNexus;

    public function test_sin_sesion_las_pantallas_redirigen_al_login(): void
    {
        $this->get('/sistema/dashboard')->assertRedirect('/login');
        $this->get('/sistema/productos')->assertRedirect('/login');
        $this->get('/modulos-js/bodegas/index.js')->assertRedirect('/login');
    }

    public function test_sin_sesion_la_api_responde_401_en_json(): void
    {
        $this->getJson('/api/v1/inventario/bodegas')
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
        $this->actingAs($usuario)->getJson('/api/v1/inventario/bodegas')->assertForbidden();
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

    // ── Paso 3a: layout nuevo, menú lateral y dashboard ─────────────────────

    private function crearMenu(): array
    {
        $grupo = DB::table('menu')->insertGetId(['id_empresa' => 1, 'nombre' => 'Inventario', 'orden' => 1]);
        $productos = DB::table('menu')->insertGetId(['id_empresa' => 1, 'id_padre' => $grupo, 'nombre' => 'Productos', 'icono' => 'package', 'ruta' => '/sistema/productos', 'orden' => 1]);
        $bodegas = DB::table('menu')->insertGetId(['id_empresa' => 1, 'id_padre' => $grupo, 'nombre' => 'Bodegas', 'icono' => 'archive', 'ruta' => '/sistema/bodegas', 'orden' => 2]);
        $vacio = DB::table('menu')->insertGetId(['id_empresa' => 1, 'nombre' => 'Solo admin', 'orden' => 2]);
        $usuarios = DB::table('menu')->insertGetId(['id_empresa' => 1, 'id_padre' => $vacio, 'nombre' => 'Usuarios', 'icono' => 'user', 'ruta' => '/sistema/usuarios', 'orden' => 1]);

        return compact('productos', 'bodegas', 'usuarios');
    }

    public function test_dashboard_con_el_layout_nuevo_y_cifras_de_la_empresa(): void
    {
        $admin = $this->crearUsuario();
        $this->crearMenu();
        DB::table('cliente')->insert([['id_empresa' => 1, 'activo' => 1], ['id_empresa' => 1, 'activo' => 1], ['id_empresa' => 2, 'activo' => 1]]);
        DB::table('ticket')->insert([['id_empresa' => 1, 'estado' => 'ABIERTO'], ['id_empresa' => 1, 'estado' => 'CERRADO']]);

        $respuesta = $this->actingAs($admin)->get(route('dashboard'));

        $respuesta->assertOk()
            ->assertSee('Hola, Administrador Nexus')
            ->assertSee('Empresa Demo')
            ->assertSee('navbar-vertical', false)
            ->assertSee('Seguridad y accesos');
        $this->assertSame([2, 0, 0, 1], collect($respuesta->viewData('tarjetas'))->pluck('valor')->all());
    }

    public function test_el_menu_lateral_respeta_los_roles(): void
    {
        $menu = $this->crearMenu();
        $admin = $this->crearUsuario();
        $bodeguero = $this->crearUsuario(['username' => 'bodega', 'email' => 'bodega@nexus.test'], 'Bodega');
        $idRolBodega = DB::table('usuario_rol')->where('id_usuario', $bodeguero->id_usuario)->value('id_rol');
        // Bodegas solo para el rol Bodega; Usuarios solo para otro rol: el bodeguero no lo ve.
        $idOtroRol = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Contabilidad', 'activo' => true]);
        DB::table('menu_rol')->insert([['id_menu' => $menu['bodegas'], 'id_rol' => $idRolBodega], ['id_menu' => $menu['usuarios'], 'id_rol' => $idOtroRol]]);

        $this->actingAs($admin)->get(route('dashboard'))->assertSee('Productos')->assertSee('Bodegas')->assertSee('Solo admin');

        $this->actingAs($bodeguero)->get(route('dashboard'))
            ->assertSee('Productos')->assertSee('Bodegas')
            ->assertDontSee('Solo admin')->assertDontSee('Seguridad y accesos');
    }

    public function test_las_pantallas_vue_usan_el_layout_puente(): void
    {
        $this->crearMenu();

        $this->actingAs($this->crearUsuario())->get('/sistema/bodegas')
            ->assertOk()
            ->assertSee('navbar-vertical', false)
            ->assertSee('vue@2.5.16', false)
            ->assertSee('/modulos-js/bodegas/index.js', false)
            ->assertSee('Gestión de Bodegas');
    }

    public function test_una_pantalla_que_no_existe_vuelve_al_inicio_con_aviso(): void
    {
        $this->actingAs($this->crearUsuario())->get('/sistema/prospectos')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('aviso', 'Esa pantalla todavía no está disponible.');
    }
}
