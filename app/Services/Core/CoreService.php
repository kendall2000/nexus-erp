<?php

namespace App\Services\Core;

use App\Support\Bitacora;
use App\Support\ExportarCsv;
use App\Support\MatrizPermisos;
use App\Support\MenuLateral;
use App\Support\Referencias;
use App\Support\Seguridad;
use App\Support\Sistema;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Servicio de núcleo (Core): Auditoría, Seguridad, Menú, Permisos y Exportación.
 */
class CoreService
{
    public function registrarBitacora(string $tabla, string $idRegistro, string $accion, ?array $antes, ?array $despues, mixed $idEmpresa = null): void
    {
        Bitacora::registrar($tabla, $idRegistro, $accion, $antes, $despues, $idEmpresa);
    }

    public function verificarReferenciasEnUso(string $columna, int $id, array $tablas): ?string
    {
        return Referencias::enUso($columna, $id, $tablas);
    }

    public function descargarCsv(string $nombre, array $encabezados, iterable $filas): StreamedResponse
    {
        return ExportarCsv::descargar($nombre, $encabezados, $filas);
    }

    public function configSistema(): object
    {
        return Sistema::config();
    }

    public function menuParaUsuario(?\App\Models\Core\Usuario $usuario): array
    {
        return MenuLateral::para($usuario);
    }

    public function limpiarCacheMenu(): void
    {
        MenuLateral::olvidar();
    }

    public function ModulosYPermisos(): \Illuminate\Support\Collection
    {
        return MatrizPermisos::modulos();
    }

    public function parametrosSeguridad(): object
    {
        return Seguridad::config();
    }
}
