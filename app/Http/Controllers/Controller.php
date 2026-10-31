<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Devuelve el ID de la empresa del usuario autenticado.
     */
    protected function idEmpresa(?Request $request = null): int
    {
        $user = $request ? $request->user() : auth()->user();

        return (int) $user->id_empresa;
    }

    /**
     * Devuelve el ID del usuario autenticado.
     */
    protected function idUsuario(?Request $request = null): int
    {
        $user = $request ? $request->user() : auth()->user();

        return (int) $user->id_usuario;
    }

    /**
     * Inicia una consulta Eloquent filtrada por la empresa del usuario autenticado.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param  class-string<TModel>  $modelClass
     * @return Builder<TModel>
     */
    protected function consultaEmpresa(string $modelClass, ?Request $request = null): Builder
    {
        return $modelClass::query()->where('id_empresa', $this->idEmpresa($request));
    }

    /**
     * Atributos de auditoría al crear un registro de empresa.
     *
     * @return array{id_empresa: int, created_by: int, updated_by: int}
     */
    protected function selloCreacion(?Request $request = null): array
    {
        $idUsuario = $this->idUsuario($request);

        return [
            'id_empresa' => $this->idEmpresa($request),
            'created_by' => $idUsuario,
            'updated_by' => $idUsuario,
        ];
    }

    /**
     * Atributos de auditoría al actualizar un registro.
     *
     * @return array{updated_by: int}
     */
    protected function selloActualizacion(?Request $request = null): array
    {
        return [
            'updated_by' => $this->idUsuario($request),
        ];
    }
}

