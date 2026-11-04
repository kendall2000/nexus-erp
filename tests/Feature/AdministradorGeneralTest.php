<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use App\Support\MenuLateral;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/**
 * Lo que comparten todas las empresas (configuración, reglas de sesión y contraseñas, módulos,
 * geografía y accesos a otras empresas) solo lo cambia el administrador general: quien tiene
 * acceso total en su empresa principal. El administrador de otra empresa administra solo esa.
 */
class AdministradorGeneralTest extends TestCase
{
    use EsquemaNexus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        DB::table('empresa')->insert(['id_empresa' => 2, 'nombre_comercial' => 'Adinser', 'activo' => true]);
    }

    /** Usuario de la empresa 1 con rol normal ahí y Administrador en Adinser, trabajando en Adinser. */
    private function administradorDeAdinser(): Usuario
    {
        $marta = $this->crearUsuario(['username' => 'marta', 'email' => 'marta@nexus.test', 'nombre_completo' => 'Marta'], 'Ventas');
        $idRol = DB::table('rol')->insertGetId(['id_empresa' => 2, 'nombre' => 'Administrador', 'activo' => true]);
        DB::table('usuario_rol')->insert(['id_usuario' => $marta->id_usuario, 'id_rol' => $idRol]);
        $this->actingAs($marta)->post(route('empresa-activa.cambiar'), ['id_empresa' => 2])->assertRedirect();

        return $marta;
    }

    public function test_el_administrador_general_cambia_lo_compartido(): void
    {
        $admin = $this->crearUsuario();
        $this->assertTrue($admin->esAdministradorGeneral());

        $this->actingAs($admin)->get(route('configuracion.edit'))->assertOk();
        $this->get(route('modulos.index'))->assertOk();
        $this->get(route('seguridad.index'))->assertOk()->assertDontSee('solo el administrador general puede cambiarlo');
        $this->put(route('seguridad.guardar'), ['max_intentos' => 7, 'bloqueo_minutos' => 20, 'sesion_expira_min' => 60])->assertSessionHasNoErrors();
        $this->assertSame(7, (int) DB::table('ConfiguracionSistema')->value('maxIntentosSesion'));
    }

    public function test_el_administrador_de_otra_empresa_no_cambia_lo_compartido(): void
    {
        $marta = $this->administradorDeAdinser();
        $antes = (int) DB::table('ConfiguracionSistema')->value('maxIntentosSesion');

        $enAdinser = Usuario::find($marta->id_usuario);
        $enAdinser->usarEmpresa(2);
        $this->assertTrue($enAdinser->esAdministrador());
        $this->assertFalse($enAdinser->esAdministradorGeneral());

        $this->get(route('configuracion.edit'))->assertForbidden();
        $this->put(route('configuracion.update'), [])->assertForbidden();
        $this->put(route('seguridad.guardar'), ['max_intentos' => 99, 'bloqueo_minutos' => 1, 'sesion_expira_min' => 1440])->assertForbidden();
        $this->put(route('seguridad.contrasenas'), ['minimo' => 8, 'vence_dias' => 0, 'historial' => 0])->assertForbidden();
        $this->get(route('modulos.index'))->assertForbidden();
        $this->get(route('geografia.index'))->assertForbidden();
        $this->assertSame($antes, (int) DB::table('ConfiguracionSistema')->value('maxIntentosSesion'));

        // Lo de su empresa sí lo administra: ve Seguridad y accesos con lo compartido en solo lectura.
        $this->get(route('seguridad.index'))->assertOk()
            ->assertSee('solo el administrador general puede cambiarlo')->assertSee('<fieldset disabled', false)
            ->assertDontSee(route('configuracion.edit'));
    }

    public function test_el_administrador_de_otra_empresa_no_da_accesos_a_otras_empresas(): void
    {
        $marta = $this->administradorDeAdinser();
        $admin1 = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Administrador', 'activo' => true]);
        $consulta2 = DB::table('rol')->insertGetId(['id_empresa' => 2, 'nombre' => 'Consulta', 'activo' => true]);
        $luis = Usuario::create(['id_empresa' => 2, 'username' => 'luis', 'email' => 'luis@nexus.test', 'password_hash' => 'x', 'nombre_completo' => 'Luis', 'activo' => true]);
        DB::table('usuario_rol')->insert(['id_usuario' => $luis->id_usuario, 'id_rol' => $consulta2]);

        $this->get(route('usuarios.edit', $luis->id_usuario))->assertOk()->assertDontSee('Acceso a otras empresas');
        // Intento de crear un administrador en la empresa principal de Marta: se ignora.
        $this->put(route('usuarios.update', $luis->id_usuario), ['nombre_completo' => 'Luis', 'username' => 'luis', 'email' => 'luis@nexus.test', 'id_rol' => $consulta2, 'accesos' => [1 => $admin1]])
            ->assertSessionHasNoErrors();
        $this->assertSame([$consulta2], DB::table('usuario_rol')->where('id_usuario', $luis->id_usuario)->pluck('id_rol')->map(fn ($id) => (int) $id)->all());
    }

    public function test_el_menu_oculta_lo_compartido_al_administrador_de_otra_empresa(): void
    {
        foreach (['configuracion' => 'sistema/configuracion', 'modulos' => 'sistema/modulos', 'sucursales' => 'sistema/sucursales'] as $codigo => $ruta) {
            DB::table('modulo')->insert(['codigo' => $codigo, 'nombre' => ucfirst($codigo), 'ruta' => $ruta, 'grupo' => 'Configuración', 'activo' => true]);
        }
        $rutas = fn (Usuario $u) => MenuLateral::para($u)->flatMap(fn ($g) => $g['items'])->pluck('ruta')->all();

        $this->assertEqualsCanonicalizing(['sistema/configuracion', 'sistema/modulos', 'sistema/sucursales'], $rutas($this->crearUsuario()));

        $marta = $this->administradorDeAdinser();
        $enAdinser = Usuario::find($marta->id_usuario);
        $enAdinser->usarEmpresa(2);
        $this->assertSame(['sistema/sucursales'], $rutas($enAdinser));
    }
}
