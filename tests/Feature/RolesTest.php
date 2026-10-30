<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 3c + 4: Roles con matriz módulos × acciones (roles.ver/crear/editar/eliminar) sin escalada. */
class RolesTest extends TestCase
{
    use EsquemaNexus;

    private function idRol(Usuario $usuario): int
    {
        return (int) DB::table('usuario_rol')->where('id_usuario', $usuario->id_usuario)->value('id_rol');
    }

    private function permisosDe(int $idRol): array
    {
        return DB::table('rol_permiso')->where('id_rol', $idRol)->orderBy('id_permiso')->pluck('id_permiso')->map(fn ($id) => (int) $id)->all();
    }

    private function rol(int $id): ?object
    {
        return DB::table('rol')->where('id_rol', $id)->first();
    }

    /** Usuario con roles.ver/crear/editar/eliminar (y lo que se indique) que no es Administrador. */
    private function gestor(array $extra = []): Usuario
    {
        $gestor = $this->crearUsuario(['username' => 'gestor', 'email' => 'gestor@nexus.test'], 'Jefe TI');
        $this->darPermisos($gestor, ['roles.ver', 'roles.crear', 'roles.editar', 'roles.eliminar', ...$extra]);

        return $gestor;
    }

    public function test_listado_con_permiso_de_ver_y_solo_de_mi_empresa(): void
    {
        $admin = $this->crearUsuario();
        DB::table('rol')->insert(['id_empresa' => 2, 'nombre' => 'Rol Ajeno', 'activo' => true]);
        $sinPermiso = $this->crearUsuario(['username' => 'nadie', 'email' => 'nadie@nexus.test'], 'Bodega');

        $this->actingAs($sinPermiso)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('roles.index'))
            ->assertOk()->assertSee('Administrador')->assertSee('Bodega')->assertDontSee('Rol Ajeno');
    }

    public function test_crea_un_rol_con_la_matriz_de_permisos(): void
    {
        $admin = $this->crearUsuario();
        $ver = $this->permiso('bodegas.ver', ['nombre' => 'Bodegas', 'grupo' => 'Inventario']);
        $crear = $this->permiso('bodegas.crear');
        DB::table('modulo')->insert(['codigo' => 'sistema', 'nombre' => 'Solo admin', 'activo' => true]);

        // La matriz muestra el módulo con sus acciones; los módulos sin acciones no aparecen.
        $this->actingAs($admin)->get(route('roles.create'))->assertOk()
            ->assertSee('Bodegas')->assertSee('Inventario')->assertSee('value="'.$crear.'"', false)->assertDontSee('Solo admin');

        $this->actingAs($admin)->post(route('roles.store'), [
            'nombre' => ' Bodeguero ', 'descripcion' => 'Maneja la bodega', 'activo' => '1', 'requiere_2fa' => '1',
            'permisos' => [$ver, $crear],
        ])->assertRedirect(route('roles.index'))->assertSessionHasNoErrors();

        $rol = DB::table('rol')->where('nombre', 'Bodeguero')->first();
        $this->assertSame(1, (int) $rol->id_empresa);
        $this->assertTrue((bool) $rol->requiere_2fa);
        $this->assertSame([$ver, $crear], $this->permisosDe($rol->id_rol));
    }

    public function test_el_nombre_administrador_esta_reservado(): void
    {
        // Quien no tiene acceso total no puede crear un rol que lo daría (ver también AccesoTotalTest).
        $jefe = $this->crearUsuario(['username' => 'jefe', 'email' => 'jefe@nexus.test'], 'Jefe');
        $this->darPermisos($jefe, ['roles.ver', 'roles.crear']);

        $this->actingAs($jefe)->post(route('roles.store'), ['nombre' => ' administrador '])->assertSessionHasErrors('nombre');
        $this->assertSame(1, DB::table('rol')->count());
    }

    public function test_el_rol_administrador_no_se_renombra_limita_ni_elimina(): void
    {
        $admin = $this->crearUsuario();
        $idAdmin = $this->idRol($admin);

        $this->actingAs($admin)->put(route('roles.update', $idAdmin), [
            'nombre' => 'Jefe', 'descripcion' => 'Todo el sistema', 'activo' => '0', 'permisos' => [$this->permiso('x.ver')],
        ])->assertSessionHasNoErrors();

        $rol = $this->rol($idAdmin);
        $this->assertSame('Administrador', $rol->nombre);
        $this->assertSame('Todo el sistema', $rol->descripcion);
        $this->assertTrue((bool) $rol->activo);
        $this->assertSame([], $this->permisosDe($idAdmin));

        $this->actingAs($admin)->delete(route('roles.destroy', $idAdmin))->assertSessionHasErrors('rol');
        $this->assertNotNull($this->rol($idAdmin));
    }

    public function test_cada_accion_de_roles_exige_su_permiso(): void
    {
        $lector = $this->crearUsuario(['username' => 'l', 'email' => 'l@nexus.test'], 'Consulta');
        $this->darPermisos($lector, ['roles.ver']);
        $otro = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Ventas', 'activo' => true]);

        $this->actingAs($lector)->get(route('roles.edit', $otro))->assertOk()->assertDontSee('Guardar rol');
        $this->actingAs($lector)->get(route('roles.create'))->assertForbidden();
        $this->actingAs($lector)->put(route('roles.update', $otro), ['nombre' => 'X'])->assertForbidden();
        $this->actingAs($lector)->delete(route('roles.destroy', $otro))->assertForbidden();
    }

    public function test_quien_no_es_admin_no_puede_editar_su_propio_rol(): void
    {
        $gestor = $this->gestor();
        $idPropio = $this->idRol($gestor);

        $this->actingAs($gestor)->get(route('roles.edit', $idPropio))->assertOk()->assertSee('No puedes modificar un rol que tú mismo tienes');
        $this->actingAs($gestor)->put(route('roles.update', $idPropio), [
            'nombre' => 'Jefe TI', 'permisos' => [$this->permiso('usuarios.editar')],
        ])->assertSessionHasErrors('rol');
        $this->assertNotContains($this->permiso('usuarios.editar'), $this->permisosDe($idPropio));
    }

    public function test_quien_no_es_admin_solo_da_permisos_que_tiene_y_conserva_los_ajenos(): void
    {
        $gestor = $this->gestor(['productos.ver']);
        $otro = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Ventas', 'activo' => true]);
        $ajeno = $this->permiso('facturas.anular');
        DB::table('rol_permiso')->insert(['id_rol' => $otro, 'id_permiso' => $ajeno]);

        // Intentar dar un permiso que no tiene → 403.
        $this->actingAs($gestor)->put(route('roles.update', $otro), [
            'nombre' => 'Ventas', 'activo' => '1', 'permisos' => [$this->permiso('usuarios.editar')],
        ])->assertForbidden();

        // Dar uno que sí tiene: el ajeno que ya tenía el rol se conserva.
        $this->actingAs($gestor)->put(route('roles.update', $otro), [
            'nombre' => 'Ventas', 'activo' => '1', 'permisos' => [$this->permiso('productos.ver')],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([$ajeno, $this->permiso('productos.ver')], $this->permisosDe($otro));
    }

    public function test_los_extras_propios_tambien_cuentan_para_dar_permisos(): void
    {
        $gestor = $this->gestor();
        DB::table('usuario_permiso')->insert(['id_usuario' => $gestor->id_usuario, 'id_permiso' => $this->permiso('pagos.ver')]);
        $otro = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Caja', 'activo' => true]);

        $this->actingAs($gestor->fresh())->put(route('roles.update', $otro), [
            'nombre' => 'Caja', 'activo' => '1', 'permisos' => [$this->permiso('pagos.ver')],
        ])->assertSessionHasNoErrors();
        $this->assertSame([$this->permiso('pagos.ver')], $this->permisosDe($otro));
    }

    public function test_no_elimina_roles_con_usuarios_y_si_los_vacios(): void
    {
        $admin = $this->crearUsuario();
        $conUsuarios = $this->idRol($this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas'));
        $vacio = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Temporal', 'activo' => true]);
        DB::table('rol_permiso')->insert(['id_rol' => $vacio, 'id_permiso' => $this->permiso('x.ver')]);

        $this->actingAs($admin)->delete(route('roles.destroy', $conUsuarios))->assertSessionHasErrors('rol');
        $this->actingAs($admin)->delete(route('roles.destroy', $vacio))->assertRedirect(route('roles.index'));

        $this->assertNull($this->rol($vacio));
        $this->assertSame(0, DB::table('rol_permiso')->where('id_rol', $vacio)->count());
    }

    public function test_los_roles_de_sistema_son_de_solo_lectura(): void
    {
        $admin = $this->crearUsuario();
        $sistema = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Auditor', 'activo' => true, 'es_rol_sistema' => true]);

        $this->actingAs($admin)->put(route('roles.update', $sistema), ['nombre' => 'Otro'])->assertSessionHasErrors('rol');
        $this->assertSame('Auditor', $this->rol($sistema)->nombre);
    }

    public function test_desactivar_el_rol_impide_entrar_a_sus_usuarios(): void
    {
        $admin = $this->crearUsuario();
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');

        $this->actingAs($admin)->put(route('roles.update', $this->idRol($vendedor)), ['nombre' => 'Ventas'])->assertSessionHasNoErrors();

        $this->assertFalse($vendedor->fresh()->puedeEntrar());
    }
}
