<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 3d: Gestión del menú lateral en Blade (solo Administrador). */
class GestionMenuTest extends TestCase
{
    use EsquemaNexus;

    private function grupo(string $nombre, int $orden = 1, array $extra = []): int
    {
        return DB::table('menu')->insertGetId(['id_empresa' => 1, 'nombre' => $nombre, 'orden' => $orden] + $extra);
    }

    private function opcion(int $grupo, string $nombre, int $orden = 1, array $extra = []): int
    {
        return DB::table('menu')->insertGetId(['id_empresa' => 1, 'id_padre' => $grupo, 'nombre' => $nombre, 'icono' => 'package', 'ruta' => '/sistema/x', 'orden' => $orden] + $extra);
    }

    private function fila(int $id): ?object
    {
        return DB::table('menu')->where('id_menu', $id)->first();
    }

    public function test_solo_el_administrador_entra(): void
    {
        $this->grupo('Inventario');
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');

        $this->actingAs($vendedor)->get(route('menu.index'))->assertForbidden();
        $this->actingAs($this->crearUsuario())->get(route('menu.index'))->assertOk()->assertSee('Inventario');
    }

    public function test_crea_grupos_y_opciones_al_final_y_limpia_los_datos(): void
    {
        $admin = $this->crearUsuario();
        $grupo = $this->grupo('Inventario');
        $this->opcion($grupo, 'Productos', 4);

        $this->actingAs($admin)->post(route('menu.store'), ['nombre' => 'Bodegas', 'id_padre' => $grupo, 'icono' => ' Archive ', 'ruta' => 'sistema/bodegas', 'activo' => '1'])
            ->assertRedirect(route('menu.index'))->assertSessionHasNoErrors();
        $opcion = DB::table('menu')->where('nombre', 'Bodegas')->first();
        $this->assertSame(['archive', '/sistema/bodegas', 5, 1], [$opcion->icono, $opcion->ruta, (int) $opcion->orden, (int) $opcion->id_empresa]);

        // Un grupo no guarda ícono ni ruta.
        $this->actingAs($admin)->post(route('menu.store'), ['nombre' => 'Reportes', 'icono' => 'pie-chart', 'ruta' => '/sistema/r', 'activo' => '1']);
        $nuevoGrupo = DB::table('menu')->where('nombre', 'Reportes')->first();
        $this->assertNull($nuevoGrupo->id_padre);
        $this->assertNull($nuevoGrupo->ruta);
        $this->assertNull($nuevoGrupo->icono);
    }

    public function test_solo_acepta_rutas_internas_iconos_validos_y_dos_niveles(): void
    {
        $admin = $this->crearUsuario();
        $grupo = $this->grupo('Inventario');
        $opcion = $this->opcion($grupo, 'Productos');
        $grupoAjeno = DB::table('menu')->insertGetId(['id_empresa' => 2, 'nombre' => 'Ajeno']);

        $this->actingAs($admin)->post(route('menu.store'), ['nombre' => 'Malo', 'id_padre' => $grupo, 'ruta' => 'javascript:alert(1)', 'icono' => '"><script>'])
            ->assertSessionHasErrors(['ruta', 'icono']);
        $this->actingAs($admin)->post(route('menu.store'), ['nombre' => 'Externo', 'id_padre' => $grupo, 'ruta' => '//otro-sitio.com/x'])
            ->assertSessionHasErrors('ruta');
        // Una opción no puede ser padre (solo dos niveles) ni se aceptan grupos de otra empresa.
        $this->actingAs($admin)->post(route('menu.store'), ['nombre' => 'Nieto', 'id_padre' => $opcion])->assertSessionHasErrors('id_padre');
        $this->actingAs($admin)->post(route('menu.store'), ['nombre' => 'Ajena', 'id_padre' => $grupoAjeno])->assertSessionHasErrors('id_padre');
    }

    public function test_un_grupo_con_opciones_no_se_vuelve_opcion_ni_se_elimina(): void
    {
        $admin = $this->crearUsuario();
        $inventario = $this->grupo('Inventario');
        $finanzas = $this->grupo('Finanzas', 2);
        $this->opcion($inventario, 'Productos');

        $this->actingAs($admin)->put(route('menu.update', $inventario), ['nombre' => 'Inventario', 'id_padre' => $finanzas])->assertSessionHasErrors('id_padre');
        $this->actingAs($admin)->delete(route('menu.destroy', $inventario))->assertSessionHasErrors('menu');
        $this->assertNotNull($this->fila($inventario));
    }

    public function test_desactivar_un_grupo_desactiva_sus_opciones(): void
    {
        $admin = $this->crearUsuario();
        $grupo = $this->grupo('Inventario', 1, ['activo' => true]);
        $opcion = $this->opcion($grupo, 'Productos', 1, ['activo' => true]);

        $this->actingAs($admin)->patch(route('menu.estado', $grupo))->assertSessionHas('status');

        $this->assertFalse((bool) $this->fila($grupo)->activo);
        $this->assertFalse((bool) $this->fila($opcion)->activo);
    }

    public function test_mover_intercambia_con_el_vecino_y_renumera(): void
    {
        $admin = $this->crearUsuario();
        $grupo = $this->grupo('Inventario');
        $a = $this->opcion($grupo, 'A', 4);
        $b = $this->opcion($grupo, 'B', 4); // orden repetido: se desempata por id
        $c = $this->opcion($grupo, 'C', 9);

        $this->actingAs($admin)->patch(route('menu.mover', $c), ['direccion' => 'subir']);
        $this->assertSame([1, 3, 2], [(int) $this->fila($a)->orden, (int) $this->fila($b)->orden, (int) $this->fila($c)->orden]);

        // El primero no sube más.
        $this->actingAs($admin)->patch(route('menu.mover', $a), ['direccion' => 'subir']);
        $this->assertSame(1, (int) $this->fila($a)->orden);
    }

    public function test_eliminar_una_opcion_quita_sus_asignaciones_a_roles(): void
    {
        $admin = $this->crearUsuario();
        $opcion = $this->opcion($this->grupo('Inventario'), 'Productos');
        DB::table('menu_rol')->insert(['id_menu' => $opcion, 'id_rol' => 1]);

        $this->actingAs($admin)->delete(route('menu.destroy', $opcion))->assertRedirect(route('menu.index'));

        $this->assertNull($this->fila($opcion));
        $this->assertSame(0, DB::table('menu_rol')->count());
    }

    public function test_no_toca_el_menu_de_otra_empresa(): void
    {
        $ajeno = DB::table('menu')->insertGetId(['id_empresa' => 2, 'nombre' => 'Ajeno', 'activo' => true]);

        $this->actingAs($this->crearUsuario())->patch(route('menu.estado', $ajeno))->assertNotFound();
        $this->assertTrue((bool) $this->fila($ajeno)->activo);
    }

    public function test_las_apis_de_menu_ya_no_existen(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->getJson('/api/v1/menu')->assertNotFound();
        $this->actingAs($admin)->getJson('/api/v1/gestion-menu')->assertNotFound();
    }
}
