<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Prestaciones laborales (aguinaldo, bono 14, liquidación) y documentos del expediente del empleado. */
class PrestacionesTest extends TestCase
{
    use EsquemaNexus;

    private int $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        Carbon::setTestNow('2026-12-05 10:00');
        $this->ana = $this->empleado('Ana', 'López', 4000);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function empleado(string $nombre, string $apellido, ?float $salario, string $ingreso = '2025-01-01', array $datos = []): int
    {
        $id = DB::table('empleado')->insertGetId($datos + ['id_empresa' => 1, 'primer_nombre' => $nombre, 'primer_apellido' => $apellido, 'estado' => 'ACTIVO', 'fecha_ingreso' => $ingreso]);
        if ($salario) {
            DB::table('contrato_laboral')->insert(['id_empleado' => $id, 'id_empresa' => 1, 'numero_contrato' => 'CL-'.$id, 'tipo' => 'INDEFINIDO',
                'fecha_inicio' => $ingreso, 'salario_base' => $salario, 'estado' => 'VIGENTE']);
        }

        return $id;
    }

    private function prestacion(int $empleado, string $tipo): ?object
    {
        return DB::table('prestacion_laboral')->where('id_empleado', $empleado)->where('tipo', $tipo)->first();
    }

    public function test_aguinaldo_y_bono14_proporcionales_al_tiempo_laborado(): void
    {
        $this->actingAs($this->crearUsuario());
        $luis = $this->empleado('Luis', 'Pérez', 3650, '2026-06-01');
        $this->empleado('Sin', 'Contrato', null);
        $this->empleado('Rita', 'Rotativa', null, '2025-01-01', ['es_rotativo' => true, 'tarifa_dia' => 120]);
        $this->empleado('De', 'Baja', 3000, '2025-01-01', ['estado' => 'BAJA', 'fecha_baja' => '2026-03-01']);

        $this->post(route('prestaciones.calcular'), ['tipo' => 'AGUINALDO', 'anio' => 2026])->assertSessionHasNoErrors()->assertSessionHas('status', fn ($m) => str_contains($m, 'Sin Contrato') && ! str_contains($m, 'Rita'));
        $this->assertSame(2, DB::table('prestacion_laboral')->count());
        // Ana trabajó el periodo completo (dic 2025 – nov 2026): un salario. Luis entró el 1 de junio: 183 días.
        $this->assertSame([4000.0, 365.0, '2026'], [(float) $this->prestacion($this->ana, 'AGUINALDO')->monto_calculado, (float) $this->prestacion($this->ana, 'AGUINALDO')->dias_calculados, $this->prestacion($this->ana, 'AGUINALDO')->periodo_calculo]);
        $this->assertSame([1830.0, 183.0], [(float) $this->prestacion($luis, 'AGUINALDO')->monto_calculado, (float) $this->prestacion($luis, 'AGUINALDO')->dias_calculados]);

        // Bono 14 (jul 2025 – jun 2026): Luis solo trabajó junio (30 días).
        $this->post(route('prestaciones.calcular'), ['tipo' => 'BONO14', 'anio' => 2026])->assertSessionHasNoErrors();
        $this->assertSame([4000.0, 300.0], [(float) $this->prestacion($this->ana, 'BONO14')->monto_calculado, (float) $this->prestacion($luis, 'BONO14')->monto_calculado]);

        $this->post(route('prestaciones.calcular'), ['tipo' => 'INDEMNIZACION', 'anio' => 2026])->assertSessionHasErrors('tipo');
        $this->get(route('prestaciones.index', ['anio' => 2026]))->assertOk()->assertSee('Aguinaldo 2026')->assertSee('Bono 14 2026')->assertSee('1,830.00');
    }

