<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 7c: Empleados, contrato laboral, historial salarial, departamentos y cargos. */
class EmpleadosTest extends TestCase
{
    use EsquemaNexus;

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'primer_nombre' => 'Ana', 'primer_apellido' => 'López', 'tipo_doc_id' => 'DPI', 'dpi_nit' => '1234 56789 0101',
            'fecha_ingreso' => '2026-01-15', 'tipo_contrato' => 'INDEFINIDO', 'modalidad_trabajo' => 'PRESENCIAL',
        ], $cambios);
    }

    private function empleado(): object
    {
        return DB::table('empleado')->orderByDesc('id_empleado')->first();
    }

    private function contrato(int $empleado, array $cambios = []): array
    {
        return array_merge(['tipo' => 'INDEFINIDO', 'fecha_inicio' => '2026-01-15', 'salario_base' => 4000, 'moneda' => 'GTQ', 'jornada' => 'COMPLETA', 'horas_semana' => 44], $cambios);
    }

    public function test_crea_empleado_con_codigo_y_dpi_normalizado(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->post(route('empleados.store'), $this->datos())->assertSessionHasNoErrors();
        $e = $this->empleado();
        $this->assertSame(['EMP-0001', '1234567890101', 'ACTIVO'], [$e->codigo_empleado, $e->dpi_nit, $e->estado]);

        $this->actingAs($admin)->post(route('empleados.store'), $this->datos(['primer_nombre' => 'Otra']))->assertSessionHasErrors('dpi_nit'); // DPI repetido
        $this->actingAs($admin)->post(route('empleados.store'), $this->datos(['dpi_nit' => '123']))->assertSessionHasErrors('dpi_nit'); // DPI de 13 dígitos
        $this->actingAs($admin)->post(route('empleados.store'), $this->datos(['tipo_doc_id' => 'PASAPORTE', 'dpi_nit' => 'X123', 'fecha_nacimiento' => now()->subYears(10)->format('Y-m-d')]))
            ->assertSessionHasErrors('fecha_nacimiento');
        $this->actingAs($admin)->post(route('empleados.store'), $this->datos(['tipo_doc_id' => 'PASAPORTE', 'dpi_nit' => 'X123']))->assertSessionHasNoErrors();
        $this->assertSame('EMP-0002', $this->empleado()->codigo_empleado);
    }

    public function test_jefe_inmediato_sin_ciclos_y_de_mi_empresa(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('empleados.store'), $this->datos());
        $jefe = $this->empleado()->id_empleado;
        $this->actingAs($admin)->post(route('empleados.store'), $this->datos(['dpi_nit' => '9999999999999', 'id_supervisor' => $jefe]))->assertSessionHasNoErrors();
        $subordinado = $this->empleado()->id_empleado;

        // El jefe no puede reportar a su propio subordinado.
        $this->actingAs($admin)->put(route('empleados.update', $jefe), $this->datos(['id_supervisor' => $subordinado, 'estado' => 'ACTIVO']))->assertSessionHasErrors('id_supervisor');
        $ajeno = DB::table('empleado')->insertGetId(['id_empresa' => 2, 'primer_nombre' => 'X', 'estado' => 'ACTIVO']);
        $this->actingAs($admin)->put(route('empleados.update', $jefe), $this->datos(['id_supervisor' => $ajeno, 'estado' => 'ACTIVO']))->assertSessionHasErrors('id_supervisor');
        // BAJA no se elige al editar: tiene su propia acción.
        $this->actingAs($admin)->put(route('empleados.update', $jefe), $this->datos(['estado' => 'BAJA']))->assertSessionHasErrors('estado');
    }

    public function test_contrato_y_cambios_de_salario_quedan_en_el_historial(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('empleados.store'), $this->datos());
        $id = $this->empleado()->id_empleado;

        $this->actingAs($admin)->post(route('empleados.contrato', $id), $this->contrato($id, ['fecha_inicio' => '2025-12-01']))->assertSessionHasErrorsIn('contrato', 'fecha_inicio');
        $this->actingAs($admin)->post(route('empleados.contrato', $id), $this->contrato($id, ['tipo' => 'PRUEBA']))->assertSessionHasErrorsIn('contrato', 'fecha_fin');
        $this->actingAs($admin)->post(route('empleados.contrato', $id), $this->contrato($id))->assertSessionHasNoErrors();
        $contrato = DB::table('contrato_laboral')->sole();
        $this->assertSame(['CL-'.now()->year.'-0001', 'VIGENTE', 4000.0], [$contrato->numero_contrato, $contrato->estado, (float) $contrato->salario_base]);

        $this->actingAs($admin)->post(route('empleados.salario', $id), ['tipo_cambio' => 'AUMENTO', 'salario_nuevo' => 4500, 'fecha_efectiva' => '2026-07-01', 'motivo' => 'Evaluación anual'])
            ->assertSessionHasNoErrors();
        $this->assertSame(4500.0, (float) DB::table('contrato_laboral')->value('salario_base'));
        $this->assertSame([['CONTRATACION', null, 4000.0], ['AUMENTO', 4000.0, 4500.0]],
            DB::table('historial_salarial')->orderBy('id_historial')->get()->map(fn ($h) => [$h->tipo_cambio, $h->salario_anterior === null ? null : (float) $h->salario_anterior, (float) $h->salario_nuevo])->all());

        // Un contrato nuevo deja el anterior como renovado.
        $this->actingAs($admin)->post(route('empleados.contrato', $id), $this->contrato($id, ['fecha_inicio' => '2026-08-01', 'salario_base' => 5000]))->assertSessionHasNoErrors();
        $this->assertSame(['RENOVADO', 'VIGENTE'], DB::table('contrato_laboral')->orderBy('id_contrato')->pluck('estado')->all());
        $this->actingAs($admin)->get(route('empleados.show', $id))->assertOk()->assertSee('GTQ 5,000.00')->assertSee('Evaluación anual');
    }

    public function test_baja_cierra_el_contrato_y_libera_al_equipo(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('empleados.store'), $this->datos());
        $jefe = $this->empleado()->id_empleado;
        $this->actingAs($admin)->post(route('empleados.contrato', $jefe), $this->contrato($jefe));
        $this->actingAs($admin)->post(route('empleados.store'), $this->datos(['dpi_nit' => '9999999999999', 'id_supervisor' => $jefe]));

        $this->actingAs($admin)->patch(route('empleados.baja', $jefe), ['fecha_baja' => '2025-01-01', 'motivo_baja' => 'Renuncia'])->assertSessionHasErrors('fecha_baja');
        $this->actingAs($admin)->patch(route('empleados.baja', $jefe), ['fecha_baja' => '2026-09-30', 'motivo_baja' => 'Renuncia voluntaria'])->assertSessionHasNoErrors();

        $this->assertSame('BAJA', DB::table('empleado')->where('id_empleado', $jefe)->value('estado'));
        $this->assertSame('RESCINDIDO', DB::table('contrato_laboral')->value('estado'));
        $this->assertNull(DB::table('empleado')->where('id_empleado', '!=', $jefe)->value('id_supervisor'));
        $this->actingAs($admin)->get(route('empleados.index'))->assertDontSee('EMP-0001'); // por defecto no lista bajas
        $this->actingAs($admin)->get(route('empleados.index', ['estado' => 'BAJA']))->assertSee('EMP-0001');

        $this->actingAs($admin)->patch(route('empleados.reactivar', $jefe))->assertSessionHasNoErrors();
        $this->assertSame('ACTIVO', DB::table('empleado')->where('id_empleado', $jefe)->value('estado'));
    }

    public function test_departamentos_y_cargos(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->post(route('organizacion.departamentos.store'), ['nombre' => 'Operaciones', 'codigo' => 'ops', 'activo' => '1'])->assertSessionHasNoErrors();
        $ops = DB::table('departamento_org')->value('id_depto_org');
        $this->actingAs($admin)->post(route('organizacion.departamentos.store'), ['nombre' => 'Limpieza', 'id_padre' => $ops, 'activo' => '1']);
        $limpieza = DB::table('departamento_org')->where('nombre', 'Limpieza')->value('id_depto_org');
        // Ciclo: Operaciones no puede depender de Limpieza.
        $this->actingAs($admin)->put(route('organizacion.departamentos.update', $ops), ['nombre' => 'Operaciones', 'id_padre' => $limpieza, 'activo' => '1'])
            ->assertSessionHasErrorsIn('departamento', 'id_padre');
        // Desactivar con la casilla desmarcada (el campo oculto manda 0).
        $this->actingAs($admin)->put(route('organizacion.departamentos.update', $limpieza), ['nombre' => 'Limpieza', 'id_padre' => $ops, 'activo' => '0']);
        $this->assertSame(0, (int) DB::table('departamento_org')->where('id_depto_org', $limpieza)->value('activo'));

        $this->actingAs($admin)->post(route('organizacion.cargos.store'), ['nombre' => 'Conserje', 'nivel_jerarquico' => 1, 'moneda' => 'GTQ', 'salario_min' => 4000, 'salario_max' => 3000])
            ->assertSessionHasErrorsIn('cargo', 'salario_max');
        $this->actingAs($admin)->post(route('organizacion.cargos.store'), ['nombre' => 'Conserje', 'nivel_jerarquico' => 1, 'moneda' => 'GTQ', 'id_depto_org' => $limpieza, 'activo' => '1'])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->delete(route('organizacion.departamentos.destroy', $limpieza))->assertSessionHasErrors('departamento'); // tiene cargos
        $this->actingAs($admin)->get(route('organizacion.index'))->assertOk()->assertSee('Conserje')->assertSee('Operaciones');
    }

    public function test_permisos_y_otra_empresa(): void
    {
        $rrhh = $this->crearUsuario(['username' => 'r', 'email' => 'r@nexus.test'], 'RRHH');
        $this->darPermisos($rrhh, ['empleados.ver']);
        $ajeno = DB::table('empleado')->insertGetId(['id_empresa' => 2, 'primer_nombre' => 'Ajeno', 'primer_apellido' => 'X', 'estado' => 'ACTIVO', 'fecha_ingreso' => '2026-01-01']);

        $this->actingAs($rrhh)->get(route('empleados.index'))->assertOk()->assertDontSee('Nuevo empleado')->assertDontSee('Departamentos y cargos');
        $this->actingAs($rrhh)->get(route('empleados.show', $ajeno))->assertNotFound();
        $this->actingAs($rrhh)->get(route('organizacion.index'))->assertForbidden();
        $this->actingAs($rrhh)->post(route('empleados.store'), $this->datos())->assertForbidden();
    }
}
