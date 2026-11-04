<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Recibe los avisos del navegador cuando algo no cumple la CSP (App\Support\PoliticaContenido)
 * y los anota en storage/logs/csp.log: cada problema una sola vez al día y como mucho
 * MAXIMO_DIARIO avisos distintos, para que nadie llene el disco mandando reportes.
 */
class ReporteCspController extends Controller
{
    public const MAXIMO_DIARIO = 300;

    private const TAMANO_MAXIMO = 16384;

    public function guardar(Request $request): Response
    {
        $contenido = (string) $request->getContent();
        if ($contenido === '' || strlen($contenido) > self::TAMANO_MAXIMO) {
            return response()->noContent();
        }
        $json = json_decode($contenido, true);
        if (! is_array($json)) {
            return response()->noContent();
        }

        foreach ($this->avisos($json) as $aviso) {
            $this->anotar($aviso);
        }

        return response()->noContent();
    }

    /**
     * Formato «report-uri» ({"csp-report": {...}}) o «report-to» ([{"type": "csp-violation", "body": {...}}]).
     *
     * @return list<array{directiva: string, bloqueado: string, pagina: string, fuente: string, linea: int}>
     */
    private function avisos(array $json): array
    {
        $crudos = isset($json['csp-report']) && is_array($json['csp-report'])
            ? [[
                'directiva' => $json['csp-report']['effective-directive'] ?? $json['csp-report']['violated-directive'] ?? '',
                'bloqueado' => $json['csp-report']['blocked-uri'] ?? '',
                'pagina' => $json['csp-report']['document-uri'] ?? '',
                'fuente' => $json['csp-report']['source-file'] ?? '',
                'linea' => $json['csp-report']['line-number'] ?? 0,
            ]]
            : collect(array_is_list($json) ? $json : [])
                ->filter(fn ($r) => is_array($r) && ($r['type'] ?? '') === 'csp-violation' && is_array($r['body'] ?? null))
                ->map(fn ($r) => [
                    'directiva' => $r['body']['effectiveDirective'] ?? '',
                    'bloqueado' => $r['body']['blockedURL'] ?? '',
                    'pagina' => $r['body']['documentURL'] ?? $r['url'] ?? '',
                    'fuente' => $r['body']['sourceFile'] ?? '',
                    'linea' => $r['body']['lineNumber'] ?? 0,
                ])->take(20)->all();

        return array_values(array_map(fn ($a) => [
            'directiva' => $this->texto($a['directiva'], 60),
            'bloqueado' => $this->sinConsulta($a['bloqueado']),
            'pagina' => $this->sinConsulta($a['pagina']),
            'fuente' => $this->sinConsulta($a['fuente']),
            'linea' => (int) $a['linea'],
        ], array_filter($crudos, fn ($a) => is_string($a['directiva']) && $a['directiva'] !== '')));
    }

    private function anotar(array $aviso): void
    {
        $hoy = now()->format('Ymd');
        $llave = 'csp:'.$hoy.':'.md5($aviso['directiva'].'|'.$aviso['bloqueado'].'|'.$aviso['pagina'].'|'.$aviso['fuente'].'|'.$aviso['linea']);
        if (! Cache::add($llave, 1, now()->endOfDay())) {
            return; // ya anotado hoy
        }
        Cache::add("csp:{$hoy}:total", 0, now()->endOfDay());
        if (Cache::increment("csp:{$hoy}:total") > self::MAXIMO_DIARIO) {
            return;
        }

        Log::channel('csp')->warning('CSP: '.$aviso['directiva'].' bloquearía '.($aviso['bloqueado'] ?: '(sin origen)'), $aviso);
    }

    /** Sin la parte «?…» ni «#…» (puede llevar datos) y recortado. */
    private function sinConsulta(mixed $valor): string
    {
        $valor = is_string($valor) ? $valor : '';

        return $this->texto(preg_replace('/[?#].*$/s', '', $valor) ?? '', 300);
    }

    private function texto(mixed $valor, int $largo): string
    {
        return is_string($valor) ? mb_substr(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $valor) ?? '', 0, $largo) : '';
    }
}
