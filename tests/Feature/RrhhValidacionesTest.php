<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/**
 * Auditoría de nómina y prestaciones, tanda D: hallazgos medios y bajos (prorrateo, fechas,
 * líquidos negativos, préstamos y coberturas fuera de fecha, vacaciones, contratos, finiquito).
 */
class RrhhValidacionesTest extends TestCase
{
    use EsquemaNexus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Carbon::setTestNow('2026-11-04 09:00');
        $this->actingAs($this->crearUsuario());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function empleado(float $salario, string $ingreso = '2025-01-01', array $datos = []): int
    {
        $id = DB::table('empleado')->insertGetId($datos + ['id_empresa' => 1, 'primer_nombre' => 'Ana', 'primer_apellido' => 'López', 'estado' => 'ACTIVO', 'fecha_ingreso' => $ingreso]);
        if ($salario > 0) {
            DB::table('contrato_laboral')->insert(['id_empleado' => $id, 'id_empresa' => 1, 'numero_contrato' => 'CL-'.$id, 'tipo' => 'INDEFINIDO',
                'fecha_inicio' => $ingreso, 'salario_base' => $salario, 'moneda' => 'GTQ', 'estado' => 'VIGENTE']);
        }

        return $id;
    }

    private function periodo(string $tipo, string $inicio, string $fin): int
    {
        $this->post(route('nomina.store'), ['tipo' => $tipo, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'fecha_pago' => $fin, 'moneda' => 'GTQ'])->assertSessionHasNoErrors();

        return (int) DB::table('periodo_nomina')->max('id_periodo');
    }

    private function detalle(int $periodo, int $empleado): object
    {
        return DB::table('detalle_nomina')->where('id_periodo', $periodo)->where('id_empleado', $empleado)->first();
    }

    public function test_dias_proporcionales_sobre_la_base_de_30(): void
    {
        $feb = $this->empleado(3000, '2026-02-15', ['primer_nombre' => 'Feb']);
        $oct = $this->empleado(3000, '2026-10-17', ['primer_nombre' => 'Oct']);
        $p1 = $this->periodo('MENSUAL', '2026-02-01', '2026-02-28');
        $this->patch(route('nomina.procesar', $p1));
        $this->assertSame(15.0, (float) $this->detalle($p1, $feb)->dias_trabajados); // 14 de 28 días = la mitad del mes

        $p2 = $this->periodo('QUINCENAL', '2026-10-16', '2026-10-31');
        $this->patch(route('nomina.procesar', $p2));
        $this->assertSame(14.06, (float) $this->detalle($p2, $oct)->dias_trabajados); // 15 de 16 días, no la quincena completa
    }

