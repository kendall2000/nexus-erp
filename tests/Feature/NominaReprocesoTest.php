<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/**
 * Auditoría de nómina, tanda A: reprocesar y cerrar sin pagar de más.
 * Préstamos descontados una sola vez, detalles que ya no corresponden, días recalculados
 * salvo ajuste manual y periodos que se cruzan.
 */
class NominaReprocesoTest extends TestCase
{
    use EsquemaNexus;

    private function empleado(float $salario, string $ingreso = '2025-01-01', string $nombre = 'Ana'): int
    {
        $id = DB::table('empleado')->insertGetId(['id_empresa' => 1, 'primer_nombre' => $nombre, 'primer_apellido' => 'López', 'estado' => 'ACTIVO', 'fecha_ingreso' => $ingreso]);
        DB::table('contrato_laboral')->insert(['id_empleado' => $id, 'id_empresa' => 1, 'numero_contrato' => 'CL-'.$id, 'tipo' => 'INDEFINIDO',
            'fecha_inicio' => $ingreso, 'salario_base' => $salario, 'estado' => 'VIGENTE']);

        return $id;
    }

    private function periodo(string $tipo, string $inicio, string $fin): int
    {
        $this->post(route('nomina.store'), ['tipo' => $tipo, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'fecha_pago' => $fin, 'moneda' => 'GTQ'])->assertSessionHasNoErrors();

        return (int) DB::table('periodo_nomina')->orderByDesc('id_periodo')->value('id_periodo');
    }

    private function detalle(int $periodo, int $empleado): ?object
    {
        return DB::table('detalle_nomina')->where('id_periodo', $periodo)->where('id_empleado', $empleado)->first();
    }

    private function prestamoDe(int $periodo, int $empleado): float
    {
        return (float) DB::table('detalle_nomina_concepto as l')->join('concepto_nomina as c', 'c.id_concepto', '=', 'l.id_concepto')
            ->where('l.id_detalle', $this->detalle($periodo, $empleado)->id_detalle)->where('c.codigo', 'PRESTAMO')->sum('l.monto');
    }

    public function test_un_prestamo_no_se_descuenta_dos_veces_y_reabrir_es_simetrico(): void
    {
        $this->actingAs($this->crearUsuario());
        $ana = $this->empleado(4000);
        $this->post(route('nomina.prestamos.store'), ['id_empleado' => $ana, 'monto_total' => 100, 'cuota_quincenal' => 100, 'fecha_otorgamiento' => '2026-09-01'])->assertSessionHasNoErrors();
        $a = $this->periodo('QUINCENAL', '2026-10-01', '2026-10-15');
        $b = $this->periodo('QUINCENAL', '2026-10-16', '2026-10-31');
        $this->patch(route('nomina.procesar', $a));
        $this->patch(route('nomina.procesar', $b));
        $this->assertSame([100.0, 100.0], [$this->prestamoDe($a, $ana), $this->prestamoDe($b, $ana)]); // los dos lo calculan

        $this->patch(route('nomina.cerrar', $a))->assertSessionHasNoErrors();
        $liquidoAntes = (float) $this->detalle($b, $ana)->liquido_pagar;

        // B ya no puede cerrarse tal cual: el préstamo cambió desde su cálculo. Se avisa y no se toca nada.
        $this->patch(route('nomina.cerrar', $b))->assertSessionHasErrors(['periodo' => 'Hubo cambios desde el último cálculo (Ana López). Vuelve a procesar el periodo, revísalo y ciérralo.']);
        $this->assertSame(['EN_PROCESO', 100.0], [DB::table('periodo_nomina')->where('id_periodo', $b)->value('estado'), $this->prestamoDe($b, $ana)]);

        // Reprocesado, B ya no descuenta nada: el préstamo de Q100 se cobró una sola vez.
        $this->patch(route('nomina.procesar', $b))->assertSessionHasNoErrors();
        $this->patch(route('nomina.cerrar', $b))->assertSessionHasNoErrors();
        $this->assertSame(0.0, $this->prestamoDe($b, $ana));
        $this->assertEqualsWithDelta($liquidoAntes + 100, (float) $this->detalle($b, $ana)->liquido_pagar, 0.001);
        $this->assertEqualsWithDelta((float) DB::table('detalle_nomina')->where('id_periodo', $b)->sum('liquido_pagar'), (float) DB::table('periodo_nomina')->where('id_periodo', $b)->value('total_neto'), 0.001);
        $this->assertSame(['LIQUIDADO', 0.0], [DB::table('prestamo_empleado')->value('estado'), (float) DB::table('prestamo_empleado')->value('monto_pendiente')]);

        // Reabrir B no devuelve saldo (no descontó nada); reabrir A sí devuelve sus Q100.
        $this->patch(route('nomina.reabrir', $b))->assertSessionHasNoErrors();
        $this->assertSame(['LIQUIDADO', 0.0], [DB::table('prestamo_empleado')->value('estado'), (float) DB::table('prestamo_empleado')->value('monto_pendiente')]);
        $this->patch(route('nomina.reabrir', $a))->assertSessionHasNoErrors();
        $this->assertSame(['ACTIVO', 100.0], [DB::table('prestamo_empleado')->value('estado'), (float) DB::table('prestamo_empleado')->value('monto_pendiente')]);
    }

