<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 5a: Centros de costo y Cuentas contables en Blade (con importador). */
class CentrosCuentasTest extends TestCase
{
    use EsquemaNexus;

    private function cuenta(string $codigo, ?int $padre = null, array $datos = []): int
    {
        return DB::table('cuenta_contable')->insertGetId($datos + [
            'id_empresa' => 1, 'id_padre' => $padre, 'codigo' => $codigo, 'nombre' => 'Cuenta '.$codigo,
            'tipo' => 'GASTO', 'naturaleza' => 'DEUDORA', 'nivel' => 1, 'permite_movimiento' => true,
        ]);
    }

    private function datosCuenta(array $cambios = []): array
    {
        return array_merge(['codigo' => '5.01', 'nombre' => 'Gastos de personal', 'tipo' => 'GASTO', 'naturaleza' => 'DEUDORA', 'permite_movimiento' => '1', 'activo' => '1'], $cambios);
    }

    // ── Centros de costo ─────────────────────────────────────────────

    public function test_crud_de_centros_de_costo(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->post(route('centros-costo.store'), ['codigo' => 'adm-01', 'nombre' => 'Administración', 'activo' => '1'])
            ->assertRedirect(route('centros-costo.index'));
        $centro = DB::table('centro_costo')->first();
        $this->assertSame('ADM-01', $centro->codigo);

        $this->actingAs($admin)->post(route('centros-costo.store'), ['codigo' => 'ADM-01', 'nombre' => 'Otro'])->assertSessionHasErrors('codigo');
        $this->actingAs($admin)->get(route('centros-costo.index'))->assertOk()->assertSee('Administración');
        $this->actingAs($admin)->put(route('centros-costo.update', $centro->id_centro), ['codigo' => 'ADM-01', 'nombre' => 'Admin central'])->assertSessionHasNoErrors();
        $this->assertSame([false, 'Admin central'], [(bool) DB::table('centro_costo')->value('activo'), DB::table('centro_costo')->value('nombre')]);
        $this->assertStringContainsString('ADM-01', $this->actingAs($admin)->get(route('centros-costo.exportar'))->streamedContent());
    }

