<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use App\Support\Contrasenas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Política de contraseñas: largo, complejidad, historial, vencimiento y cambio obligatorio. */
class ContrasenasTest extends TestCase
{
    use EsquemaNexus;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function politica(array $datos): void
    {
        DB::table('ConfiguracionSistema')->update($datos);
        Contrasenas::olvidar();
    }

    private function cambiarMiContrasena(Usuario $usuario, string $actual, string $nueva)
    {
        return $this->actingAs($usuario)->put('/user/password', [
            'current_password' => $actual, 'password' => $nueva, 'password_confirmation' => $nueva,
        ]);
    }

    public function test_solo_el_administrador_guarda_la_politica_y_queda_en_la_bitacora(): void
    {
        $admin = $this->crearUsuario();
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');
        $datos = ['minimo' => 10, 'mayusculas' => '1', 'numeros' => '1', 'simbolos' => '0', 'vence_dias' => 90, 'historial' => 5];

        $this->actingAs($vendedor)->put(route('seguridad.contrasenas'), $datos)->assertForbidden();
        $this->actingAs($admin)->put(route('seguridad.contrasenas'), $datos)->assertSessionHasNoErrors();

        $this->assertSame(['minimo' => 10, 'mayusculas' => true, 'numeros' => true, 'simbolos' => false, 'vence' => 90, 'historial' => 5], Contrasenas::politica());
        $this->assertSame('Mínimo 10 caracteres, con mayúsculas y minúsculas y números. No puede ser ninguna de tus últimas 5.', Contrasenas::requisitos());
        $this->actingAs($admin)->get(route('seguridad.index'))->assertOk()->assertSee('Política de contraseñas');
        $cambio = json_decode(DB::table('auditoria_cambio')->where('tabla_afectada', 'ConfiguracionSistema')->value('datos_nuevos'), true);
        $this->assertSame(['passwordMinimo' => '10', 'passwordSimbolos' => '0', 'passwordVenceDias' => '90', 'passwordHistorial' => '5'], $cambio);

        $this->actingAs($admin)->put(route('seguridad.contrasenas'), ['minimo' => 6] + $datos)->assertSessionHasErrors('minimo');
    }

