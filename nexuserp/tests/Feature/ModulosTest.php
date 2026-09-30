<?php

namespace Tests\Feature;

use App\Support\MenuLateral;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 4: Gestión de módulos (menú lateral + acciones de permisos), solo Administrador. */
class ModulosTest extends TestCase
{
    use EsquemaNexus;

    private array $acciones;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        foreach (['ver', 'crear', 'editar', 'eliminar'] as $i => $codigo) {
            $this->acciones[$codigo] = DB::table('accion')->insertGetId(['codigo' => $codigo, 'nombre' => ucfirst($codigo), 'orden' => $i + 1]);
        }
    }

    private function modulo(string $codigo, array $datos = []): int
    {
        return DB::table('modulo')->insertGetId($datos + ['codigo' => $codigo, 'nombre' => ucfirst($codigo), 'grupo' => 'Inventario', 'orden' => 301, 'activo' => true]);
    }

    private function fila(int $id): ?object
    {
        return DB::table('modulo')->where('id_modulo', $id)->first();
    }

    private function accionesDe(int $idModulo): array
    {
        return DB::table('permiso')->join('accion', 'accion.id_accion', '=', 'permiso.id_accion')
            ->where('id_modulo', $idModulo)->orderBy('accion.orden')->pluck('accion.codigo')->all();
    }

    public function test_solo_el_administrador_entra(): void
    {
        $this->modulo('bodegas', ['nombre' => 'Bodegas']);
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');

        $this->actingAs($vendedor)->get(route('modulos.index'))->assertForbidden();
        $this->actingAs($this->crearUsuario())->get(route('modulos.index'))->assertOk()->assertSee('Bodegas')->assertSee('Solo Administrador');
        $this->actingAs($this->crearUsuario(['username' => 'a2', 'email' => 'a2@nexus.test']))->get('/sistema/menu')->assertRedirect('/sistema/modulos');
    }

    public function test_crea_un_modulo_con_sus_acciones_como_permisos(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->post(route('modulos.store'), [
            'codigo' => ' Bodegas ', 'nombre' => 'Bodegas', 'grupo' => 'Inventario', 'icono' => ' Archive ', 'ruta' => 'sistema/bodegas',
            'activo' => '1', 'acciones' => [$this->acciones['ver'], $this->acciones['crear']],
        ])->assertRedirect(route('modulos.index'))->assertSessionHasNoErrors();

        $modulo = DB::table('modulo')->where('codigo', 'bodegas')->first();
        $this->assertSame(['archive', '/sistema/bodegas', 'Inventario'], [$modulo->icono, $modulo->ruta, $modulo->grupo]);
        $this->assertSame(['ver', 'crear'], $this->accionesDe($modulo->id_modulo));
        $this->assertTrue($admin->fresh()->puede('bodegas.crear'));
    }

    public function test_valida_codigo_ruta_icono_y_dos_niveles(): void
    {
        $admin = $this->crearUsuario();
        $padre = $this->modulo('reportes');
        $hijo = $this->modulo('reportes.kardex', ['id_modulo_padre' => $padre]);

        $this->actingAs($admin)->post(route('modulos.store'), ['codigo' => 'Con Espacio', 'nombre' => 'X', 'ruta' => 'javascript:alert(1)', 'icono' => '"><x'])
            ->assertSessionHasErrors(['codigo', 'ruta', 'icono']);
        $this->actingAs($admin)->post(route('modulos.store'), ['codigo' => 'reportes', 'nombre' => 'Repetido'])->assertSessionHasErrors('codigo');
        // Un submódulo no puede ser padre.
        $this->actingAs($admin)->post(route('modulos.store'), ['codigo' => 'nieto', 'nombre' => 'Nieto', 'id_modulo_padre' => $hijo])->assertSessionHasErrors('id_modulo_padre');
    }

    public function test_el_codigo_no_cambia_y_quitar_una_accion_borra_sus_asignaciones(): void
    {
        $admin = $this->crearUsuario();
        $idModulo = $this->modulo('bodegas');
        $ver = DB::table('permiso')->insertGetId(['id_modulo' => $idModulo, 'id_accion' => $this->acciones['ver']]);
        $crear = DB::table('permiso')->insertGetId(['id_modulo' => $idModulo, 'id_accion' => $this->acciones['crear']]);
        DB::table('rol_permiso')->insert(['id_rol' => 1, 'id_permiso' => $crear]);
        DB::table('usuario_permiso')->insert(['id_usuario' => $admin->id_usuario, 'id_permiso' => $crear]);

        $this->actingAs($admin)->put(route('modulos.update', $idModulo), [
            'codigo' => 'otro', 'nombre' => 'Almacenes', 'grupo' => 'Inventario', 'activo' => '1', 'acciones' => [$this->acciones['ver'], $this->acciones['editar']],
        ])->assertSessionHasNoErrors();

        $this->assertSame('bodegas', $this->fila($idModulo)->codigo);
        $this->assertSame('Almacenes', $this->fila($idModulo)->nombre);
        $this->assertSame(['ver', 'editar'], $this->accionesDe($idModulo));
        $this->assertSame(1, DB::table('permiso')->where('id_permiso', $ver)->count()); // «ver» se conserva
        $this->assertSame(0, DB::table('rol_permiso')->where('id_permiso', $crear)->count());
        $this->assertSame(0, DB::table('usuario_permiso')->where('id_permiso', $crear)->count());
    }

    public function test_mover_intercambia_con_el_vecino_del_mismo_grupo(): void
    {
        $admin = $this->crearUsuario();
        $a = $this->modulo('a', ['orden' => 305]);
        $b = $this->modulo('b', ['orden' => 305]); // orden repetido: se desempata por id
        $c = $this->modulo('c', ['orden' => 309]);
        $otroGrupo = $this->modulo('d', ['grupo' => 'Finanzas', 'orden' => 501]);

        $this->actingAs($admin)->patch(route('modulos.mover', $c), ['direccion' => 'subir']);
        $this->assertSame([301, 303, 302, 501], array_map(fn ($id) => (int) $this->fila($id)->orden, [$a, $b, $c, $otroGrupo]));
    }

    public function test_no_elimina_un_modulo_con_submodulos_y_si_uno_sin_ellos(): void
    {
        $admin = $this->crearUsuario();
        $padre = $this->modulo('reportes');
        $hijo = $this->modulo('reportes.kardex', ['id_modulo_padre' => $padre]);
        $permiso = DB::table('permiso')->insertGetId(['id_modulo' => $hijo, 'id_accion' => $this->acciones['ver']]);
        DB::table('rol_permiso')->insert(['id_rol' => 1, 'id_permiso' => $permiso]);

        $this->actingAs($admin)->delete(route('modulos.destroy', $padre))->assertSessionHasErrors('modulo');
        $this->actingAs($admin)->delete(route('modulos.destroy', $hijo))->assertRedirect(route('modulos.index'));

        $this->assertNull($this->fila($hijo));
        $this->assertSame(0, DB::table('permiso')->where('id_modulo', $hijo)->count());
        $this->assertSame(0, DB::table('rol_permiso')->count());
    }

    public function test_desactivar_un_modulo_lo_quita_del_menu_y_de_los_permisos(): void
    {
        $admin = $this->crearUsuario();
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');
        $this->darPermisos($vendedor, ['clientes.ver']);
        DB::table('modulo')->where('codigo', 'clientes')->update(['ruta' => '/sistema/clientes', 'nombre' => 'Clientes', 'grupo' => 'CRM']);

        $rutas = fn ($u) => MenuLateral::para($u)->flatMap(fn ($g) => $g['items'])->pluck('ruta')->all();
        $this->assertContains('/sistema/clientes', $rutas($vendedor));
        $this->actingAs($admin)->patch(route('modulos.estado', DB::table('modulo')->where('codigo', 'clientes')->value('id_modulo')));

        $this->assertNotContains('/sistema/clientes', $rutas($vendedor->fresh()));
        $this->assertFalse($vendedor->fresh()->puede('clientes.ver'));
    }
}
