<?php

namespace Tests\Feature;

use App\Support\Nomina;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 7f: Nómina (cálculo guatemalteco, ajustes, préstamos, cierre y pago). */
class NominaTest extends TestCase
{
    use EsquemaNexus;

    private function empleado(float $salario, string $ingreso = '2025-01-01', array $datos = []): int
    {
        $id = DB::table('empleado')->insertGetId($datos + ['id_empresa' => 1, 'primer_nombre' => 'Ana', 'primer_apellido' => 'López', 'estado' => 'ACTIVO', 'fecha_ingreso' => $ingreso]);
        DB::table('contrato_laboral')->insert(['id_empleado' => $id, 'id_empresa' => 1, 'numero_contrato' => 'CL-'.$id, 'tipo' => 'INDEFINIDO',
            'fecha_inicio' => $ingreso, 'salario_base' => $salario, 'estado' => 'VIGENTE']);

        return $id;
    }

    private function periodo(string $tipo = 'QUINCENAL', string $inicio = '2026-10-01', string $fin = '2026-10-15'): int
    {
        $this->post(route('nomina.store'), ['tipo' => $tipo, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'fecha_pago' => $fin, 'moneda' => 'GTQ'])->assertSessionHasNoErrors();

        return (int) DB::table('periodo_nomina')->orderByDesc('id_periodo')->value('id_periodo');
    }

    private function detalle(int $empleado): ?object
    {
        return DB::table('detalle_nomina')->where('id_empleado', $empleado)->first();
    }

    private function montos(int $empleado): array
    {
        return DB::table('detalle_nomina_concepto as l')->join('concepto_nomina as c', 'c.id_concepto', '=', 'l.id_concepto')
            ->where('l.id_detalle', $this->detalle($empleado)->id_detalle)->pluck('l.monto', 'c.codigo')->map(fn ($m) => (float) $m)->all();
    }

    public function test_quincena_completa_sin_isr(): void
    {
        $this->actingAs($this->crearUsuario());
        $ana = $this->empleado(4000);
        $p = $this->periodo();
        $this->assertSame('Quincena 1 de octubre 2026', DB::table('periodo_nomina')->value('nombre'));

        $this->patch(route('nomina.procesar', $p))->assertSessionHasNoErrors();
        // 4,000 / 2 = 2,000; bonificación 125; IGSS 4.83 % = 96.60; ISR 0 (no pasa de la deducción única).
        $this->assertSame(['SALBASE' => 2000.0, 'BONINC' => 125.0, 'IGSS' => 96.6], $this->montos($ana));
        $d = $this->detalle($ana);
        $this->assertSame([15.0, 2125.0, 96.6, 2028.4, 253.4], [(float) $d->dias_trabajados, (float) $d->total_ingresos, (float) $d->total_deducciones, (float) $d->liquido_pagar, (float) $d->cuota_igss_pat]);
        $this->assertSame(['EN_PROCESO', 2028.4], [DB::table('periodo_nomina')->value('estado'), (float) DB::table('periodo_nomina')->value('total_neto')]);
    }

    public function test_mes_con_isr_proyectado(): void
    {
        $this->actingAs($this->crearUsuario());
        $jefe = $this->empleado(10000);
        $p = $this->periodo('MENSUAL', '2026-10-01', '2026-10-31');
        $this->patch(route('nomina.procesar', $p));

        // Renta anual = 120,000 − 48,000 − 5,796 (IGSS) = 66,204 → 5 % = 3,310.20 → 275.85 al mes.
        $this->assertSame(275.85, $this->montos($jefe)['ISR']);
        $this->assertSame(9491.15, (float) $this->detalle($jefe)->liquido_pagar);
        $this->assertSame(0.0, Nomina::isrDelPeriodo(4000, 193.2, 12));
        // Tramo alto: 400,000 → 15,000 + 7 % de 100,000 = 22,000 al año.
        $this->assertSame(22000.0, Nomina::isrDelPeriodo(448000, 0, 1));
    }

    public function test_ingreso_a_medio_periodo_y_sin_contrato(): void
    {
        $this->actingAs($this->crearUsuario());
        $nuevo = $this->empleado(4000, '2026-10-10');
        $sinContrato = DB::table('empleado')->insertGetId(['id_empresa' => 1, 'primer_nombre' => 'Sin', 'primer_apellido' => 'Contrato', 'estado' => 'ACTIVO', 'fecha_ingreso' => '2025-01-01']);
        $p = $this->periodo();

        $this->patch(route('nomina.procesar', $p))->assertSessionHas('status', fn ($s) => str_contains($s, 'Sin Contrato'));
        $this->assertSame(6.0, (float) $this->detalle($nuevo)->dias_trabajados); // del 10 al 15
        $this->assertSame(800.0, $this->montos($nuevo)['SALBASE']); // 4,000 × 6/15 × ½
        $this->assertNull($this->detalle($sinContrato));
    }

