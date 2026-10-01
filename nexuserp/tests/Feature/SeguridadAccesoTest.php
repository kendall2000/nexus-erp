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
    }

    public function test_ya_no_hay_api_ni_modulos_js(): void
    {
        // Paso 6: todo es Blade; la API /api/v1 y los JS de Vue ya no existen.
        $this->getJson('/api/v1/auth/me')->assertNotFound();
        $this->actingAs($this->crearUsuario())->get('/modulos-js/recepciones/index.js')->assertNotFound();
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

    public function test_permisos_por_rol_extras_por_usuario_y_administrador(): void
    {
        Route::middleware(['web', 'auth', 'permiso:productos.ver'])->get('/_prueba-permiso', fn () => 'ok');
        Route::middleware(['web', 'auth', 'admin'])->get('/_prueba-admin', fn () => 'ok');

        $admin = $this->crearUsuario();
        $vendedor = $this->crearUsuario(['username' => 'ventas', 'email' => 'ventas@nexus.test'], 'Ventas');
        $bodeguero = $this->crearUsuario(['username' => 'bodega', 'email' => 'bodega@nexus.test'], 'Bodega');
        $this->darPermisos($bodeguero, ['productos.ver']);

        $this->assertTrue($admin->puede('cualquier.cosa'));
        $this->assertTrue($bodeguero->puede('productos.ver'));
        $this->assertFalse($bodeguero->puede('productos.crear'));
        $this->assertFalse($vendedor->fresh()->puede('productos.ver'));

        // Extra por usuario: el vendedor recibe productos.ver sin cambiar su rol.
        DB::table('usuario_permiso')->insert(['id_usuario' => $vendedor->id_usuario, 'id_permiso' => $this->permiso('productos.ver')]);
        $this->assertTrue($vendedor->fresh()->puede('productos.ver'));

        // Un módulo inactivo deja de dar permisos.
        DB::table('modulo')->where('codigo', 'productos')->update(['activo' => false]);
        $this->assertFalse($bodeguero->fresh()->puede('productos.ver'));
        DB::table('modulo')->where('codigo', 'productos')->update(['activo' => true]);

        $this->actingAs($bodeguero->fresh())->get('/_prueba-permiso')->assertOk();
        $this->actingAs($this->crearUsuario(['username' => 'x', 'email' => 'x@nexus.test'], 'Otro'))->get('/_prueba-permiso')->assertForbidden();
        $this->actingAs($bodeguero->fresh())->get('/_prueba-admin')->assertForbidden();
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
        $this->actingAs($usuario)->get('/sistema/productos')->assertRedirect(route('cuenta.seguridad'));
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
        $this->assertSame(['intentos' => 4, 'bloqueo' => 30, 'expira' => 90, 'zona' => 'America/Guatemala'], Seguridad::config());

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

    /** Módulos del menú: Inventario (productos, bodegas), Configuración (usuarios y «sistema», solo admin) y un submenú. */
    private function crearMenu(): void
    {
        $this->permiso('productos.ver', ['nombre' => 'Productos', 'grupo' => 'Inventario', 'ruta' => '/sistema/productos', 'icono' => 'package', 'orden' => 301]);
        $this->permiso('bodegas.ver', ['nombre' => 'Bodegas', 'grupo' => 'Inventario', 'ruta' => '/sistema/bodegas', 'icono' => 'archive', 'orden' => 302]);
        $this->permiso('usuarios.ver', ['nombre' => 'Usuarios', 'grupo' => 'Configuración', 'ruta' => '/sistema/usuarios', 'orden' => 601]);
        DB::table('modulo')->insert(['codigo' => 'sistema', 'nombre' => 'Solo admin', 'grupo' => 'Configuración', 'ruta' => '/sistema/configuracion', 'orden' => 602, 'activo' => true]);
        $reportes = DB::table('modulo')->insertGetId(['codigo' => 'reportes', 'nombre' => 'Reportes', 'grupo' => 'Inventario', 'orden' => 303, 'activo' => true]);
        $this->permiso('reportes.kardex.ver', ['nombre' => 'Kardex', 'ruta' => '/sistema/kardex', 'id_modulo_padre' => $reportes]);
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

    public function test_el_menu_lateral_sale_de_los_modulos_que_puede_ver(): void
    {
        $this->crearMenu();
        $admin = $this->crearUsuario();
        $bodeguero = $this->crearUsuario(['username' => 'bodega', 'email' => 'bodega@nexus.test'], 'Bodega');
        $this->darPermisos($bodeguero, ['bodegas.ver']);

        // El Administrador ve todo, incluidos los módulos sin permisos y los submenús.
        $this->actingAs($admin)->get(route('dashboard'))
            ->assertSeeInOrder(['Inicio', 'Inventario', 'Productos', 'Bodegas', 'Reportes', 'Kardex', 'Configuración', 'Usuarios', 'Solo admin', 'Mi cuenta']);

        // El bodeguero solo ve lo que tiene con «ver»; sin hijos visibles, el submenú no aparece.
        $this->actingAs($bodeguero)->get(route('dashboard'))
            ->assertSee('Bodegas')->assertDontSee('>Productos<', false)->assertDontSee('Reportes')
            ->assertDontSee('Solo admin')->assertDontSee('Seguridad y accesos');

        // Con un extra por usuario aparece la opción.
        DB::table('usuario_permiso')->insert(['id_usuario' => $bodeguero->id_usuario, 'id_permiso' => $this->permiso('reportes.kardex.ver')]);
        $this->actingAs($bodeguero->fresh())->get(route('dashboard'))->assertSee('Reportes')->assertSee('Kardex');
    }

    public function test_la_ultima_pantalla_vue_ya_es_blade(): void
    {
        $this->crearMenu();

        // Pagos era la última pantalla con el layout puente de Vue (paso 5e).
        $this->actingAs($this->crearUsuario())->get('/sistema/pagos')
            ->assertOk()
            ->assertSee('navbar-vertical', false)
            ->assertDontSee('vue@2.5.16', false)
            ->assertDontSee('/modulos-js/', false);
    }

    public function test_una_pantalla_que_no_existe_vuelve_al_inicio_con_aviso(): void
    {
        $this->actingAs($this->crearUsuario())->get('/sistema/reportes-inexistentes')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('aviso', 'Esa pantalla todavía no está disponible.');
    }
}
