<?php

namespace Tests\Feature;

use App\Models\RRHH\Empleado;
use App\Support\Prestaciones;
use App\Support\Salarios;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Auditoría de nómina y prestaciones, tanda C: el salario cuenta desde su fecha efectiva, no desde que se registró. */
class SalarioFechaEfectivaTest extends TestCase
{
    use EsquemaNexus;

    private int $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Carbon::setTestNow('2026-11-04 09:00');
        $this->actingAs($this->crearUsuario());
        $this->ana = DB::table('empleado')->insertGetId(['id_empresa' => 1, 'primer_nombre' => 'Ana', 'primer_apellido' => 'López', 'estado' => 'ACTIVO', 'fecha_ingreso' => '2024-01-01']);
        DB::table('contrato_laboral')->insert(['id_empleado' => $this->ana, 'id_empresa' => 1, 'numero_contrato' => 'CL-1', 'tipo' => 'INDEFINIDO',
            'fecha_inicio' => '2024-01-01', 'salario_base' => 4000, 'moneda' => 'GTQ', 'estado' => 'VIGENTE']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function aumento(float $nuevo, string $efectiva): void
    {
        $this->post(route('empleados.salario', $this->ana), ['tipo_cambio' => 'AUMENTO', 'salario_nuevo' => $nuevo, 'fecha_efectiva' => $efectiva, 'motivo' => 'Revisión anual'])->assertSessionHasNoErrors();
    }

    private function salarioEl(string $fecha): ?float
    {
        return Salarios::vigente(Empleado::query()->find($this->ana), Carbon::parse($fecha));
    }

    public function test_cada_fecha_usa_el_salario_que_regia(): void
    {
        $this->aumento(5000, '2026-10-10');  // registrado hoy, efectivo en octubre
        $this->aumento(6000, '2026-12-01');  // registrado hoy, efectivo en el futuro

        $this->assertSame(6000.0, (float) DB::table('contrato_laboral')->value('salario_base')); // el contrato guarda el último acordado
        $this->assertSame([4000.0, 4000.0, 5000.0, 5000.0, 6000.0], [
            $this->salarioEl('2024-01-01'), $this->salarioEl('2026-10-09'), $this->salarioEl('2026-10-10'), $this->salarioEl('2026-11-30'), $this->salarioEl('2026-12-01'),
        ]);
        $this->assertNull($this->salarioEl('2023-12-31')); // antes del contrato
    }

    public function test_la_nomina_prorratea_el_aumento_y_no_adelanta_el_futuro(): void
    {
        $this->aumento(5000, '2026-10-10');
        $this->aumento(6000, '2026-12-01');

        // Quincena 1–15 de octubre: 9 días a 4,000 y 6 a 5,000 → 4,400 de promedio → 2,200 de salario.
        $this->post(route('nomina.store'), ['tipo' => 'QUINCENAL', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-15', 'fecha_pago' => '2026-10-15', 'moneda' => 'GTQ']);
        $p = (int) DB::table('periodo_nomina')->max('id_periodo');
        $this->patch(route('nomina.procesar', $p))->assertSessionHasNoErrors();
        $d = DB::table('detalle_nomina')->where('id_periodo', $p)->first();
        $this->assertSame(4400.0, (float) $d->salario_base);
        $salbase = DB::table('detalle_nomina_concepto as l')->join('concepto_nomina as c', 'c.id_concepto', '=', 'l.id_concepto')
            ->where('l.id_detalle', $d->id_detalle)->where('c.codigo', 'SALBASE')->value('l.monto');
        $this->assertSame(2200.0, (float) $salbase);

        // Noviembre todavía va a 5,000: el aumento a 6,000 es de diciembre.
        $this->post(route('nomina.store'), ['tipo' => 'QUINCENAL', 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-15', 'fecha_pago' => '2026-11-15', 'moneda' => 'GTQ']);
        $p2 = (int) DB::table('periodo_nomina')->max('id_periodo');
        $this->patch(route('nomina.procesar', $p2));
        $this->assertSame(5000.0, (float) DB::table('detalle_nomina')->where('id_periodo', $p2)->value('salario_base'));
    }

    public function test_prestaciones_usan_el_salario_de_su_fecha(): void
    {
        $this->aumento(5000, '2026-06-01');
        $this->aumento(6000, '2026-12-01');

        // Bono 14 (jul 2025 – jun 2026): al cierre ya regía el aumento de junio; el de diciembre no.
        $this->post(route('prestaciones.calcular'), ['tipo' => 'BONO14', 'anio' => 2026])->assertSessionHasNoErrors();
        $this->assertSame(5000.0, (float) DB::table('prestacion_laboral')->where(['tipo' => 'BONO14', 'periodo_calculo' => '2026'])->value('monto_base'));

        // Liquidación con salida antes del aumento: base 4,000 aunque el aumento se registró antes.
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $this->ana, 'fecha_salida' => '2026-03-31'])->assertSessionHasNoErrors();
        $this->assertSame(4000.0, (float) DB::table('prestacion_laboral')->where('periodo_calculo', 'LIQ-20260331')->value('monto_base'));
        // Y la vista de vacaciones de hoy usa el salario vigente hoy (5,000), no el de diciembre.
        $this->assertSame(round(5000 / 30, 2), Prestaciones::saldoVacaciones(Empleado::query()->find($this->ana))['salario_diario']);
    }
}
