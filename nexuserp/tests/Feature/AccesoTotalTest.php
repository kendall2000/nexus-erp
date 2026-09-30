<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Roles de acceso total (Administrador, Superadmin) y anti-escalada al gestionar usuarios. */
class AccesoTotalTest extends TestCase
{
    use EsquemaNexus;

    private function rol(string $nombre, array $permisos = []): int
    {
        $id = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => $nombre, 'activo' => true]);
        foreach ($permisos as $codigo) {
            DB::table('rol_permiso')->insert(['id_rol' => $id, 'id_permiso' => $this->permiso($codigo)]);
        }

        return $id;
    }

    private function datosUsuario(int $idRol, array $cambios = []): array
    {
        return array_merge(['nombre_completo' => 'Ana López', 'username' => 'ana', 'email' => 'ana@nexus.test', 'id_rol' => $idRol,
            'password' => 'Clave-Nueva-2027!', 'password_confirmation' => 'Clave-Nueva-2027!'], $cambios);
    }

    /** Jefe de personal: gestiona usuarios, pero no tiene acceso total. */
    private function jefe(): Usuario
    {
        $jefe = $this->crearUsuario(['username' => 'jefe', 'email' => 'jefe@nexus.test'], 'Jefe');
        $this->darPermisos($jefe, ['usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar', 'productos.ver']);

        return $jefe;
    }

    public function test_superadmin_y_administrador_pueden_todo_sin_permisos(): void
    {
        foreach (['Superadmin', 'SUPER ADMINISTRADOR', 'administrador'] as $i => $nombre) {
            $usuario = $this->crearUsuario(['username' => "u{$i}", 'email' => "u{$i}@nexus.test"], $nombre);

            $this->assertTrue($usuario->esAdministrador(), $nombre);
            $this->assertTrue($usuario->puede('ordenes_compra.aprobar'), $nombre);
            $this->actingAs($usuario)->get(route('productos.index'))->assertOk();
            $this->actingAs($usuario)->get(route('modulos.index'))->assertOk(); // pantalla solo de acceso total
        }
    }

    public function test_un_rol_de_acceso_total_inactivo_no_da_acceso(): void
    {
        $usuario = $this->crearUsuario([], 'Superadmin', rolActivo: false);

        $this->assertFalse($usuario->esAdministrador());
    }

    public function test_solo_quien_tiene_acceso_total_crea_roles_con_ese_nombre(): void
    {
        $jefe = $this->jefe();
        $this->darPermisos($jefe, ['roles.ver', 'roles.crear']);

        $this->actingAs($jefe)->post(route('roles.store'), ['nombre' => 'superadmin'])->assertSessionHasErrors('nombre');
        $this->assertFalse(DB::table('rol')->whereRaw('LOWER(nombre) = ?', ['superadmin'])->exists());

        $this->actingAs($this->crearUsuario())->post(route('roles.store'), ['nombre' => 'Superadmin'])->assertSessionHasNoErrors();
        $this->assertTrue(DB::table('rol')->where('nombre', 'Superadmin')->exists());
    }

    public function test_no_se_asigna_un_rol_con_mas_acceso_que_el_propio(): void
    {
        $jefe = $this->jefe();
        $admin = $this->rol('Superadmin');
        $contador = $this->rol('Contador', ['facturas.ver']);   // el jefe no tiene facturas.ver
        $bodeguero = $this->rol('Bodeguero', ['productos.ver']);

        $this->actingAs($jefe)->post(route('usuarios.store'), $this->datosUsuario($admin))->assertSessionHasErrors('id_rol');
        $this->actingAs($jefe)->post(route('usuarios.store'), $this->datosUsuario($contador))->assertSessionHasErrors('id_rol');
        $this->assertFalse(Usuario::where('username', 'ana')->exists());

        $this->actingAs($jefe)->get(route('usuarios.create'))->assertOk()->assertSee('Bodeguero')->assertDontSee('Superadmin')->assertDontSee('Contador');
        $this->actingAs($jefe)->post(route('usuarios.store'), $this->datosUsuario($bodeguero))->assertSessionHasNoErrors();

        // Tampoco al editar: no puede subir de rol a quien ya gestiona.
        $ana = Usuario::where('username', 'ana')->firstOrFail();
        $this->actingAs($jefe)->put(route('usuarios.update', $ana->id_usuario), $this->datosUsuario($admin, ['password' => '', 'password_confirmation' => '']))
            ->assertSessionHasErrors('id_rol');
        $this->assertTrue($ana->fresh()->roles->contains('id_rol', $bodeguero));
    }

    public function test_no_gestiona_usuarios_con_mas_acceso(): void
    {
        $jefe = $this->jefe();
        $admin = $this->crearUsuario(['username' => 'root', 'email' => 'root@nexus.test'], 'Superadmin');
        $contador = $this->crearUsuario(['username' => 'conta', 'email' => 'conta@nexus.test'], 'Contador');
        $this->darPermisos($contador, ['facturas.ver']);

        foreach ([$admin, $contador] as $otro) {
            $id = $otro->id_usuario;
            $this->actingAs($jefe)->get(route('usuarios.edit', $id))->assertForbidden();
            // Cambiarle la contraseña sería quedarse con su cuenta.
            $this->actingAs($jefe)->put(route('usuarios.update', $id), $this->datosUsuario($otro->roles->first()->id_rol, [
                'username' => $otro->username, 'email' => $otro->email, 'password' => 'Robada-Clave-2027!', 'password_confirmation' => 'Robada-Clave-2027!',
            ]))->assertForbidden();
            $this->actingAs($jefe)->patch(route('usuarios.estado', $id))->assertForbidden();
            $this->actingAs($jefe)->delete(route('usuarios.sesiones', $id))->assertForbidden();
            $this->actingAs($jefe)->delete(route('usuarios.destroy', $id))->assertForbidden();
            $this->actingAs($jefe)->put(route('usuarios.permisos', $id), ['permisos' => []])->assertForbidden();
        }

        $this->assertTrue($admin->fresh()->activo);
        $this->assertNull($contador->fresh()->deleted_at);
        $this->actingAs($jefe)->get(route('usuarios.index'))->assertOk()
            ->assertDontSee(route('usuarios.edit', $admin->id_usuario))->assertDontSee(route('usuarios.edit', $contador->id_usuario));
    }

    public function test_quien_tiene_acceso_total_gestiona_a_todos(): void
    {
        $root = $this->crearUsuario(['username' => 'root', 'email' => 'root@nexus.test'], 'Superadmin');
        $otroAdmin = $this->crearUsuario(['username' => 'admin2', 'email' => 'admin2@nexus.test'], 'Administrador');

        $this->actingAs($root)->get(route('usuarios.edit', $otroAdmin->id_usuario))->assertOk();
        $this->actingAs($root)->patch(route('usuarios.estado', $otroAdmin->id_usuario))->assertSessionHasNoErrors();
        $this->assertFalse($otroAdmin->fresh()->activo);
    }
}
