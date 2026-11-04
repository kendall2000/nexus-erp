<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/**
 * Auditoría, paso 2: el bloqueo por intentos no se evade cambiando la forma de escribir la
 * cuenta ni la IP, «¿Olvidaste tu contraseña?» no delata correos y cambiar las credenciales
 * propias pide la contraseña actual.
 */
class SeguridadAccesoCuentaTest extends TestCase
{
    use EsquemaNexus;

    private function fallar(string $login, string $ip = '127.0.0.1'): void
    {
        $this->from('/login')->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('/login', ['login' => $login, 'password' => 'mala']);
    }

    private function entrar(string $login, string $ip = '127.0.0.1')
    {
        return $this->from('/login')->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('/login', ['login' => $login, 'password' => 'Clave-Segura-2026']);
    }

    public function test_usuario_correo_e_invisibles_comparten_el_contador_de_intentos(): void
    {
        $this->crearUsuario();

        // Cinco formas distintas de escribir la misma cuenta desde la misma IP.
        foreach (['admin', 'ADMIN', 'admin@nexus.test', "admin\u{200B}", " ad\u{200D}min "] as $login) {
            $this->fallar($login);
        }

        $this->entrar('admin')->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('login'));
    }

    public function test_con_invisibles_tambien_encuentra_la_cuenta(): void
    {
        $usuario = $this->crearUsuario();

        $this->entrar("ad\u{200B}min")->assertRedirect('/sistema/dashboard');
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_muchos_fallidos_desde_varias_ips_bloquean_la_cuenta(): void
    {
        $usuario = $this->crearUsuario();

        // 10 fallidos (el doble del límite por IP), repartidos en 5 IPs: ninguna llega a su límite.
        for ($i = 0; $i < 10; $i++) {
            $this->fallar('admin', '10.0.0.'.($i % 5 + 1));
        }
        $this->assertNotNull($usuario->fresh()->bloqueado_hasta);

        // Ni con la contraseña correcta ni desde otra IP.
        $this->entrar('admin', '10.9.9.9')->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('login'));

        // Vence sola.
        $this->travel(16)->minutes();
        $this->entrar('admin', '10.9.9.9')->assertRedirect('/sistema/dashboard');
        $this->assertAuthenticatedAs($usuario);
        $this->assertNull($usuario->fresh()->bloqueado_hasta);
        $this->assertSame(0, $usuario->fresh()->intentos_fallidos);
    }

    public function test_el_administrador_quita_el_bloqueo_de_la_cuenta(): void
    {
        $admin = $this->crearUsuario();
        $vendedor = $this->crearUsuario(['username' => 'ventas', 'email' => 'ventas@nexus.test'], 'Ventas');
        for ($i = 0; $i < 10; $i++) {
            $this->fallar('ventas', '10.0.0.'.($i % 5 + 1));
        }

        $bloqueo = $this->actingAs($admin)->get(route('seguridad.index'))->assertOk()->viewData('bloqueos')
            ->first(fn ($b) => $b->ip === null);
        $this->assertSame(['ventas', $vendedor->id_usuario], [$bloqueo->login, $bloqueo->id_usuario]);

        $this->actingAs($admin)->post(route('seguridad.desbloquear'), ['login' => 'ventas', 'ip' => ''])->assertSessionHas('status');
        $this->assertNull($vendedor->fresh()->bloqueado_hasta);

        auth()->logout();
        $this->post('/login', ['login' => 'ventas', 'password' => 'Clave-Segura-2026'])->assertRedirect();
        $this->assertAuthenticatedAs($vendedor->fresh());
    }

    public function test_no_se_cree_un_x_forwarded_for_de_cualquiera(): void
    {
        $this->crearUsuario();

        // Desde una IP pública que no es proxy, inventarse otra IP en cada intento no reinicia el contador.
        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])
                ->withHeader('X-Forwarded-For', '198.51.100.'.$i)
                ->post('/login', ['login' => 'admin', 'password' => 'mala']);
        }

        $this->entrar('admin', '203.0.113.5')->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_recuperar_contrasena_no_delata_el_correo_ni_cuando_ya_se_pidio(): void
    {
        Notification::fake();
        $this->crearUsuario();

        // El segundo pedido de una cuenta que existe lo frena el broker (RESET_THROTTLED):
        // la respuesta debe ser la misma que para un correo que no existe.
        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'admin@nexus.test']);
        $repetido = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'admin@nexus.test']);
        $inexistente = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'nadie@nexus.test']);

        $repetido->assertSessionHasNoErrors()->assertSessionHas('status', $inexistente->getSession()->get('status'));
    }

    public function test_recuperar_contrasena_tiene_limite_por_correo_y_por_ip(): void
    {
        Notification::fake();
        $usuario = $this->crearUsuario();

        for ($i = 0; $i < 3; $i++) {
            $this->travel(2)->minutes();
            $this->from('/forgot-password')->post('/forgot-password', ['email' => 'admin@nexus.test'])->assertSessionHasNoErrors();
        }
        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'admin@nexus.test'])->assertSessionHasErrors('email');
        Notification::assertSentToTimes($usuario, ResetPassword::class, 3);

        // Por IP: 10 en total (ya van 3; el frenado no cuenta), con correos distintos.
        for ($i = 0; $i < 7; $i++) {
            $this->from('/forgot-password')->post('/forgot-password', ['email' => "otro{$i}@nexus.test"])->assertSessionHasNoErrors();
        }
        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'otro9@nexus.test'])->assertSessionHasErrors('email');
    }

    public function test_cambiar_mi_contrasena_usuario_o_correo_pide_la_actual(): void
    {
        $yo = $this->crearUsuario();
        $idRol = (int) DB::table('usuario_rol')->where('id_usuario', $yo->id_usuario)->value('id_rol');
        $base = ['nombre_completo' => 'Admin', 'username' => 'admin', 'email' => 'admin@nexus.test', 'id_rol' => $idRol];
        $clave = ['password' => 'Tomada-Cuenta-2027!', 'password_confirmation' => 'Tomada-Cuenta-2027!'];

        // Sin la actual, o con una incorrecta: no cambia nada.
        $this->actingAs($yo)->put(route('usuarios.update', $yo->id_usuario), $base + $clave)->assertSessionHasErrors('password_actual');
        $this->actingAs($yo)->put(route('usuarios.update', $yo->id_usuario), ['email' => 'atacante@mal.test'] + $base)->assertSessionHasErrors('password_actual');
        $this->actingAs($yo)->put(route('usuarios.update', $yo->id_usuario), ['username' => 'otro'] + $base + ['password_actual' => 'mala'])
            ->assertSessionHasErrors('password_actual');
        $yo->refresh();
        $this->assertSame(['admin', 'admin@nexus.test'], [$yo->username, $yo->email]);
        $this->assertTrue(Hash::check('Clave-Segura-2026', $yo->password_hash));

        // Otros datos (el nombre) no la piden.
        $this->actingAs($yo)->put(route('usuarios.update', $yo->id_usuario), ['nombre_completo' => 'Yo mismo'] + $base)->assertSessionHasNoErrors();

        // Con la actual, sí.
        $this->actingAs($yo)->put(route('usuarios.update', $yo->id_usuario), $base + $clave + ['password_actual' => 'Clave-Segura-2026'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Tomada-Cuenta-2027!', $yo->fresh()->password_hash));
    }

    public function test_el_administrador_no_necesita_la_contrasena_del_otro(): void
    {
        $admin = $this->crearUsuario();
        $otro = $this->crearUsuario(['username' => 'ventas', 'email' => 'ventas@nexus.test'], 'Ventas');
        $idRol = (int) DB::table('usuario_rol')->where('id_usuario', $otro->id_usuario)->value('id_rol');

        $this->actingAs($admin)->put(route('usuarios.update', $otro->id_usuario), [
            'nombre_completo' => 'Ventas', 'username' => 'ventas2', 'email' => 'ventas2@nexus.test', 'id_rol' => $idRol,
        ])->assertSessionHasNoErrors();

        $this->assertSame('ventas2', Usuario::find($otro->id_usuario)->username);
    }

    public function test_recordar_sesion_dura_30_dias(): void
    {
        $this->crearUsuario();

        $respuesta = $this->post('/login', ['login' => 'admin', 'password' => 'Clave-Segura-2026', 'remember' => 'on']);

        $cookie = collect($respuesta->headers->getCookies())->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $cookie->getExpiresTime(), 60);
    }

    public function test_el_dashboard_no_muestra_montos_sin_permiso(): void
    {
        $vendedor = $this->crearUsuario(['username' => 'ventas', 'email' => 'ventas@nexus.test'], 'Ventas');
        DB::table('factura')->insert(['id_empresa' => 1, 'id_cliente' => DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Cliente', 'activo' => 1]), 'id_serie' => 1, 'numero_completo' => 'A-1', 'estado' => 'EMITIDA', 'total' => 98765.43, 'saldo_pendiente' => 98765.43, 'fecha_emision' => now()]);

        $respuesta = $this->actingAs($vendedor)->get(route('dashboard'))->assertOk();

        $respuesta->assertDontSee('Cartera por cobrar')->assertDontSee('98,765.43')->assertDontSee('98765.43');
        $this->assertSame([], $respuesta->viewData('tarjetas'));
        $this->assertSame([], $respuesta->viewData('analitica')); // nada llega al @json de las gráficas

        $this->darPermisos($vendedor, ['facturas.ver']);
        $this->actingAs($vendedor->fresh())->get(route('dashboard'))->assertSee('Cartera por cobrar')->assertSee('98,765.43');
    }
}
