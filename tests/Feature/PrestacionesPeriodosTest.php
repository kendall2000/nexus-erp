<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/**
 * Auditoría de prestaciones, tanda B: cada periodo de aguinaldo y bono 14 se paga una sola vez
 * (cálculo anual o liquidación), también a quien ya se fue, solo cuando ya terminó, y un
 * reactivado empieza una relación laboral nueva.
 */
class PrestacionesPeriodosTest extends TestCase
{
    use EsquemaNexus;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function empleado(float $salario, string $ingreso = '2020-01-01', array $datos = []): int
    {
        $id = DB::table('empleado')->insertGetId($datos + ['id_empresa' => 1, 'primer_nombre' => 'Ana', 'primer_apellido' => 'López', 'estado' => 'ACTIVO', 'fecha_ingreso' => $ingreso]);
        DB::table('contrato_laboral')->insert(['id_empleado' => $id, 'id_empresa' => 1, 'numero_contrato' => 'CL-'.$id, 'tipo' => 'INDEFINIDO',
            'fecha_inicio' => $ingreso, 'salario_base' => $salario, 'estado' => 'VIGENTE']);

        return $id;
    }

    /** @return array<string, float> tipo => monto de ese periodo */
    private function montos(int $empleado, string $periodo): array
    {
        return DB::table('prestacion_laboral')->where('id_empleado', $empleado)->where('periodo_calculo', $periodo)->pluck('monto_calculado', 'tipo')->map(fn ($m) => (float) $m)->all();
    }