    public function test_al_reprocesar_sale_quien_ya_no_corresponde(): void
    {
        $this->actingAs($this->crearUsuario());
        $ana = $this->empleado(4000);
        $luis = $this->empleado(3000, '2025-01-01', 'Luis');
        $p = $this->periodo('QUINCENAL', '2026-10-16', '2026-10-31');
        $this->patch(route('nomina.procesar', $p));
        $this->assertNotNull($this->detalle($p, $luis));

        // Se registra que Luis salió antes del periodo: al reprocesar ya no se le paga.
        DB::table('empleado')->where('id_empleado', $luis)->update(['estado' => 'BAJA', 'fecha_baja' => '2026-10-10']);
        $this->patch(route('nomina.procesar', $p))->assertSessionHasNoErrors()->assertSessionHas('status', fn ($m) => str_contains($m, 'Se quitaron 1'));

        $this->assertNull($this->detalle($p, $luis));
        $this->assertSame(0, DB::table('detalle_nomina_concepto')->whereNotIn('id_detalle', DB::table('detalle_nomina')->pluck('id_detalle'))->count());
        $this->assertEqualsWithDelta((float) $this->detalle($p, $ana)->liquido_pagar, (float) DB::table('periodo_nomina')->where('id_periodo', $p)->value('total_neto'), 0.001);
    }

    public function test_los_dias_se_recalculan_salvo_que_se_ajusten_a_mano(): void
    {
        $this->actingAs($this->crearUsuario());
        $ana = $this->empleado(4000);
        $luis = $this->empleado(3000, '2025-01-01', 'Luis');
        $p = $this->periodo('QUINCENAL', '2026-10-01', '2026-10-15');
        $this->patch(route('nomina.procesar', $p));
        $this->assertSame([15.0, 15.0], [(float) $this->detalle($p, $ana)->dias_trabajados, (float) $this->detalle($p, $luis)->dias_trabajados]);

        // A Luis se le ajustan los días a mano; después las dos tienen dos ausencias registradas.
        $this->patch(route('nomina.ajustar', [$p, $this->detalle($p, $luis)->id_detalle]), ['dias_trabajados' => 14])->assertSessionHasNoErrors();
        foreach ([$ana, $luis] as $e) {
            DB::table('asistencia')->insert([['id_empleado' => $e, 'id_empresa' => 1, 'fecha' => '2026-10-05', 'estado' => 'AUSENTE'], ['id_empleado' => $e, 'id_empresa' => 1, 'fecha' => '2026-10-06', 'estado' => 'AUSENTE']]);
        }
        $this->patch(route('nomina.procesar', $p))->assertSessionHasNoErrors();

        $this->assertSame(13.0, (float) $this->detalle($p, $ana)->dias_trabajados); // recalculado: 15 − 2 ausencias
        $this->assertSame(14.0, (float) $this->detalle($p, $luis)->dias_trabajados); // ajustado a mano: se respeta
        $this->assertTrue((bool) $this->detalle($p, $luis)->dias_manual);
        $this->assertFalse((bool) $this->detalle($p, $ana)->dias_manual);
    }

    public function test_periodos_de_distinto_tipo_no_se_cruzan(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->empleado(4000);
        $this->periodo('MENSUAL', '2026-10-01', '2026-10-31');

        $this->post(route('nomina.store'), ['tipo' => 'QUINCENAL', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-15', 'fecha_pago' => '2026-10-15', 'moneda' => 'GTQ'])
            ->assertSessionHasErrors(['fecha_inicio' => 'Esas fechas se cruzan con el periodo «Nómina de octubre 2026»: se pagarían dos veces los mismos días.']);
        $this->assertSame(1, DB::table('periodo_nomina')->count());

        $this->periodo('QUINCENAL', '2026-11-01', '2026-11-15'); // sin cruce: se crea
        $this->assertSame(2, DB::table('periodo_nomina')->count());
    }
}