    public function test_no_elimina_un_centro_en_uso(): void
    {
        $admin = $this->crearUsuario();
        $centro = DB::table('centro_costo')->insertGetId(['id_empresa' => 1, 'codigo' => 'C1', 'nombre' => 'Uno']);
        DB::table('producto')->insert(['id_empresa' => 1, 'nombre' => 'Cemento', 'id_centro_default' => $centro]);

        $this->actingAs($admin)->delete(route('centros-costo.destroy', $centro))->assertSessionHasErrors('centro');
        $this->assertSame(1, DB::table('centro_costo')->count());

        DB::table('producto')->delete();
        $this->actingAs($admin)->delete(route('centros-costo.destroy', $centro))->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('centro_costo')->count());
    }

    public function test_centros_exigen_permiso_y_no_tocan_otra_empresa(): void
    {
        $lector = $this->crearUsuario(['username' => 'l', 'email' => 'l@nexus.test'], 'Contador');
        $this->darPermisos($lector, ['centros_costo.ver']);
        $ajeno = DB::table('centro_costo')->insertGetId(['id_empresa' => 2, 'codigo' => 'X', 'nombre' => 'Ajeno']);

        $this->actingAs($lector)->get(route('centros-costo.index'))->assertOk()->assertDontSee('Ajeno')->assertDontSee('Nuevo centro');
        $this->actingAs($lector)->post(route('centros-costo.store'), ['codigo' => 'A', 'nombre' => 'A'])->assertForbidden();

        $admin = $this->crearUsuario();
        $this->actingAs($admin)->get(route('centros-costo.edit', $ajeno))->assertNotFound();
    }

    // ── Cuentas contables ────────────────────────────────────────────

    public function test_crear_subcuenta_calcula_nivel_y_exige_padre_de_agrupacion_del_mismo_tipo(): void
    {
        $admin = $this->crearUsuario();
        $raiz = $this->cuenta('5', null, ['permite_movimiento' => false]);

        $this->actingAs($admin)->post(route('cuentas-contables.store'), $this->datosCuenta(['id_padre' => $raiz]))->assertSessionHasNoErrors();
        $hija = DB::table('cuenta_contable')->where('codigo', '5.01')->first();
        $this->assertSame([$raiz, 2], [(int) $hija->id_padre, (int) $hija->nivel]);

        // Padre de otro tipo.
        $this->actingAs($admin)->post(route('cuentas-contables.store'), $this->datosCuenta(['codigo' => '5.02', 'tipo' => 'INGRESO', 'id_padre' => $raiz]))->assertSessionHasErrors('id_padre');
        // Padre que permite movimientos.
        $this->actingAs($admin)->post(route('cuentas-contables.store'), $this->datosCuenta(['codigo' => '5.01.1', 'id_padre' => $hija->id_cuenta]))->assertSessionHasErrors('id_padre');
        // Padre de otra empresa.
        $ajena = DB::table('cuenta_contable')->insertGetId(['id_empresa' => 2, 'codigo' => '9', 'nombre' => 'Ajena', 'tipo' => 'GASTO', 'naturaleza' => 'DEUDORA', 'permite_movimiento' => false]);
        $this->actingAs($admin)->post(route('cuentas-contables.store'), $this->datosCuenta(['codigo' => '5.03', 'id_padre' => $ajena]))->assertSessionHasErrors('id_padre');

        $this->assertSame(2, DB::table('cuenta_contable')->where('id_empresa', 1)->count());
    }

    public function test_mover_una_rama_recalcula_niveles_y_evita_ciclos(): void
    {
        $admin = $this->crearUsuario();
        $a = $this->cuenta('5', null, ['permite_movimiento' => false]);
        $b = $this->cuenta('5.01', $a, ['permite_movimiento' => false, 'nivel' => 2]);
        $c = $this->cuenta('5.01.001', $b, ['nivel' => 3]);
        $otra = $this->cuenta('6', null, ['permite_movimiento' => false]);

        // «5» debajo de su nieta: ciclo (además la nieta permite movimientos).
        $this->actingAs($admin)->put(route('cuentas-contables.update', $a), $this->datosCuenta(['codigo' => '5', 'permite_movimiento' => '0', 'id_padre' => $b]))
            ->assertSessionHasErrors('id_padre');

        // Mover «5.01» (con su hija) debajo de «6».
        $this->actingAs($admin)->put(route('cuentas-contables.update', $b), $this->datosCuenta(['codigo' => '5.01', 'permite_movimiento' => '0', 'id_padre' => $otra]))
            ->assertSessionHasNoErrors();
        $this->assertSame([1, 2, 3], [(int) DB::table('cuenta_contable')->where('id_cuenta', $otra)->value('nivel'),
            (int) DB::table('cuenta_contable')->where('id_cuenta', $b)->value('nivel'), (int) DB::table('cuenta_contable')->where('id_cuenta', $c)->value('nivel')]);

        // Con subcuentas no puede permitir movimientos ni cambiar a un tipo distinto del de sus hijas.
        $this->actingAs($admin)->put(route('cuentas-contables.update', $b), $this->datosCuenta(['codigo' => '5.01', 'id_padre' => $otra]))->assertSessionHasErrors('permite_movimiento');
        $this->actingAs($admin)->put(route('cuentas-contables.update', $a), $this->datosCuenta(['codigo' => '5', 'tipo' => 'COSTO', 'permite_movimiento' => '0']))->assertSessionHasNoErrors();
    }

    public function test_no_elimina_cuentas_con_subcuentas_o_en_uso(): void
    {
        $admin = $this->crearUsuario();
        $padre = $this->cuenta('5', null, ['permite_movimiento' => false]);
        $hija = $this->cuenta('5.01', $padre);
        DB::table('producto')->insert(['id_empresa' => 1, 'nombre' => 'Cemento', 'id_cuenta_gasto' => $hija]);

        $this->actingAs($admin)->delete(route('cuentas-contables.destroy', $padre))->assertSessionHasErrors('cuenta');
        $this->actingAs($admin)->delete(route('cuentas-contables.destroy', $hija))->assertSessionHasErrors('cuenta');
        $this->assertSame(2, DB::table('cuenta_contable')->count());
    }

    public function test_arbol_y_exportar(): void
    {
        $admin = $this->crearUsuario();
        $padre = $this->cuenta('5', null, ['permite_movimiento' => false, 'nombre' => 'GASTOS']);
        $this->cuenta('5.01', $padre, ['nivel' => 2, 'nombre' => 'Sueldos']);

        $this->actingAs($admin)->get(route('cuentas-contables.index'))->assertOk()->assertSeeInOrder(['GASTOS', 'Sueldos']);
        $csv = $this->actingAs($admin)->get(route('cuentas-contables.exportar'))->streamedContent();
        $this->assertStringContainsString('5.01;Sueldos;GASTO;DEUDORA;5;Sí', $csv);
    }

    public function test_importar_csv_con_vista_previa_y_confirmacion(): void
    {
        $admin = $this->crearUsuario();
        $this->cuenta('5', null, ['permite_movimiento' => false, 'nombre' => 'Viejo nombre']);
        $csv = "\xEF\xBB\xBFCódigo;Nombre;Tipo;Naturaleza;Código padre;Permite movimiento\n"
            ."5;GASTOS;GASTO;DEUDORA;;No\n"
            ."5.01;Gastos de personal;gasto;;5;\n" // naturaleza por tipo; «movimiento» vacío con hijas = No
            ."5.01.001;Sueldos;GASTO;DEUDORA;5.01;Sí\n\n";

        $this->actingAs($admin)->post(route('cuentas-contables.importar.previa'), ['archivo' => UploadedFile::fake()->createWithContent('plan.csv', $csv)])
            ->assertRedirect(route('cuentas-contables.importar'));
        $this->actingAs($admin)->get(route('cuentas-contables.importar'))->assertOk()->assertSee('Importar 3 cuentas');
        $this->assertSame(1, DB::table('cuenta_contable')->count()); // nada guardado todavía

        $this->actingAs($admin)->post(route('cuentas-contables.importar.confirmar'))->assertRedirect(route('cuentas-contables.index'));
        $cuentas = DB::table('cuenta_contable')->orderBy('codigo')->get()->keyBy('codigo');
        $this->assertSame('GASTOS', $cuentas['5']->nombre); // actualizada
        $this->assertSame([2, 'DEUDORA', 0], [(int) $cuentas['5.01']->nivel, $cuentas['5.01']->naturaleza, (int) $cuentas['5.01']->permite_movimiento]);
        $this->assertSame([3, (int) $cuentas['5.01']->id_cuenta, 1], [(int) $cuentas['5.01.001']->nivel, (int) $cuentas['5.01.001']->id_padre, (int) $cuentas['5.01.001']->permite_movimiento]);
    }

    public function test_importar_excel(): void
    {
        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'plan_'.uniqid().'.xlsx';
        (new FastExcel(collect([
            ['Código' => '1', 'Nombre' => 'ACTIVO', 'Tipo' => 'ACTIVO', 'Naturaleza' => '', 'Código padre' => '', 'Permite movimiento' => 'No'],
            ['Código' => '1.01', 'Nombre' => 'Caja', 'Tipo' => 'ACTIVO', 'Naturaleza' => '', 'Código padre' => '1', 'Permite movimiento' => 'Sí'],
        ])))->export($ruta);

        $this->actingAs($this->crearUsuario())->post(route('cuentas-contables.importar.previa'), ['archivo' => new UploadedFile($ruta, 'plan.xlsx', null, null, true)]);
        $this->post(route('cuentas-contables.importar.confirmar'))->assertSessionHasNoErrors();
        @unlink($ruta);

        $caja = DB::table('cuenta_contable')->where('codigo', '1.01')->first();
        $this->assertSame(['DEUDORA', 2], [$caja->naturaleza, (int) $caja->nivel]);
    }

    public function test_importar_con_errores_no_guarda_nada(): void
    {
        $admin = $this->crearUsuario();
        $csv = "Codigo,Nombre,Tipo,Naturaleza,Codigo padre,Permite movimiento\n"
            ."5,GASTOS,GASTO,DEUDORA,,Sí\n"          // tiene hija pero permite movimiento
            ."5.01,Personal,GASTO,DEUDORA,5,Sí\n"
            ."4,INGRESOS,VENTAS,ACREEDORA,,No\n"     // tipo no válido
            ."7.01,Huérfana,GASTO,DEUDORA,7,Sí\n"    // padre inexistente
            ."5.01,Repetida,GASTO,DEUDORA,5,Sí\n";

        $this->actingAs($admin)->post(route('cuentas-contables.importar.previa'), ['archivo' => UploadedFile::fake()->createWithContent('plan.csv', $csv)]);
        $analisis = session('importacion_cuentas');
        $this->assertSame([3, 4, 5, 6], array_keys($analisis['errores']));

        $this->actingAs($admin)->post(route('cuentas-contables.importar.confirmar'))->assertSessionHasErrors('archivo');
        $this->assertSame(0, DB::table('cuenta_contable')->count());
    }

    public function test_cuentas_exigen_permisos(): void
    {
        $lector = $this->crearUsuario(['username' => 'l', 'email' => 'l@nexus.test'], 'Contador');
        $this->darPermisos($lector, ['cuentas_contables.ver', 'cuentas_contables.crear']);

        $this->actingAs($lector)->get(route('cuentas-contables.index'))->assertOk()->assertDontSee('Importar');
        $this->actingAs($lector)->get(route('cuentas-contables.importar'))->assertForbidden();
        $this->actingAs($lector)->get(route('cuentas-contables.exportar'))->assertForbidden();
    }

    public function test_las_apis_ya_no_existen(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->getJson('/api/v1/core/centros-costo')->assertNotFound();
        $this->actingAs($admin)->getJson('/api/v1/core/cuentas-contables')->assertNotFound();
    }
}
