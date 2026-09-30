<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 3b: módulo Usuarios en Blade con permisos CONFIG.USUARIOS.*. */
class UsuariosTest extends TestCase
{
    use EsquemaNexus;

    private function idRol(Usuario $usuario): int
    {
        return (int) DB::table('usuario_rol')->where('id_usuario', $usuario->id_usuario)->value('id_rol');
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'nombre_completo' => 'Ana López',
            'username' => ' Ana.Lopez ',
            'email' => 'ANA@Nexus.test',
            'id_rol' => null,
            'password' => 'Clave-Nueva-2027!',
            'password_confirmation' => 'Clave-Nueva-2027!',
        ], $cambios);
    }

    public function test_lista_solo_los_usuarios_de_mi_empresa_y_busca(): void
    {
        $admin = $this->crearUsuario();
        $this->crearUsuario(['username' => 'ventas', 'email' => 'ventas@nexus.test', 'nombre_completo' => 'Vendedor Uno'], 'Ventas');
        $this->crearUsuario(['id_empresa' => 2, 'username' => 'otra', 'email' => 'otra@nexus.test', 'nombre_completo' => 'De Otra Empresa']);

        $this->actingAs($admin)->get(route('usuarios.index'))
            ->assertOk()->assertSee('Vendedor Uno')->assertDontSee('De Otra Empresa');

        $this->actingAs($admin)->get(route('usuarios.index', ['buscar' => 'ventas']))
            ->assertSee('Vendedor Uno')->assertDontSee('Administrador Nexus</td>', false);
    }

    public function test_los_permisos_controlan_cada_accion(): void
    {
        $lector = $this->crearUsuario(['username' => 'lector', 'email' => 'lector@nexus.test'], 'Consulta');
        $sinPermiso = $this->crearUsuario(['username' => 'nadie', 'email' => 'nadie@nexus.test'], 'Bodega');
        $this->darPermisos($lector, ['CONFIG.USUARIOS.VER']);

        $this->actingAs($sinPermiso)->get(route('usuarios.index'))->assertForbidden();
        $this->actingAs($lector)->get(route('usuarios.index'))->assertOk()->assertDontSee('Nuevo usuario')->assertDontSee('Editar');
        $this->actingAs($lector)->get(route('usuarios.create'))->assertForbidden();
        $this->actingAs($lector)->patch(route('usuarios.estado', $sinPermiso->id_usuario))->assertForbidden();
    }

    public function test_crea_un_usuario_con_rol_y_contrasena_segura(): void
    {
        $admin = $this->crearUsuario();
        $idRol = $this->idRol($admin);

        $this->actingAs($admin)->post(route('usuarios.store'), $this->datos(['id_rol' => $idRol, 'password' => 'corta', 'password_confirmation' => 'corta']))
            ->assertSessionHasErrors('password');

        $this->actingAs($admin)->post(route('usuarios.store'), $this->datos(['id_rol' => $idRol]))
            ->assertRedirect(route('usuarios.index'))->assertSessionHasNoErrors();

        $nuevo = Usuario::where('username', 'ana.lopez')->firstOrFail();
        $this->assertSame('ana@nexus.test', $nuevo->email);
        $this->assertSame(1, (int) $nuevo->id_empresa);
        $this->assertTrue(Hash::check('Clave-Nueva-2027!', $nuevo->password_hash));
        $this->assertSame($idRol, $this->idRol($nuevo));
    }

    public function test_no_acepta_roles_de_otra_empresa_ni_usuarios_con_espacios(): void
    {
        $admin = $this->crearUsuario();
        $rolAjeno = DB::table('rol')->insertGetId(['id_empresa' => 2, 'nombre' => 'Ajeno', 'activo' => true]);

        $this->actingAs($admin)->post(route('usuarios.store'), $this->datos(['id_rol' => $rolAjeno, 'username' => 'con espacio']))
            ->assertSessionHasErrors(['id_rol', 'username']);
    }

    public function test_editar_sin_contrasena_la_conserva_y_con_contrasena_cierra_sus_sesiones(): void
    {
        $admin = $this->crearUsuario();
        $otro = $this->crearUsuario(['username' => 'ventas', 'email' => 'ventas@nexus.test'], 'Ventas');
        DB::table('sessions')->insert(['id' => 's-ventas', 'user_id' => $otro->id_usuario, 'payload' => '', 'last_activity' => time()]);
        $base = ['nombre_completo' => 'Vendedor Editado', 'username' => 'ventas', 'email' => 'ventas@nexus.test', 'id_rol' => $this->idRol($otro)];

        $this->actingAs($admin)->put(route('usuarios.update', $otro->id_usuario), $base)->assertSessionHasNoErrors();
        $this->assertSame('Vendedor Editado', $otro->fresh()->nombre_completo);
        $this->assertTrue(Hash::check('Clave-Segura-2026', $otro->fresh()->password_hash));
        $this->assertSame(1, DB::table('sessions')->count());

        $this->actingAs($admin)->put(route('usuarios.update', $otro->id_usuario), $base + ['password' => 'Otra-Clave-2027!', 'password_confirmation' => 'Otra-Clave-2027!'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Otra-Clave-2027!', $otro->fresh()->password_hash));
        $this->assertSame(0, DB::table('sessions')->count());
        $this->assertSame(1, $this->auditoria('RESET_PASSWORD'));
    }

    public function test_no_puedo_cambiar_mi_rol_desactivarme_ni_eliminarme(): void
    {
        $admin = $this->crearUsuario();
        $otroRol = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Ventas', 'activo' => true]);

        $this->actingAs($admin)->put(route('usuarios.update', $admin->id_usuario), [
            'nombre_completo' => 'Yo', 'username' => 'admin', 'email' => 'admin@nexus.test', 'id_rol' => $otroRol,
        ])->assertSessionHasErrors('id_rol');

        $this->actingAs($admin)->patch(route('usuarios.estado', $admin->id_usuario))->assertSessionHasErrors('usuario');
        $this->actingAs($admin)->delete(route('usuarios.destroy', $admin->id_usuario))->assertSessionHasErrors('usuario');
        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_desactivar_cierra_sus_sesiones_y_eliminar_es_logico(): void
    {
        $admin = $this->crearUsuario();
        $otro = $this->crearUsuario(['username' => 'ventas', 'email' => 'ventas@nexus.test'], 'Ventas');
        DB::table('sessions')->insert(['id' => 's-ventas', 'user_id' => $otro->id_usuario, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($admin)->patch(route('usuarios.estado', $otro->id_usuario))->assertSessionHas('status');
        $this->assertFalse($otro->fresh()->activo);
        $this->assertSame(0, DB::table('sessions')->count());

        $this->actingAs($admin)->delete(route('usuarios.destroy', $otro->id_usuario))->assertRedirect(route('usuarios.index'));
        $this->assertNull(Usuario::find($otro->id_usuario));
        $this->assertNotNull(Usuario::withTrashed()->find($otro->id_usuario));
    }

    public function test_no_puedo_tocar_usuarios_de_otra_empresa(): void
    {
        $admin = $this->crearUsuario();
        $ajeno = $this->crearUsuario(['id_empresa' => 2, 'username' => 'otra', 'email' => 'otra@nexus.test']);

        $this->actingAs($admin)->get(route('usuarios.edit', $ajeno->id_usuario))->assertNotFound();
        $this->actingAs($admin)->patch(route('usuarios.estado', $ajeno->id_usuario))->assertNotFound();
        $this->assertTrue($ajeno->fresh()->activo);
    }

    public function test_la_api_de_usuarios_ya_no_existe(): void
    {
        $this->actingAs($this->crearUsuario())->getJson('/api/v1/usuarios')->assertNotFound();
    }

    public function test_sube_la_foto_de_perfil_a_contabo(): void
    {
        config(['filesystems.disks.contabo' => array_merge(config('filesystems.disks.contabo'), [
            'key' => 'k', 'secret' => 's', 'bucket' => 'b', 'url' => 'https://cdn.test/nexus',
        ])]);
        Storage::fake('contabo', ['url' => 'https://cdn.test/nexus']);
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->put(route('usuarios.update', $admin->id_usuario), [
            'nombre_completo' => 'Admin', 'username' => 'admin', 'email' => 'admin@nexus.test', 'id_rol' => $this->idRol($admin),
            'foto' => UploadedFile::fake()->image('yo.png', 200, 200),
        ])->assertRedirect(route('usuarios.index'))->assertSessionHasNoErrors();

        $url = $admin->fresh()->avatar_url;
        $this->assertStringStartsWith('https://cdn.test/nexus/usuarios/', $url);
        Storage::disk('contabo')->assertExists('usuarios/'.basename($url));
    }

    public function test_sin_contabo_configurado_guarda_el_usuario_y_avisa_de_la_foto(): void
    {
        config(['filesystems.disks.contabo.key' => null]);
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->put(route('usuarios.update', $admin->id_usuario), [
            'nombre_completo' => 'Nombre Nuevo', 'username' => 'admin', 'email' => 'admin@nexus.test', 'id_rol' => $this->idRol($admin),
            'foto' => UploadedFile::fake()->image('yo.png'),
        ])->assertRedirect(route('usuarios.edit', $admin->id_usuario))->assertSessionHasErrors('foto');

        $this->assertSame('Nombre Nuevo', $admin->fresh()->nombre_completo);
        $this->assertNull($admin->fresh()->avatar_url);
    }
}
