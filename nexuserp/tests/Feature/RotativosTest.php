<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Personal rotativo: coberturas pagadas por día y su pago en la nómina. */
class RotativosTest extends TestCase
{
    use EsquemaNexus;

    private int $ana;

    private int $rita;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        $this->ana = $this->empleado('Ana', 'López');
        $this->rita = $this->empleado('Rita', 'Rotativa', ['es_rotativo' => true, 'tarifa_dia' => 120]);
    }

    private function empleado(string $nombre, string $apellido, array $datos = []): int
    {
        return DB::table('empleado')->insertGetId($datos + ['id_empresa' => 1, 'primer_nombre' => $nombre, 'primer_apellido' => $apellido, 'estado' => 'ACTIVO', 'fecha_ingreso' => '2025-01-01']);
    }

    private function cobertura(array $datos = []): array
    {
        return $datos + ['id_rotativo' => $this->rita, 'id_titular' => $this->ana, 'motivo' => 'VACACIONES', 'fecha_inicio' => '2026-10-05', 'fecha_fin' => '2026-10-09', 'paga_fines_semana' => 1];
    }

    public function test_cobertura_calcula_dias_y_total_con_tarifa_dinamica(): void
    {
        $this->actingAs($this->crearUsuario());

        // Lunes 5 a viernes 9 con la tarifa de la ficha: 5 × 120.
        $this->post(route('rotativos.store'), $this->cobertura())->assertSessionHasNoErrors();
        $c = DB::table('cobertura_rotativo')->sole();
        $this->assertSame([5.0, 120.0, 600.0, 'VIGENTE'], [(float) $c->dias, (float) $c->tarifa_dia, (float) $c->total, $c->estado]);

        // Otra cobertura con tarifa propia y sin fines de semana: viernes 16 a martes 20 = 3 días × 150.
        $luis = $this->empleado('Luis', 'Pérez');
        $this->post(route('rotativos.store'), ['id_titular' => $luis, 'motivo' => 'SUSPENSION', 'fecha_inicio' => '2026-10-16', 'fecha_fin' => '2026-10-20', 'tarifa_dia' => 150, 'paga_fines_semana' => 0] + $this->cobertura())
            ->assertSessionHasNoErrors();
        $c = DB::table('cobertura_rotativo')->orderByDesc('id_cobertura')->first();
        $this->assertSame([3.0, 150.0, 450.0], [(float) $c->dias, (float) $c->tarifa_dia, (float) $c->total]);

        $this->get(route('rotativos.index', ['mes' => '2026-10']))->assertOk()->assertSee('Rita Rotativa')->assertSee('1,050.00');
    }

    public function test_validaciones_de_la_cobertura(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->post(route('rotativos.store'), $this->cobertura())->assertSessionHasNoErrors();
        $luis = $this->empleado('Luis', 'Pérez');
        $otra = $this->empleado('Olga', 'Suplente', ['es_rotativo' => true]);

        // El rotativo no puede estar en dos puestos, ni el titular tener dos rotativos, en las mismas fechas.
        $this->post(route('rotativos.store'), $this->cobertura(['id_titular' => $luis, 'fecha_inicio' => '2026-10-09', 'fecha_fin' => '2026-10-12']))->assertSessionHasErrors('id_rotativo');
        $this->post(route('rotativos.store'), $this->cobertura(['id_rotativo' => $otra, 'tarifa_dia' => 100]))->assertSessionHasErrors('id_titular');
        // Sin tarifa en la ficha hay que escribirla; quien no es rotativo no cubre; nadie se cubre a sí mismo.
        $this->post(route('rotativos.store'), $this->cobertura(['id_rotativo' => $otra, 'id_titular' => $luis]))->assertSessionHasErrors('tarifa_dia');
        $this->post(route('rotativos.store'), $this->cobertura(['id_rotativo' => $luis]))->assertSessionHasErrors('id_rotativo');
        $this->post(route('rotativos.store'), $this->cobertura(['id_titular' => $this->rita, 'fecha_inicio' => '2026-11-02', 'fecha_fin' => '2026-11-03']))->assertSessionHasErrors('id_titular');
        // Un puesto vacante no necesita titular; con otro motivo sí.
        $this->post(route('rotativos.store'), $this->cobertura(['id_titular' => null, 'fecha_inicio' => '2026-11-02', 'fecha_fin' => '2026-11-03']))->assertSessionHasErrors('id_titular');
        $this->post(route('rotativos.store'), $this->cobertura(['id_titular' => null, 'motivo' => 'VACANTE', 'fecha_inicio' => '2026-11-02', 'fecha_fin' => '2026-11-03']))->assertSessionHasNoErrors();
        // Empleados de otra empresa no cuentan.
        $ajeno = DB::table('empleado')->insertGetId(['id_empresa' => 2, 'primer_nombre' => 'X', 'estado' => 'ACTIVO', 'es_rotativo' => true, 'tarifa_dia' => 90, 'fecha_ingreso' => '2025-01-01']);
        $this->post(route('rotativos.store'), $this->cobertura(['id_rotativo' => $ajeno, 'id_titular' => $luis]))->assertSessionHasErrors('id_rotativo');
        $this->assertSame(2, DB::table('cobertura_rotativo')->count());
    }

    public function test_nomina_paga_los_dias_cubiertos_del_periodo(): void
    {
        $this->actingAs($this->crearUsuario());
        // Del lunes 12 al domingo 18: solo 4 días (12 al 15) caen en la primera quincena.
        $this->post(route('rotativos.store'), $this->cobertura())->assertSessionHasNoErrors();
        $this->post(route('rotativos.store'), $this->cobertura(['motivo' => 'INCAPACIDAD', 'fecha_inicio' => '2026-10-12', 'fecha_fin' => '2026-10-18', 'tarifa_dia' => 100]))->assertSessionHasNoErrors();

        $this->post(route('nomina.store'), ['tipo' => 'QUINCENAL', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-15', 'fecha_pago' => '2026-10-15', 'moneda' => 'GTQ']);
        $p = DB::table('periodo_nomina')->value('id_periodo');
        $this->patch(route('nomina.procesar', $p))->assertSessionHasNoErrors();

        // Rita no tiene contrato: cobra 5 × 120 + 4 × 100, sin salario base, bonificación ni IGSS.
        $d = DB::table('detalle_nomina')->where('id_empleado', $this->rita)->sole();
        $this->assertSame([1000.0, 0.0, 1000.0], [(float) $d->total_ingresos, (float) $d->total_deducciones, (float) $d->liquido_pagar]);
        $this->assertSame(['ROTATIVO', 'ROTATIVO'], DB::table('detalle_nomina_concepto as l')->join('concepto_nomina as c', 'c.id_concepto', '=', 'l.id_concepto')
            ->where('l.id_detalle', $d->id_detalle)->pluck('c.codigo')->all());
        $this->assertSame(0, DB::table('detalle_nomina')->where('id_empleado', $this->ana)->count()); // Ana sigue «sin contrato»

        // Anular una cobertura y recalcular deja de pagarla.
        $this->patch(route('rotativos.anular', DB::table('cobertura_rotativo')->min('id_cobertura')))->assertSessionHasNoErrors();
        $this->patch(route('nomina.procesar', $p));
        $this->assertSame(400.0, (float) DB::table('detalle_nomina')->where('id_empleado', $this->rita)->value('liquido_pagar'));

        // Con la nómina cerrada ya no se anulan ni se registran coberturas en esas fechas.
        $this->patch(route('nomina.cerrar', $p))->assertSessionHasNoErrors();
        $this->patch(route('rotativos.anular', DB::table('cobertura_rotativo')->max('id_cobertura')))->assertSessionHasErrors('cobertura');
        $this->post(route('rotativos.store'), $this->cobertura(['fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-02']))->assertSessionHasErrors('fecha_inicio');
    }

    public function test_rotativo_con_contrato_cobra_salario_mas_coberturas(): void
    {
        $this->actingAs($this->crearUsuario());
        DB::table('contrato_laboral')->insert(['id_empleado' => $this->rita, 'id_empresa' => 1, 'numero_contrato' => 'CL-1', 'tipo' => 'INDEFINIDO',
            'fecha_inicio' => '2025-01-01', 'salario_base' => 4000, 'estado' => 'VIGENTE']);
        $this->post(route('rotativos.store'), $this->cobertura())->assertSessionHasNoErrors();
        $this->post(route('nomina.store'), ['tipo' => 'QUINCENAL', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-15', 'fecha_pago' => '2026-10-15', 'moneda' => 'GTQ']);
        $this->patch(route('nomina.procesar', DB::table('periodo_nomina')->value('id_periodo')))->assertSessionHasNoErrors();

        // 2,000 de salario + 125 de bonificación + 600 de coberturas; el IGSS solo sobre el salario.
        $d = DB::table('detalle_nomina')->where('id_empleado', $this->rita)->sole();
        $this->assertSame([2725.0, 96.6], [(float) $d->total_ingresos, (float) $d->cuota_igss_emp]);
    }

    public function test_ficha_del_empleado_y_permisos(): void
    {
        $admin = $this->crearUsuario();
        $datos = ['primer_nombre' => 'Olga', 'primer_apellido' => 'Suplente', 'tipo_doc_id' => 'DPI', 'dpi_nit' => '1234567890123', 'fecha_ingreso' => '2026-01-01',
            'tipo_contrato' => 'INDEFINIDO', 'modalidad_trabajo' => 'PRESENCIAL', 'es_rotativo' => 1];
        $this->actingAs($admin)->post(route('empleados.store'), $datos)->assertSessionHasErrors('tarifa_dia');
        $this->actingAs($admin)->post(route('empleados.store'), $datos + ['tarifa_dia' => 110])->assertSessionHasNoErrors();
        $olga = DB::table('empleado')->where('primer_nombre', 'Olga')->sole();
        $this->assertSame([1, 110.0], [(int) $olga->es_rotativo, (float) $olga->tarifa_dia]);

        $lector = $this->crearUsuario(['username' => 'l', 'email' => 'l@nexus.test'], 'Lector');
        $this->darPermisos($lector, ['asistencia.ver']);
        $this->actingAs($lector)->get(route('rotativos.index'))->assertOk();
        $this->actingAs($lector)->post(route('rotativos.store'), $this->cobertura())->assertForbidden();
    }
}