    public function test_flujo_aprobar_pagar_y_recalculo_conserva_lo_aprobado(): void
    {
        $this->actingAs($this->crearUsuario());
        $luis = $this->empleado('Luis', 'Pérez', 3650, '2026-06-01');
        $this->post(route('prestaciones.calcular'), ['tipo' => 'AGUINALDO', 'anio' => 2026]);
        $idAna = $this->prestacion($this->ana, 'AGUINALDO')->id_prestacion;
        $idLuis = $this->prestacion($luis, 'AGUINALDO')->id_prestacion;

        // No se paga sin aprobar; aprobada ya no se elimina.
        $this->patch(route('prestaciones.pagar', $idAna), ['fecha_pago' => '2026-12-15'])->assertSessionHasErrors('prestacion');
        $this->patch(route('prestaciones.aprobar', $idAna))->assertSessionHasNoErrors();
        $this->delete(route('prestaciones.destroy', $idAna))->assertSessionHasErrors('prestacion');

        // Un aumento y recalcular: cambia la de Luis (calculada), no la de Ana (aprobada).
        DB::table('contrato_laboral')->update(['salario_base' => 7300]);
        $this->post(route('prestaciones.calcular'), ['tipo' => 'AGUINALDO', 'anio' => 2026])->assertSessionHas('status', fn ($m) => str_contains($m, '1 ya aprobados'));
        $this->assertSame([4000.0, 3660.0], [(float) $this->prestacion($this->ana, 'AGUINALDO')->monto_calculado, (float) $this->prestacion($luis, 'AGUINALDO')->monto_calculado]);

        // Por lote: aprobar las calculadas y pagar las aprobadas del grupo.
        $this->patch(route('prestaciones.lote.aprobar'), ['periodo' => '2026', 'tipo' => 'AGUINALDO'])->assertSessionHasNoErrors();
        $this->patch(route('prestaciones.lote.pagar'), ['periodo' => '2026', 'tipo' => 'AGUINALDO', 'fecha_pago' => '2026-12-15'])->assertSessionHasNoErrors();
        $this->assertSame(['PAGADO', 'PAGADO'], DB::table('prestacion_laboral')->orderBy('id_prestacion')->pluck('estado')->all());
        $this->assertSame('2026-12-15', substr(DB::table('prestacion_laboral')->where('id_prestacion', $idLuis)->value('fecha_pago'), 0, 10));
        $this->patch(route('prestaciones.lote.aprobar'), ['periodo' => '2026', 'tipo' => 'AGUINALDO'])->assertSessionHasErrors('prestacion');
    }

