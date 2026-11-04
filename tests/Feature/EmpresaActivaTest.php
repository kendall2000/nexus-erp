<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 3: cambiar de empresa desde la barra superior; permisos y roles por empresa. */
class EmpresaActivaTest extends TestCase
{
    use EsquemaNexus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        DB::table('empresa')->insert([
            ['id_empresa' => 2, 'nombre_comercial' => 'Adinser', 'activo' => true],
            ['id_empresa' => 3, 'nombre_comercial' => 'ADESA', 'activo' => true],
            ['id_empresa' => 4, 'nombre_comercial' => 'Cerrada', 'activo' => false],
        ]);
    }

    private function rol(int $idEmpresa, string $nombre, array $permisos = []): int
    {
        $id = DB::table('rol')->insertGetId(['id_empresa' => $idEmpresa, 'nombre' => $nombre, 'activo' => true]);
        foreach ($permisos as $codigo) {
            DB::table('rol_permiso')->insert(['id_rol' => $id, 'id_permiso' => $this->permiso($codigo)]);
        }

        return $id;
    }

    private function darRol(Usuario $usuario, int $idRol): void
    {
        DB::table('usuario_rol')->insert(['id_usuario' => $usuario->id_usuario, 'id_rol' => $idRol]);
    }

    public function test_el_administrador_cambia_de_empresa_y_ve_solo_sus_datos(): void
    {
        $admin = $this->crearUsuario();
        $this->crearUsuario(['id_empresa' => 2, 'username' => 'luis', 'email' => 'luis@nexus.test', 'nombre_completo' => 'Luis de Adinser'], 'Ventas');

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertSee('Trabajar en')->assertSee('Adinser')->assertSee('ADESA')->assertDontSee('Cerrada');
        $this->get(route('usuarios.index'))->assertOk()->assertDontSee('Luis de Adinser');

        $this->post(route('empresa-activa.cambiar'), ['id_empresa' => 2])
            ->assertRedirect(route('dashboard'))->assertSessionHas('status', 'Ahora trabajas en Adinser.');
        $this->get(route('usuarios.index'))->assertOk()->assertSee('Luis de Adinser')->assertDontSee('admin@nexus.test');

        // La empresa activa solo vive en la sesión: la columna del usuario no cambia.
        $this->assertSame(1, (int) DB::table('usuario')->where('id_usuario', $admin->id_usuario)->value('id_empresa'));

        $this->post(route('empresa-activa.cambiar'), ['id_empresa' => 4])->assertForbidden(); // inactiva
        $this->post(route('empresa-activa.cambiar'), ['id_empresa' => 1])->assertRedirect();
        $this->assertNull(session('empresa_activa'));
        $this->get(route('usuarios.index'))->assertSee('admin@nexus.test')->assertDontSee('Luis de Adinser');
    }

    public function test_guardar_al_usuario_no_escribe_la_empresa_activa(): void
    {
        $admin = $this->crearUsuario();
        $admin->usarEmpresa(2);
        $admin->update(['nombre_completo' => 'Admin renombrado']);

        $fila = DB::table('usuario')->where('id_usuario', $admin->id_usuario)->first();
        $this->assertSame([1, 'Admin renombrado'], [(int) $fila->id_empresa, $fila->nombre_completo]);
        $this->assertSame([2, 1], [(int) $admin->id_empresa, $admin->idEmpresaPrincipal()]);
    }

    public function test_sin_rol_en_otra_empresa_no_puede_cambiarse(): void
    {
        $vendedor = $this->crearUsuario(['username' => 'ventas', 'email' => 'ventas@nexus.test'], 'Ventas');
        $this->darPermisos($vendedor, ['usuarios.ver']);

        $this->actingAs($vendedor)->get(route('dashboard'))->assertOk()->assertDontSee('Trabajar en');
        $this->post(route('empresa-activa.cambiar'), ['id_empresa' => 2])->assertForbidden();
        $this->assertNull(session('empresa_activa'));
    }

    public function test_en_cada_empresa_usa_solo_los_permisos_de_su_rol_ahi(): void
    {
        $ana = $this->crearUsuario(['username' => 'ana', 'email' => 'ana@nexus.test'], 'Ventas');
        $this->darPermisos($ana, ['usuarios.ver']);                     // rol de la empresa 1
        $this->darRol($ana, $this->rol(2, 'Consulta', ['roles.ver']));   // rol de la empresa 2
        DB::table('usuario_permiso')->insert(['id_usuario' => $ana->id_usuario, 'id_permiso' => $this->permiso('sucursales.ver')]); // extra

        $this->actingAs($ana)->get(route('usuarios.index'))->assertOk();
        $this->get(route('roles.index'))->assertForbidden();
        $this->assertTrue(Usuario::find($ana->id_usuario)->puede('sucursales.ver'));

        $this->post(route('empresa-activa.cambiar'), ['id_empresa' => 2])->assertRedirect();
        $this->get(route('roles.index'))->assertOk();
        $this->get(route('usuarios.index'))->assertForbidden();

        $enAdinser = Usuario::find($ana->id_usuario);
        $enAdinser->usarEmpresa(2);
        $this->assertFalse($enAdinser->puede('sucursales.ver'));          // los extras solo en su empresa principal
        $this->assertTrue($enAdinser->puedeEn(1, 'usuarios.ver'));
        $this->assertFalse($enAdinser->puedeEn(3, 'usuarios.ver'));       // ADESA: sin rol
        $this->assertSame(2, (int) $enAdinser->id_empresa);               // puedeEn no cambia la empresa activa
    }

    public function test_si_le_quitan_el_rol_vuelve_a_su_empresa(): void
    {
        $ana = $this->crearUsuario(['username' => 'ana', 'email' => 'ana@nexus.test'], 'Ventas');
        $this->darPermisos($ana, ['usuarios.ver']);
        $rolAdinser = $this->rol(2, 'Consulta', ['usuarios.ver']);
        $this->darRol($ana, $rolAdinser);

        $this->actingAs($ana)->post(route('empresa-activa.cambiar'), ['id_empresa' => 2]);
        $this->assertSame(2, session('empresa_activa'));
        DB::table('rol')->where('id_rol', $rolAdinser)->update(['activo' => false]);

        $this->get(route('usuarios.index'))->assertOk()->assertSee('ana@nexus.test');
        $this->assertNull(session('empresa_activa'));
    }

    public function test_el_administrador_da_acceso_a_otra_empresa_sin_perder_los_demas_roles(): void
    {
        $admin = $this->crearUsuario();
        $ventas = $this->rol(1, 'Ventas');
        $soporte = $this->rol(1, 'Soporte');
        $consulta2 = $this->rol(2, 'Consulta');
        $consulta3 = $this->rol(3, 'Consulta');
        $luis = Usuario::create(['id_empresa' => 1, 'username' => 'luis', 'email' => 'luis@nexus.test', 'password_hash' => 'x', 'nombre_completo' => 'Luis', 'activo' => true]);
        $this->darRol($luis, $ventas);
        $this->darRol($luis, $consulta3);

        $this->actingAs($admin)->get(route('usuarios.edit', $luis->id_usuario))->assertOk()
            ->assertSee('Acceso a otras empresas')->assertSee('name="accesos[2]"', false);
        $datos = ['nombre_completo' => 'Luis', 'username' => 'luis', 'email' => 'luis@nexus.test'];

        // Rol de otra empresa en el select de la empresa 2: se rechaza sin guardar nada.
        $this->put(route('usuarios.update', $luis->id_usuario), $datos + ['id_rol' => $soporte, 'accesos' => [2 => $consulta3]])
            ->assertSessionHasErrors('accesos.2');
        $this->assertEqualsCanonicalizing([$ventas, $consulta3], DB::table('usuario_rol')->where('id_usuario', $luis->id_usuario)->pluck('id_rol')->map(fn ($id) => (int) $id)->all());

        // Cambia su rol en la empresa 1, le da Adinser y le quita ADESA.
        $this->put(route('usuarios.update', $luis->id_usuario), $datos + ['id_rol' => $soporte, 'accesos' => [2 => $consulta2, 3 => '']])
            ->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing([$soporte, $consulta2], DB::table('usuario_rol')->where('id_usuario', $luis->id_usuario)->pluck('id_rol')->map(fn ($id) => (int) $id)->all());

        // Sin el campo «accesos» (p. ej. lo edita alguien sin acceso total) los otros roles se conservan.
        $this->put(route('usuarios.update', $luis->id_usuario), $datos + ['id_rol' => $ventas])->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing([$ventas, $consulta2], DB::table('usuario_rol')->where('id_usuario', $luis->id_usuario)->pluck('id_rol')->map(fn ($id) => (int) $id)->all());
    }

    public function test_quien_no_es_administrador_no_ve_ni_da_accesos(): void
    {
        $jefe = $this->crearUsuario(['username' => 'jefe', 'email' => 'jefe@nexus.test'], 'Jefe');
        $this->darPermisos($jefe, ['usuarios.ver', 'usuarios.editar']);
        $ventas = $this->rol(1, 'Ventas');
        $admin2 = $this->rol(2, 'Administrador');
        $luis = Usuario::create(['id_empresa' => 1, 'username' => 'luis', 'email' => 'luis@nexus.test', 'password_hash' => 'x', 'nombre_completo' => 'Luis', 'activo' => true]);
        $this->darRol($luis, $ventas);

        $this->actingAs($jefe)->get(route('usuarios.edit', $luis->id_usuario))->assertOk()->assertDontSee('Acceso a otras empresas');
        $this->put(route('usuarios.update', $luis->id_usuario), ['nombre_completo' => 'Luis', 'username' => 'luis', 'email' => 'luis@nexus.test', 'id_rol' => $ventas, 'accesos' => [2 => $admin2]])
            ->assertSessionHasNoErrors();
        $this->assertSame([$ventas], DB::table('usuario_rol')->where('id_usuario', $luis->id_usuario)->pluck('id_rol')->map(fn ($id) => (int) $id)->all());
    }
}