    public function test_horas_extra_y_ajustes_manuales_sobreviven_al_recalculo(): void
    {
        $this->actingAs($this->crearUsuario());
        $ana = $this->empleado(4000);
        $p = $this->periodo();
        $this->patch(route('nomina.procesar', $p));
        $d = $this->detalle($ana)->id_detalle;
        $uniforme = DB::table('concepto_nomina')->where('codigo', 'UNIFORME')->value('id_concepto');

        $this->patch(route('nomina.ajustar', [$p, $d]), ['horas_extra' => 4, 'id_concepto' => $uniforme, 'monto' => 50])->assertSessionHasNoErrors();
        // 4 h × (4,000 / 30 / 8) × 1.5 = 100; el IGSS sube porque las horas extra son afectas.
        $montos = $this->montos($ana);
        $this->assertSame([100.0, 101.43, 50.0], [$montos['HEXTRA'], $montos['IGSS'], $montos['UNIFORME']]);

        $this->patch(route('nomina.procesar', $p)); // recalcular conserva el descuento manual
        $this->assertSame(50.0, $this->montos($ana)['UNIFORME']);
        $this->patch(route('nomina.ajustar', [$p, $d]), ['dias_trabajados' => 16])->assertSessionHasErrorsIn('ajuste', 'dias_trabajados');
        $igss = DB::table('detalle_nomina_concepto')->where('id_detalle', $d)->where('id_concepto', DB::table('concepto_nomina')->where('codigo', 'IGSS')->value('id_concepto'))->value('id_linea');
        $this->delete(route('nomina.conceptos.destroy', [$p, $igss]))->assertForbidden(); // los automáticos no se quitan
    }

    public function test_prestamo_se_descuenta_al_cerrar_y_vuelve_al_reabrir(): void
    {
        $this->actingAs($this->crearUsuario());
        $ana = $this->empleado(4000);
        $this->post(route('nomina.prestamos.store'), ['id_empleado' => $ana, 'monto_total' => 500, 'cuota_quincenal' => 300, 'fecha_otorgamiento' => '2026-09-01'])->assertSessionHasNoErrors();
        $p = $this->periodo();
        $this->patch(route('nomina.procesar', $p));
        $this->assertSame(300.0, $this->montos($ana)['PRESTAMO']);

        $this->patch(route('nomina.cerrar', $p))->assertSessionHasNoErrors();
        $this->assertSame(200.0, (float) DB::table('prestamo_empleado')->value('monto_pendiente'));
        $this->patch(route('nomina.procesar', $p))->assertSessionHasErrors('periodo'); // cerrado: no se recalcula

        $this->patch(route('nomina.reabrir', $p))->assertSessionHasNoErrors();
        $this->assertSame(500.0, (float) DB::table('prestamo_empleado')->value('monto_pendiente'));

        $this->patch(route('nomina.cerrar', $p));
        $this->patch(route('nomina.pagar', $p), ['fecha_pago' => '2026-10-15'])->assertSessionHasNoErrors();
        $this->assertSame(['PAGADO', 'PAGADO'], [DB::table('periodo_nomina')->value('estado'), DB::table('detalle_nomina')->value('estado_pago')]);
        $this->patch(route('nomina.reabrir', $p))->assertSessionHasErrors('periodo'); // pagado ya no se reabre
    }

    public function test_periodos_que_se_cruzan_pantallas_y_permisos(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin);
        $this->empleado(4000);
        $p = $this->periodo();
        $this->post(route('nomina.store'), ['tipo' => 'QUINCENAL', 'fecha_inicio' => '2026-10-10', 'fecha_fin' => '2026-10-25', 'fecha_pago' => '2026-10-25', 'moneda' => 'GTQ'])
            ->assertSessionHasErrors('fecha_inicio');
        $this->patch(route('nomina.procesar', $p));
        $this->get(route('nomina.index'))->assertOk()->assertSee('Quincena 1 de octubre 2026');
        $this->get(route('nomina.show', $p))->assertOk()->assertSee('Ana López')->assertSee('2,028.40');
        $this->get(route('nomina.imprimir', $p))->assertOk()->assertSee('BOLETA DE PAGO')->assertSee('Bonificación incentivo');

        $rrhh = $this->crearUsuario(['username' => 'r', 'email' => 'r@nexus.test'], 'RRHH');
        $this->darPermisos($rrhh, ['nomina.ver']);
        $this->actingAs($rrhh)->get(route('nomina.show', $p))->assertOk()->assertDontSee('Recalcular');
        $this->actingAs($rrhh)->patch(route('nomina.procesar', $p))->assertForbidden();
        $this->actingAs($rrhh)->get(route('nomina.imprimir', $p))->assertForbidden();
    }
}