    public function test_quien_se_fue_despues_del_cierre_cobra_el_periodo_cumplido(): void
    {
        Carbon::setTestNow('2026-12-10 09:00');
        $this->actingAs($this->crearUsuario());
        $ana = $this->empleado(3650);
        $this->patch(route('empleados.baja', $ana), ['fecha_baja' => '2026-12-05', 'motivo_baja' => 'Renuncia voluntaria'])->assertSessionHasNoErrors();
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $ana, 'fecha_salida' => '2026-12-05'])->assertSessionHasNoErrors();

        // La liquidación cubre lo que corre desde el 1 de diciembre (5 días) y el bono 14 desde julio.
        $this->assertSame(['AGUINALDO' => 50.0, 'BONO14' => 1580.0], $this->montos($ana, 'LIQ-20261205'));

        // El aguinaldo de dic 2025 – nov 2026 sigue debiéndose: el cálculo anual lo incluye completo.
        $this->post(route('prestaciones.calcular'), ['tipo' => 'AGUINALDO', 'anio' => 2026])->assertSessionHasNoErrors();
        $this->assertSame(['AGUINALDO' => 3650.0], $this->montos($ana, '2026'));
    }

    public function test_el_periodo_lo_paga_la_liquidacion_o_el_calculo_anual_no_los_dos(): void
    {
        $this->actingAs($this->crearUsuario());

        // Liquidada en junio (sigue activa en el sistema): en julio el bono 14 anual no la incluye.
        Carbon::setTestNow('2026-07-10 09:00');
        $ana = $this->empleado(3650);
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $ana, 'fecha_salida' => '2026-06-15'])->assertSessionHasNoErrors();
        $this->assertSame(3500.0, $this->montos($ana, 'LIQ-20260615')['BONO14']); // 350 días
        $this->post(route('prestaciones.calcular'), ['tipo' => 'BONO14', 'anio' => 2026])->assertSessionHas('status', fn ($m) => str_contains($m, '1 con liquidación'));
        $this->assertSame([], $this->montos($ana, '2026'));

        // Aguinaldo anual ya aprobado y luego liquidación en ese mismo periodo: no lo vuelve a pagar.
        Carbon::setTestNow('2026-12-02 09:00');
        $luis = $this->empleado(3650, '2020-01-01', ['primer_nombre' => 'Luis']);
        $this->post(route('prestaciones.calcular'), ['tipo' => 'AGUINALDO', 'anio' => 2026])->assertSessionHasNoErrors();
        $this->patch(route('prestaciones.aprobar', DB::table('prestacion_laboral')->where(['id_empleado' => $luis, 'tipo' => 'AGUINALDO'])->value('id_prestacion')))->assertSessionHasNoErrors();
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $luis, 'fecha_salida' => '2026-11-30'])->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('AGUINALDO', $this->montos($luis, 'LIQ-20261130'));
        $this->assertArrayHasKey('BONO14', $this->montos($luis, 'LIQ-20261130'));

        // Si el anual aún no se aprobó, la liquidación lo reemplaza (queda uno solo).
        $rosa = $this->empleado(3650, '2020-01-01', ['primer_nombre' => 'Rosa']);
        $this->post(route('prestaciones.calcular'), ['tipo' => 'AGUINALDO', 'anio' => 2026]);
        $this->assertArrayHasKey('AGUINALDO', $this->montos($rosa, '2026'));
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $rosa, 'fecha_salida' => '2026-11-30'])->assertSessionHasNoErrors();
        $this->assertSame([], $this->montos($rosa, '2026'));
        $this->assertSame(3650.0, $this->montos($rosa, 'LIQ-20261130')['AGUINALDO']);
    }

    public function test_no_se_calcula_un_periodo_que_no_ha_terminado(): void
    {
        Carbon::setTestNow('2026-11-04 09:00');
        $this->actingAs($this->crearUsuario());
        $this->empleado(4000);

        $this->post(route('prestaciones.calcular'), ['tipo' => 'AGUINALDO', 'anio' => 2026])->assertSessionHasErrors('anio');
        $this->post(route('prestaciones.calcular'), ['tipo' => 'AGUINALDO', 'anio' => 2027])->assertSessionHasErrors('anio');
        $this->assertSame(0, DB::table('prestacion_laboral')->count());
        $this->post(route('prestaciones.calcular'), ['tipo' => 'BONO14', 'anio' => 2026])->assertSessionHasNoErrors(); // jul 2025 – jun 2026 ya terminó
        $this->assertSame(1, DB::table('prestacion_laboral')->count());
    }

    public function test_un_reactivado_empieza_una_relacion_nueva(): void
    {
        Carbon::setTestNow('2026-11-04 09:00');
        $this->actingAs($this->crearUsuario());
        $ana = $this->empleado(3650);
        $this->patch(route('empleados.baja', $ana), ['fecha_baja' => '2026-03-31', 'motivo_baja' => 'Renuncia voluntaria']);
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $ana, 'fecha_salida' => '2026-03-31', 'indemnizacion' => 1])->assertSessionHasNoErrors();
        $this->patch(route('prestaciones.lote.aprobar'), ['periodo' => 'LIQ-20260331'])->assertSessionHasNoErrors();

        $this->patch(route('empleados.reactivar', $ana))->assertSessionHasErrors('fecha_reingreso');
        $this->patch(route('empleados.reactivar', $ana), ['fecha_reingreso' => '2026-03-15'])->assertSessionHasErrors('fecha_reingreso');
        $this->patch(route('empleados.reactivar', $ana), ['fecha_reingreso' => '2026-06-01'])->assertSessionHasNoErrors();
        $this->assertSame('2026-06-01', substr((string) DB::table('empleado')->where('id_empleado', $ana)->value('fecha_ingreso'), 0, 10));

        // Sin contrato nuevo no hay salario: el contrato rescindido no rige después de la baja.
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $ana, 'fecha_salida' => '2026-10-31'])->assertSessionHasErrorsIn('liquidacion', 'id_empleado');

        // Con contrato nuevo se liquida otra vez, contando solo desde el reingreso (153 días).
        $this->post(route('empleados.contrato', $ana), ['tipo' => 'INDEFINIDO', 'fecha_inicio' => '2026-06-01', 'salario_base' => 3650, 'moneda' => 'GTQ', 'jornada' => 'COMPLETA', 'horas_semana' => 44])->assertSessionHasNoErrors();
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $ana, 'fecha_salida' => '2026-10-31', 'indemnizacion' => 1])->assertSessionHasNoErrors();
        $nueva = DB::table('prestacion_laboral')->where(['id_empleado' => $ana, 'tipo' => 'INDEMNIZACION', 'periodo_calculo' => 'LIQ-20261031'])->first();
        $this->assertSame(153.0, (float) $nueva->dias_calculados);
        $this->assertSame(1, DB::table('prestacion_laboral')->where(['id_empleado' => $ana, 'periodo_calculo' => 'LIQ-20260331', 'tipo' => 'INDEMNIZACION', 'estado' => 'APROBADO'])->count());
    }
}
