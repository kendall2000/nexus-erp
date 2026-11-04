<?php

namespace App\Support;

use App\Models\Core\Sucursal;
use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rules\Exists;

/**
 * Sucursal o agencia de los documentos (facturas, órdenes de compra, tickets): validación,
 * la que se propone al crear y el filtro de los listados.
 */
class Sedes
{
    /** Valor del filtro para los documentos sin sucursal (anteriores a que se registrara). */
    public const SIN_SEDE = 'sin';

    /** Sede activa de la empresa; la que el documento ya tenía vale aunque se haya desactivado después. */
    public static function regla(int $idEmpresa, ?int $actual = null): Exists
    {
        return (new Exists('sucursal', 'id_sucursal'))->where('id_empresa', $idEmpresa)
            ->where(fn (Builder $q) => $q->where('activo', true)->when($actual, fn ($q) => $q->orWhere('id_sucursal', $actual)));
    }

    /** La sede del usuario si es de esta empresa y está activa; si no, la casa matriz. */
    public static function predeterminada(Usuario $usuario): ?int
    {
        $idEmpresa = (int) $usuario->id_empresa;
        $suya = $usuario->id_sucursal
            ? Sucursal::query()->where('id_empresa', $idEmpresa)->where('activo', true)->whereKey($usuario->id_sucursal)->value('id_sucursal')
            : null;

        return $suya ?? Sucursal::query()->where('id_empresa', $idEmpresa)->where('activo', true)->where('es_casa_matriz', true)->value('id_sucursal');
    }

    /** Filtra por ?sucursal= (un id o «sin»). */
    public static function filtrar(EloquentBuilder|Builder $consulta, mixed $valor, string $columna = 'id_sucursal'): EloquentBuilder|Builder
    {
        return match (true) {
            $valor === self::SIN_SEDE => $consulta->whereNull($columna),
            is_numeric($valor) && (int) $valor > 0 => $consulta->where($columna, (int) $valor),
            default => $consulta,
        };
    }
}
