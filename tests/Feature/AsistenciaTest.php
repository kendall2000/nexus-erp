<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 7g: Asistencia diaria, solicitudes de ausencia y su efecto en la nómina. */
class AsistenciaTest extends TestCase
{
    use EsquemaNexus;

    private int $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        $this->ana = DB::table('empleado')->insertGetId(['id_empresa' => 1, 'primer_nombre' => 'Ana', 'primer_apellido' => 'López', 'estado' => 'ACTIVO', 'fecha_ingreso' => '2025-01-01']);
    }

    private function registro(string $fecha): ?object
    {
        return DB::table('asistencia')->where('id_empleado', $this->ana)->whereDate('fecha', $fecha)->first();
    }

    public function test_registro_del_dia_calcula_tardanza_y_no_duplica(): void
    {
        $this->actingAs($this->crearUsuario());

        $this->post(route('asistencia.guardar'), ['fecha' => '2026-09-29', 'asistencia' => [$this->ana => ['estado' => 'PRESENTE', 'entrada' => '08:25', 'salida' => '17:00', 'horas_extra' => 1.5]]])
            ->assertSessionHasNoErrors();
        $r = $this->registro('2026-09-29');
        $this->assertSame(['TARDE', 25, 1.5], [$r->estado, (int) $r->minutos_tarde, (float) $r->horas_extra]);

        // Volver a guardar el mismo día actualiza el registro.
        $this->post(route('asistencia.guardar'), ['fecha' => '2026-09-29', 'asistencia' => [$this->ana => ['estado' => 'TARDE', 'entrada' => '07:55']]]);
        $this->assertSame(1, DB::table('asistencia')->count());
        $this->assertSame(['PRESENTE', 0], [$this->registro('2026-09-29')->estado, (int) $this->registro('2026-09-29')->minutos_tarde]);

        $this->post(route('asistencia.guardar'), ['fecha' => now()->addDay()->toDateString(), 'asistencia' => [$this->ana => ['estado' => 'PRESENTE']]])->assertSessionHasErrors('fecha');
        // Empleados de otra empresa se ignoran.
        $ajeno = DB::table('empleado')->insertGetId(['id_empresa' => 2, 'primer_nombre' => 'X', 'estado' => 'ACTIVO', 'fecha_ingreso' => '2025-01-01']);
        $this->post(route('asistencia.guardar'), ['fecha' => '2026-09-28', 'asistencia' => [$ajeno => ['estado' => 'PRESENTE']]]);
        $this->assertSame(0, DB::table('asistencia')->where('id_empleado', $ajeno)->count());
    }

    public function test_solicitud_aprobada_marca_los_dias_habiles(): void
    {
        $this->actingAs($this->crearUsuario());

        // Del viernes 2 al martes 6 de octubre: 3 días hábiles.
        $this->post(route('asistencia.solicitudes.store'), ['id_empleado' => $this->ana, 'tipo' => 'VACACIONES', 'fecha_inicio' => '2026-10-02', 'fecha_fin' => '2026-10-06'])
            ->assertSessionHasNoErrors();
        $s = DB::table('solicitud_ausencia')->sole();
        $this->assertSame([3, 'PENDIENTE'], [(int) $s->dias_habiles, $s->estado]);
        $this->post(route('asistencia.solicitudes.store'), ['id_empleado' => $this->ana, 'tipo' => 'PERMISO_CON_GOCE', 'fecha_inicio' => '2026-10-05', 'fecha_fin' => '2026-10-05'])
            ->assertSessionHasErrorsIn('solicitud', 'fecha_inicio'); // se cruza

        $this->patch(route('asistencia.solicitudes.resolver', [$s->id_solicitud, 'aprobar']))->assertSessionHasNoErrors();
        $this->assertSame(['VACACIONES', 'VACACIONES', 'VACACIONES'], DB::table('asistencia')->orderBy('fecha')->pluck('estado')->all());
        $this->assertNull($this->registro('2026-10-03')); // sábado
        $this->patch(route('asistencia.solicitudes.resolver', [$s->id_solicitud, 'rechazar']), ['observaciones' => 'Tarde'])->assertSessionHasErrors('solicitud');
    }

    public function test_rechazo_pide_motivo(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->post(route('asistencia.solicitudes.store'), ['id_empleado' => $this->ana, 'tipo' => 'PERMISO_SIN_GOCE', 'fecha_inicio' => '2026-10-07', 'fecha_fin' => '2026-10-07']);
        $id = DB::table('solicitud_ausencia')->value('id_solicitud');

        $this->patch(route('asistencia.solicitudes.resolver', [$id, 'rechazar']))->assertSessionHasErrors('observaciones');
        $this->patch(route('asistencia.solicitudes.resolver', [$id, 'rechazar']), ['observaciones' => 'Temporada alta'])->assertSessionHasNoErrors();
        $this->assertSame(['RECHAZADO', 0], [DB::table('solicitud_ausencia')->value('estado'), DB::table('asistencia')->count()]);
    }

    public function test_nomina_descuenta_ausencias_y_trae_horas_extra(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-10-20 10:00'); // las fechas de asistencia no pueden ser futuras
        $this->actingAs($this->crearUsuario());
        DB::table('contrato_laboral')->insert(['id_empleado' => $this->ana, 'id_empresa' => 1, 'numero_contrato' => 'CL-1', 'tipo' => 'INDEFINIDO',
            'fecha_inicio' => '2025-01-01', 'salario_base' => 4000, 'estado' => 'VIGENTE']);
        $this->post(route('asistencia.guardar'), ['fecha' => '2026-10-01', 'asistencia' => [$this->ana => ['estado' => 'AUSENTE']]]);
        $this->post(route('asistencia.guardar'), ['fecha' => '2026-10-02', 'asistencia' => [$this->ana => ['estado' => 'PRESENTE', 'entrada' => '08:00', 'horas_extra' => 2]]]);
        $this->post(route('asistencia.solicitudes.store'), ['id_empleado' => $this->ana, 'tipo' => 'PERMISO_SIN_GOCE', 'fecha_inicio' => '2026-10-05', 'fecha_fin' => '2026-10-06']);
        $this->patch(route('asistencia.solicitudes.resolver', [DB::table('solicitud_ausencia')->value('id_solicitud'), 'aprobar']));

        $this->post(route('nomina.store'), ['tipo' => 'QUINCENAL', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-15', 'fecha_pago' => '2026-10-15', 'moneda' => 'GTQ']);
        $this->patch(route('nomina.procesar', DB::table('periodo_nomina')->value('id_periodo')))->assertSessionHasNoErrors();
        $d = DB::table('detalle_nomina')->sole();
        $this->assertSame([12.0, 2.0], [(float) $d->dias_trabajados, (float) $d->horas_extra]); // 15 − 1 ausencia − 2 sin goce
        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_pantalla_y_permisos(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->get(route('asistencia.index', ['fecha' => '2026-09-29']))->assertOk()->assertSee('Ana López')->assertSee('Martes 29/09/2026', false);

        $jefe = $this->crearUsuario(['username' => 'j', 'email' => 'j@nexus.test'], 'Jefatura');
        $this->darPermisos($jefe, ['asistencia.ver', 'asistencia.editar']);
        $this->actingAs($jefe)->post(route('asistencia.solicitudes.store'), ['id_empleado' => $this->ana, 'tipo' => 'VACACIONES', 'fecha_inicio' => '2026-10-07', 'fecha_fin' => '2026-10-08'])
            ->assertSessionHasNoErrors();
        $this->actingAs($jefe)->patch(route('asistencia.solicitudes.resolver', [DB::table('solicitud_ausencia')->value('id_solicitud'), 'aprobar']))->assertForbidden();

        $lector = $this->crearUsuario(['username' => 'l', 'email' => 'l@nexus.test'], 'Lector');
        $this->darPermisos($lector, ['asistencia.ver']);
        $this->actingAs($lector)->post(route('asistencia.guardar'), ['fecha' => '2026-09-29', 'asistencia' => [$this->ana => ['estado' => 'PRESENTE']]])->assertForbidden();
    }
}