    public function test_las_fechas_corresponden_al_tipo_de_periodo(): void
    {
        $this->post(route('nomina.store'), ['tipo' => 'MENSUAL', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-15', 'fecha_pago' => '2026-10-15', 'moneda' => 'GTQ'])
            ->assertSessionHasErrors(['fecha_fin' => 'Un periodo mensual dura de 28 a 31 días; estas fechas abarcan 15.']);
        $this->post(route('nomina.store'), ['tipo' => 'QUINCENAL', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-31', 'fecha_pago' => '2026-10-31', 'moneda' => 'GTQ'])
            ->assertSessionHasErrors('fecha_fin');
        $this->assertSame(0, DB::table('periodo_nomina')->count());
    }

    public function test_no_se_cierra_con_liquido_negativo_ni_se_paga_a_futuro(): void
    {
        $ana = $this->empleado(3000);
        $p = $this->periodo('QUINCENAL', '2026-10-01', '2026-10-15');
        $this->patch(route('nomina.procesar', $p));
        $descuento = DB::table('concepto_nomina')->where('codigo', 'AUSENCIA')->value('id_concepto');
        $this->patch(route('nomina.ajustar', [$p, $this->detalle($p, $ana)->id_detalle]), ['id_concepto' => $descuento, 'monto' => 5000])->assertSessionHasNoErrors();

        $this->patch(route('nomina.cerrar', $p))->assertSessionHasErrors(['periodo' => 'El líquido queda negativo para Ana López: ajusta sus descuentos antes de cerrar.']);
        $this->assertSame('EN_PROCESO', DB::table('periodo_nomina')->value('estado'));

        DB::table('detalle_nomina_concepto')->where('id_concepto', $descuento)->delete();
        $this->patch(route('nomina.procesar', $p));
        $this->patch(route('nomina.cerrar', $p))->assertSessionHasNoErrors();
        $this->patch(route('nomina.pagar', $p), ['fecha_pago' => '2026-11-20'])->assertSessionHasErrors('fecha_pago');
        $this->patch(route('nomina.pagar', $p), ['fecha_pago' => '2026-11-04'])->assertSessionHasNoErrors();
    }

    public function test_prestamo_y_cobertura_respetan_sus_fechas(): void
    {
        $ana = $this->empleado(4000);
        $this->post(route('nomina.prestamos.store'), ['id_empleado' => $ana, 'monto_total' => 500, 'cuota_quincenal' => 100, 'fecha_otorgamiento' => '2026-10-20'])->assertSessionHasNoErrors();
        $rita = $this->empleado(0, '2026-10-06', ['primer_nombre' => 'Rita', 'es_rotativo' => true, 'tarifa_dia' => 100]);

        // La cobertura no puede empezar antes del ingreso del rotativo.
        $cobertura = ['id_rotativo' => $rita, 'id_titular' => $ana, 'motivo' => 'VACACIONES', 'fecha_inicio' => '2026-10-05', 'fecha_fin' => '2026-10-12', 'paga_fines_semana' => 1];
        $this->post(route('rotativos.store'), $cobertura)->assertSessionHasErrors('fecha_inicio');
        $this->post(route('rotativos.store'), ['fecha_inicio' => '2026-10-06'] + $cobertura)->assertSessionHasNoErrors();
        DB::table('empleado')->where('id_empleado', $rita)->update(['estado' => 'BAJA', 'fecha_baja' => '2026-10-09']);

        $p = $this->periodo('QUINCENAL', '2026-10-01', '2026-10-15');
        $this->patch(route('nomina.procesar', $p));
        // El préstamo es del 20 de octubre: no se descuenta en la primera quincena.
        $this->assertSame(0, DB::table('detalle_nomina_concepto as l')->join('concepto_nomina as c', 'c.id_concepto', '=', 'l.id_concepto')
            ->where('l.id_detalle', $this->detalle($p, $ana)->id_detalle)->where('c.codigo', 'PRESTAMO')->count());
        // Rita cubrió del 6 al 12 pero se fue el 9: se le pagan 4 días, no 7.
        $this->assertSame(400.0, (float) $this->detalle($p, $rita)->total_ingresos);
    }

    public function test_liquidacion_contratos_y_finiquito(): void
    {
        $ana = $this->empleado(3000, '2026-01-01');

        // Vacaciones: no más que las disponibles a la salida (304 días de servicio → 12.49).
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $ana, 'fecha_salida' => '2026-10-31', 'dias_vacaciones' => 20])->assertSessionHasErrorsIn('liquidacion', 'dias_vacaciones');
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $ana, 'fecha_salida' => '2026-10-31', 'dias_vacaciones' => 12])->assertSessionHasNoErrors();

        // El finiquito es borrador mientras haya montos sin aprobar.
        $this->get(route('prestaciones.finiquito', [$ana, 'LIQ-20261031']))->assertOk()->assertSee('BORRADOR');
        $this->patch(route('prestaciones.lote.aprobar'), ['periodo' => 'LIQ-20261031', 'id_empleado' => $ana])->assertSessionHasNoErrors();
        $this->get(route('prestaciones.finiquito', [$ana, 'LIQ-20261031']))->assertOk()->assertDontSee('BORRADOR');
        $id = DB::table('prestacion_laboral')->where('id_empleado', $ana)->value('id_prestacion');
        $this->patch(route('prestaciones.pagar', $id), ['fecha_pago' => '2026-12-01'])->assertSessionHasErrors('fecha_pago');

        // Un contrato nuevo no puede empezar antes (ni el mismo día) que el vigente.
        $luis = $this->empleado(3000, '2026-03-01', ['primer_nombre' => 'Luis']);
        $contrato = ['tipo' => 'INDEFINIDO', 'salario_base' => 3500, 'moneda' => 'GTQ', 'jornada' => 'COMPLETA', 'horas_semana' => 44];
        $this->post(route('empleados.contrato', $luis), ['fecha_inicio' => '2026-03-01'] + $contrato)->assertSessionHasErrorsIn('contrato', 'fecha_inicio');
        $this->post(route('empleados.contrato', $luis), ['fecha_inicio' => '2026-09-01'] + $contrato)->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('contrato_laboral')->where('id_empleado', $luis)->where('estado', 'VIGENTE')->count());
    }
}