    public function test_la_regla_sigue_la_politica(): void
    {
        $usuario = $this->crearUsuario();

        // Por defecto exige símbolos.
        $this->cambiarMiContrasena($usuario, 'Clave-Segura-2026', 'SinSimbolos2027')->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->politica(['passwordMinimo' => 8, 'passwordSimbolos' => false]);
        $this->cambiarMiContrasena($usuario, 'Clave-Segura-2026', 'SinSimbolos2027')->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('SinSimbolos2027', $usuario->fresh()->password_hash));
    }

    public function test_no_deja_repetir_las_ultimas_contrasenas(): void
    {
        $this->politica(['passwordHistorial' => 3]);
        $usuario = $this->crearUsuario();

        $this->cambiarMiContrasena($usuario, 'Clave-Segura-2026', 'Clave-Segura-2026')->assertSessionHasErrorsIn('updatePassword', 'password');
        $this->cambiarMiContrasena($usuario, 'Clave-Segura-2026', 'Segunda-Clave-2027!')->assertSessionHasNoErrors();
        $this->cambiarMiContrasena($usuario->fresh(), 'Segunda-Clave-2027!', 'Tercera-Clave-2027!')->assertSessionHasNoErrors();

        // La primera todavía está entre las últimas 3; la anterior también.
        $this->cambiarMiContrasena($usuario->fresh(), 'Tercera-Clave-2027!', 'Clave-Segura-2026')->assertSessionHasErrorsIn('updatePassword', 'password');
        $this->cambiarMiContrasena($usuario->fresh(), 'Tercera-Clave-2027!', 'Segunda-Clave-2027!')->assertSessionHasErrorsIn('updatePassword', 'password');
        $this->assertSame(2, DB::table('historial_password')->where('id_usuario', $usuario->id_usuario)->count());

        // Con una cuarta, la primera ya salió de las últimas 3.
        $this->cambiarMiContrasena($usuario->fresh(), 'Tercera-Clave-2027!', 'Cuarta-Clave-2027!')->assertSessionHasNoErrors();
        $this->cambiarMiContrasena($usuario->fresh(), 'Cuarta-Clave-2027!', 'Clave-Segura-2026')->assertSessionHasNoErrors();
    }

    public function test_el_administrador_tampoco_puede_reponer_una_contrasena_reciente(): void
    {
        $this->politica(['passwordHistorial' => 2]);
        $admin = $this->crearUsuario();
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');
        $idRol = DB::table('usuario_rol')->where('id_usuario', $vendedor->id_usuario)->value('id_rol');
        $datos = ['nombre_completo' => 'Vendedor', 'username' => 'v', 'email' => 'v@nexus.test', 'id_rol' => $idRol,
            'password' => 'Clave-Segura-2026', 'password_confirmation' => 'Clave-Segura-2026'];

        $this->actingAs($admin)->put(route('usuarios.update', $vendedor->id_usuario), $datos)->assertSessionHasErrors('password');
    }

    public function test_la_contrasena_vencida_obliga_a_cambiarla(): void
    {
        $this->politica(['passwordVenceDias' => 30]);
        $usuario = $this->crearUsuario();
        $usuario->forceFill(['password_cambiado_at' => now()->subDays(31)])->save();

        $this->actingAs($usuario)->get(route('dashboard'))->assertRedirect(route('cuenta.seguridad'))->assertSessionHas('aviso');
        $this->actingAs($usuario)->get(route('cuenta.seguridad'))->assertOk()->assertSee('Debes cambiar tu contraseña');

        $this->cambiarMiContrasena($usuario, 'Clave-Segura-2026', 'Nueva-Clave-2027!')->assertSessionHasNoErrors();
        $this->actingAs($usuario->fresh())->get(route('dashboard'))->assertOk();
        $this->assertTrue($usuario->fresh()->password_cambiado_at->isToday());
    }

    public function test_avisa_unos_dias_antes_de_que_venza(): void
    {
        $this->politica(['passwordVenceDias' => 30]);
        $usuario = $this->crearUsuario();
        $usuario->forceFill(['password_cambiado_at' => now()->subDays(26)])->save();

        $this->actingAs($usuario)->get(route('dashboard'))->assertOk()->assertSee('Tu contraseña vence en 4 días');
        $this->actingAs($usuario)->get(route('dashboard'))->assertOk()->assertDontSee('Tu contraseña vence en'); // una vez por sesión
    }

    public function test_si_el_administrador_la_pone_el_usuario_debe_cambiarla_al_entrar(): void
    {
        $admin = $this->crearUsuario();
        $idRol = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Ventas', 'activo' => true]);

        $this->actingAs($admin)->get(route('usuarios.create'))->assertOk()->assertSee('Pedir que cambie la contraseña al entrar');
        $this->actingAs($admin)->post(route('usuarios.store'), [
            'nombre_completo' => 'Nuevo', 'username' => 'nuevo', 'email' => 'nuevo@nexus.test', 'id_rol' => $idRol,
            'password' => 'Temporal-Clave-2026!', 'password_confirmation' => 'Temporal-Clave-2026!', 'debe_cambiar_password' => '1',
        ])->assertSessionHasNoErrors();
        $nuevo = Usuario::query()->where('username', 'nuevo')->firstOrFail();
        $this->assertTrue($nuevo->debe_cambiar_password);
        $this->assertNotNull($nuevo->password_cambiado_at);

        auth()->logout();
        $this->post('/login', ['login' => 'nuevo', 'password' => 'Temporal-Clave-2026!']);
        $this->get(route('dashboard'))->assertRedirect(route('cuenta.seguridad'));

        $this->cambiarMiContrasena($nuevo, 'Temporal-Clave-2026!', 'Mi-Propia-Clave-2027!')->assertSessionHasNoErrors();
        $this->assertFalse($nuevo->fresh()->debe_cambiar_password);
        $this->actingAs($nuevo->fresh())->get(route('dashboard'))->assertOk();

        // El administrador puede volver a pedirlo sin poner otra contraseña.
        $this->actingAs($admin)->put(route('usuarios.update', $nuevo->id_usuario), [
            'nombre_completo' => 'Nuevo', 'username' => 'nuevo', 'email' => 'nuevo@nexus.test', 'id_rol' => $idRol, 'debe_cambiar_password' => '1',
        ])->assertRedirect(route('usuarios.index'))->assertSessionHasNoErrors();
        $this->assertTrue($nuevo->fresh()->debe_cambiar_password);
    }
}
