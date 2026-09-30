<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 3c: Roles y permisos en Blade (CONFIG.ROLES.VER / GESTIONAR) sin escalada de privilegios. */
class RolesTest extends TestCase
{
    use EsquemaNexus;

    private int $modulo;

    private function permiso(string $codigo): int
    {
        $this->modulo ??= DB::table('modulo_sistema')->insertGetId(['nombre' => 'Configuración', 'codigo' => 'CONFIG']);

        return DB::table('permiso')->where('codigo', $codigo)->value('id_permiso')
            ?? DB::table('permiso')->insertGetId(['id_modulo' => $this->modulo, 'codigo' => $codigo, 'descripcion' => $codigo]);
    }

    private function idRol(Usuario $usuario): int
    {
        return (int) DB::table('usuario_rol')->where('id_usuario', $usuario->id_usuario)->value('id_rol');
    }

    private function permisosDe(int $idRol): array
    {
        return DB::table('rol_permiso')->where('id_rol', $idRol)->orderBy('id_permiso')->pluck('id_permiso')->map(fn ($id) => (int) $id)->all();
    }

    /** Usuario con CONFIG.ROLES.GESTIONAR (y lo que se indique) que no es Administrador. */
    private function gestor(array $extra = []): Usuario
    {
        $gestor = $this->crearUsuario(['username' => 'gestor', 'email' => 'gestor@nexus.test'], 'Jefe TI');
        foreach (['CONFIG.ROLES.VER', 'CONFIG.ROLES.GESTIONAR', ...$extra] as $codigo) {
            DB::table('rol_permiso')->insert(['id_rol' => $this->idRol($gestor), 'id_permiso' => $this->permiso($codigo)]);
        }

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

    public function test_crea_un_rol_con_permisos_y_opciones_del_menu(): void
    {
        $admin = $this->crearUsuario();
        $ver = $this->permiso('INV.PRODUCTOS.VER');
        $grupo = DB::table('menu')->insertGetId(['id_empresa' => 1, 'nombre' => 'Inventario']);
        $item = DB::table('menu')->insertGetId(['id_empresa' => 1, 'id_padre' => $grupo, 'nombre' => 'Productos', 'ruta' => '/sistema/productos']);

        $this->actingAs($admin)->get(route('roles.create'))->assertOk()->assertSee('INV.PRODUCTOS.VER')->assertSee('Productos');

        $this->actingAs($admin)->post(route('roles.store'), [
            'nombre' => ' Bodeguero ', 'descripcion' => 'Maneja la bodega', 'activo' => '1', 'requiere_2fa' => '1',
            'permisos' => [$ver], 'menu' => [$item],
        ])->assertRedirect(route('roles.index'))->assertSessionHasNoErrors();

        $rol = DB::table('rol')->where('nombre', 'Bodeguero')->first();
        $this->assertSame(1, (int) $rol->id_empresa);
        $this->assertTrue((bool) $rol->requiere_2fa);
        $this->assertSame([$ver], $this->permisosDe($rol->id_rol));
        $this->assertSame([$item], DB::table('menu_rol')->where('id_rol', $rol->id_rol)->pluck('id_menu')->map(fn ($i) => (int) $i)->all());
    }

    public function test_el_nombre_administrador_esta_reservado(): void
    {
        $admin = $this->crearUsuario();
        DB::table('rol')->where('nombre', 'Administrador')->update(['id_empresa' => 3]); // que no choque por «único»

        $this->actingAs($admin->fresh())->post(route('roles.store'), ['nombre' => 'Administrador'])->assertSessionHasErrors('nombre');
    }

    public function test_el_rol_administrador_no_se_renombra_limita_ni_elimina(): void
    {
        $admin = $this->crearUsuario();
        $idAdmin = $this->idRol($admin);

        $this->actingAs($admin)->put(route('roles.update', $idAdmin), [
            'nombre' => 'Jefe', 'descripcion' => 'Todo el sistema', 'activo' => '0', 'permisos' => [$this->permiso('X.Y')],
        ])->assertSessionHasNoErrors();

        $rol = DB::table('rol')->where('id_rol', $idAdmin)->first();
        $this->assertSame('Administrador', $rol->nombre);
        $this->assertSame('Todo el sistema', $rol->descripcion);
        $this->assertTrue((bool) $rol->activo);
        $this->assertSame([], $this->permisosDe($idAdmin));

        $this->actingAs($admin)->delete(route('roles.destroy', $idAdmin))->assertSessionHasErrors('rol');
        $this->assertNotNull(DB::table('rol')->where('id_rol', $idAdmin)->first());
    }

    public function test_quien_no_es_admin_no_puede_editar_su_propio_rol(): void
    {
        $gestor = $this->gestor();
        $idPropio = $this->idRol($gestor);

        $this->actingAs($gestor)->get(route('roles.edit', $idPropio))->assertOk()->assertSee('No puedes modificar un rol que tú mismo tienes');
        $this->actingAs($gestor)->put(route('roles.update', $idPropio), [
            'nombre' => 'Jefe TI', 'permisos' => [$this->permiso('CONFIG.USUARIOS.EDITAR')],
        ])->assertSessionHasErrors('rol');
        $this->assertNotContains($this->permiso('CONFIG.USUARIOS.EDITAR'), $this->permisosDe($idPropio));
    }

    public function test_quien_no_es_admin_solo_da_permisos_que_tiene_y_conserva_los_ajenos(): void
    {
        $gestor = $this->gestor(['INV.PRODUCTOS.VER']);
        $otro = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Ventas', 'activo' => true]);
        $ajeno = $this->permiso('FINANZAS.FACTURAS.ANULAR');
        DB::table('rol_permiso')->insert(['id_rol' => $otro, 'id_permiso' => $ajeno]);

        // Intentar dar un permiso que no tiene → 403.
        $this->actingAs($gestor)->put(route('roles.update', $otro), [
            'nombre' => 'Ventas', 'activo' => '1', 'permisos' => [$this->permiso('CONFIG.USUARIOS.EDITAR')],
        ])->assertForbidden();

        // Dar uno que sí tiene: el ajeno que ya tenía el rol se conserva.
        $this->actingAs($gestor)->put(route('roles.update', $otro), [
            'nombre' => 'Ventas', 'activo' => '1', 'permisos' => [$this->permiso('INV.PRODUCTOS.VER')],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([$ajeno, $this->permiso('INV.PRODUCTOS.VER')], $this->permisosDe($otro));
    }

    public function test_no_elimina_roles_con_usuarios_y_si_los_vacios(): void
    {
        $admin = $this->crearUsuario();
        $conUsuarios = $this->idRol($this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas'));
        $vacio = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Temporal', 'activo' => true]);
        DB::table('rol_permiso')->insert(['id_rol' => $vacio, 'id_permiso' => $this->permiso('X.Y')]);
        DB::table('menu_rol')->insert(['id_rol' => $vacio, 'id_menu' => 1]);

        $this->actingAs($admin)->delete(route('roles.destroy', $conUsuarios))->assertSessionHasErrors('rol');
        $this->actingAs($admin)->delete(route('roles.destroy', $vacio))->assertRedirect(route('roles.index'));

        $this->assertNull(DB::table('rol')->where('id_rol', $vacio)->first());
        $this->assertSame(0, DB::table('rol_permiso')->where('id_rol', $vacio)->count());
        $this->assertSame(0, DB::table('menu_rol')->where('id_rol', $vacio)->count());
    }

    public function test_los_roles_de_sistema_son_de_solo_lectura(): void
    {
        $admin = $this->crearUsuario();
        $sistema = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Auditor', 'activo' => true, 'es_rol_sistema' => true]);

        $this->actingAs($admin)->put(route('roles.update', $sistema), ['nombre' => 'Otro'])->assertSessionHasErrors('rol');
        $this->assertSame('Auditor', DB::table('rol')->where('id_rol', $sistema)->first()->nombre);
    }

    public function test_desactivar_el_rol_impide_entrar_a_sus_usuarios(): void
    {
        $admin = $this->crearUsuario();
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');

        $this->actingAs($admin)->put(route('roles.update', $this->idRol($vendedor)), ['nombre' => 'Ventas'])->assertSessionHasNoErrors();

        $this->assertFalse($vendedor->fresh()->puedeEntrar());
    }

    public function test_la_api_de_roles_ya_no_existe(): void
    {
        $this->actingAs($this->crearUsuario())->getJson('/api/v1/roles')->assertNotFound();
    }
}