    public function test_liquidacion_con_indemnizacion_y_vacaciones(): void
    {
        $this->actingAs($this->crearUsuario());

        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $this->ana, 'fecha_salida' => '2026-10-03', 'dias_vacaciones' => 5, 'indemnizacion' => 1])->assertSessionHasNoErrors();
        $montos = DB::table('prestacion_laboral')->where('periodo_calculo', 'LIQ-20261003')->pluck('monto_calculado', 'tipo')->map(fn ($m) => (float) $m)->all();
        // Aguinaldo: 307 días desde el 1 de diciembre. Bono 14: 95 días desde el 1 de julio. Vacaciones: 4,000 / 30 × 5.
        // Indemnización: 641 días de servicio sobre 4,000 × 14 / 12.
        $this->assertSame(['AGUINALDO' => 3364.38, 'BONO14' => 1041.1, 'INDEMNIZACION' => 8195.43, 'VACACIONES_DINERO' => 666.67], collect($montos)->sortKeys()->all());

        // Volver a liquidar reemplaza la anterior mientras no esté aprobada; aprobada ya no.
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $this->ana, 'fecha_salida' => '2026-10-03'])->assertSessionHasNoErrors();
        $this->assertSame(['AGUINALDO', 'BONO14'], DB::table('prestacion_laboral')->orderBy('tipo')->pluck('tipo')->all());
        $this->get(route('prestaciones.index', ['anio' => 2026]))->assertOk()->assertSee('Liquidación de Ana López');
        $this->patch(route('prestaciones.lote.aprobar'), ['periodo' => 'LIQ-20261003', 'id_empleado' => $this->ana])->assertSessionHasNoErrors();
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $this->ana, 'fecha_salida' => '2026-10-10'])->assertSessionHasErrorsIn('liquidacion', 'id_empleado');

        // Sin contrato no hay salario; la salida no puede ser antes del ingreso.
        $nadie = $this->empleado('Sin', 'Contrato', null);
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $nadie, 'fecha_salida' => '2026-10-03'])->assertSessionHasErrorsIn('liquidacion', 'id_empleado');
        $luis = $this->empleado('Luis', 'Pérez', 3000, '2026-06-01');
        $this->post(route('prestaciones.liquidar'), ['id_empleado' => $luis, 'fecha_salida' => '2026-05-01'])->assertSessionHasErrorsIn('liquidacion', 'fecha_salida');
    }

    public function test_permisos_y_otra_empresa(): void
    {
        $this->actingAs($this->crearUsuario())->post(route('prestaciones.calcular'), ['tipo' => 'AGUINALDO', 'anio' => 2026]);
        $id = $this->prestacion($this->ana, 'AGUINALDO')->id_prestacion;
        $ajena = DB::table('prestacion_laboral')->insertGetId(['id_empleado' => 99, 'id_empresa' => 2, 'tipo' => 'AGUINALDO', 'periodo_calculo' => '2026', 'monto_base' => 1, 'monto_calculado' => 1]);

        $lector = $this->crearUsuario(['username' => 'l', 'email' => 'l@nexus.test'], 'Lector');
        $this->darPermisos($lector, ['prestaciones.ver', 'prestaciones.procesar']);
        $this->actingAs($lector)->get(route('prestaciones.index'))->assertOk();
        $this->actingAs($lector)->patch(route('prestaciones.aprobar', $id))->assertForbidden();
        $this->actingAs($lector)->get(route('prestaciones.exportar'))->assertForbidden();
        $this->actingAs($lector)->delete(route('prestaciones.destroy', $ajena))->assertNotFound();
        $this->actingAs($lector)->delete(route('prestaciones.destroy', $id))->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('prestacion_laboral')->count());
    }

    public function test_documentos_del_expediente(): void
    {
        config(['filesystems.disks.contabo' => array_merge(config('filesystems.disks.contabo'), ['key' => 'k', 'secret' => 's', 'bucket' => 'b', 'url' => 'https://cdn.test/nexus'])]);
        Storage::fake('contabo', ['url' => 'https://cdn.test/nexus']);
        $this->actingAs($this->crearUsuario());

        $this->post(route('empleados.documentos.store', $this->ana), ['tipo_documento' => 'ANTECEDENTES', 'archivo' => UploadedFile::fake()->create('antecedentes penales.pdf', 200, 'application/pdf'),
            'fecha_emision' => '2026-11-20', 'fecha_vencimiento' => '2026-12-20'])->assertSessionHasNoErrors();
        $d = DB::table('empleado_documento')->sole();
        $this->assertSame(['antecedentes penales', 'ANTECEDENTES'], [$d->nombre, $d->tipo_documento]);
        $this->assertStringStartsWith("https://cdn.test/nexus/empleados/{$this->ana}/", $d->url_archivo);
        $ruta = substr($d->url_archivo, strlen('https://cdn.test/nexus/'));
        Storage::disk('contabo')->assertExists($ruta);
        $this->get(route('empleados.show', $this->ana))->assertOk()->assertSee('antecedentes penales')->assertSee('Vence en 15 días');

        // Solo PDF, imagen o Word; el vencimiento no puede ser antes de la emisión.
        $this->post(route('empleados.documentos.store', $this->ana), ['tipo_documento' => 'OTRO', 'archivo' => UploadedFile::fake()->create('virus.exe', 10)])->assertSessionHasErrorsIn('documento', 'archivo');
        $this->post(route('empleados.documentos.store', $this->ana), ['tipo_documento' => 'DPI', 'archivo' => UploadedFile::fake()->image('dpi.jpg'), 'fecha_emision' => '2026-11-20', 'fecha_vencimiento' => '2026-11-01'])
            ->assertSessionHasErrorsIn('documento', 'fecha_vencimiento');

        // El documento es de ese empleado: con otro empleado en la URL no se borra.
        $luis = $this->empleado('Luis', 'Pérez', 3000);
        $this->delete(route('empleados.documentos.destroy', [$luis, $d->id_doc]))->assertNotFound();
        $this->delete(route('empleados.documentos.destroy', [$this->ana, $d->id_doc]))->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('empleado_documento')->count());
        Storage::disk('contabo')->assertMissing($ruta);

        $lector = $this->crearUsuario(['username' => 'l', 'email' => 'l@nexus.test'], 'Lector');
        $this->darPermisos($lector, ['empleados.ver']);
        $this->actingAs($lector)->post(route('empleados.documentos.store', $this->ana), ['tipo_documento' => 'DPI', 'archivo' => UploadedFile::fake()->image('dpi.jpg')])->assertForbidden();
    }
}
