<?php
// app/Models/Scopes/EmpresaScope.php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Aplica WHERE id_empresa = X automáticamente en TODOS los SELECT
 * de los modelos que usen el trait PerteneceAEmpresa.
 *
 * Solo aplica si hay un usuario autenticado con id_empresa.
 * Para queries cross-empresa (super-admin, reportes globales),
 * usar withoutGlobalScope(EmpresaScope::class).
 */
class EmpresaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Solo aplica si hay sesión activa de Sanctum
        if (Auth::check() && Auth::user()->id_empresa) {
            $builder->where(
                $model->getTable() . '.id_empresa',
                Auth::user()->id_empresa
            );
        }
    }
}