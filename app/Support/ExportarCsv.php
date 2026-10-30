<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga CSV que Excel abre bien en español: UTF-8 con BOM y «;» como separador.
 * Los valores que empiezan con = + - @ se anteponen con «'» para que Excel no los
 * ejecute como fórmulas (inyección CSV).
 */
class ExportarCsv
{
    /**
     * @param  list<string>  $encabezados
     * @param  iterable<array<int, mixed>>  $filas
     */
    public static function descargar(string $nombre, array $encabezados, iterable $filas): StreamedResponse
    {
        $archivo = $nombre.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($encabezados, $filas) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, $encabezados, ';');
            foreach ($filas as $fila) {
                fputcsv($salida, array_map([self::class, 'celda'], $fila), ';');
            }
            fclose($salida);
        }, $archivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private static function celda(mixed $valor): string
    {
        $texto = is_bool($valor) ? ($valor ? 'Sí' : 'No') : (string) $valor;

        return preg_match('/^[=+\-@\t\r]/', $texto) ? "'".$texto : $texto;
    }
}
