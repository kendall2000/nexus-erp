<?php

namespace Tests\Feature;

use App\Support\PoliticaContenido;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** CSP fase 1: encabezado según CSP_MODO y avisos del navegador en storage/logs/csp.log. */
class PoliticaContenidoTest extends TestCase
{
    use EsquemaNexus;

    public function test_por_defecto_solo_reporta_con_la_politica_del_sistema(): void
    {
        config(['filesystems.disks.contabo.url' => 'https://usc1.contabostorage.com/abc:nexus']);
        $r = $this->actingAs($this->crearUsuario())->get(route('dashboard'))->assertOk()
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeader('Reporting-Endpoints', 'csp="'.url('/csp-reporte').'"');

        $csp = $r->headers->get('Content-Security-Policy-Report-Only');
        foreach (["default-src 'self'", "object-src 'none'", "base-uri 'self'", "form-action 'self'", "frame-ancestors 'self'", "connect-src 'self'",
            "img-src 'self' data: blob: https://usc1.contabostorage.com", 'report-uri /csp-reporte', 'report-to csp'] as $directiva) {
            $this->assertStringContainsString($directiva, $csp);
        }
    }

    public function test_aplicar_y_apagado(): void
    {
        config(['csp.modo' => 'aplicar']);
        $this->get(route('login'))->assertHeader('Content-Security-Policy', PoliticaContenido::politica())->assertHeaderMissing('Content-Security-Policy-Report-Only');

        config(['csp.modo' => 'apagado']);
        $this->get(route('login'))->assertHeaderMissing('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');

        config(['csp.modo' => 'cualquier-cosa']);
        $this->get(route('login'))->assertHeader('Content-Security-Policy-Report-Only');
    }

    public function test_anota_cada_aviso_una_vez_al_dia_y_sin_datos_de_la_url(): void
    {
        Cache::flush();
        $log = Log::spy();
        $log->shouldReceive('channel')->andReturnSelf();
        $aviso = ['csp-report' => ['document-uri' => 'https://nexus.test/sistema/clientes?buscar=secreto', 'effective-directive' => 'script-src-elem',
            'blocked-uri' => 'https://malo.test/x.js?token=123', 'source-file' => 'https://nexus.test/sistema/clientes', 'line-number' => 12]];

        // Sin sesión y sin token CSRF (el navegador no lo manda).
        $this->call('POST', '/csp-reporte', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode($aviso))->assertNoContent();
        $this->call('POST', '/csp-reporte', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode($aviso))->assertNoContent();
        // Formato nuevo (report-to).
        $nuevo = [['type' => 'csp-violation', 'url' => 'https://nexus.test/sistema/facturas', 'body' => ['documentURL' => 'https://nexus.test/sistema/facturas', 'effectiveDirective' => 'img-src', 'blockedURL' => 'https://otro.test/a.png']]];
        $this->call('POST', '/csp-reporte', [], [], [], ['CONTENT_TYPE' => 'application/reports+json'], json_encode($nuevo))->assertNoContent();
        // Basura: se ignora.
        $this->call('POST', '/csp-reporte', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'no es json')->assertNoContent();
        $this->call('POST', '/csp-reporte', [], [], [], ['CONTENT_TYPE' => 'application/json'], str_repeat('x', 20000))->assertNoContent();

        $log->shouldHaveReceived('channel')->with('csp')->twice();
        $log->shouldHaveReceived('warning')->with('CSP: script-src-elem bloquearía https://malo.test/x.js', \Mockery::on(fn ($d) => $d['pagina'] === 'https://nexus.test/sistema/clientes' && $d['linea'] === 12))->once();
        $log->shouldHaveReceived('warning')->with('CSP: img-src bloquearía https://otro.test/a.png', \Mockery::any())->once();
    }
}
