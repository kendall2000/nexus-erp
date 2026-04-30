<?php
// app/Models/Concerns/PerteneceAEmpresa.php

namespace App\Models\Concerns;

use App\Models\Scopes\EmpresaScope;
use Illuminate\Support\Facades\Auth;

/**
 * Trait que cualquier modelo multi-tenant DEBE usar.
 *
 * Hace 2 cosas automáticamente:
 *   1. Aplica EmpresaScope global → filtra SELECTs por id_empresa
 *   2. Asigna id_empresa al crear (INSERT) si no viene seteado
 *
 * Métodos auxiliares:
 *   - scopeSinFiltroEmpresa(): para queries cross-empresa (admin global)
 */
trait PerteneceAEmpresa
{
    /**
     * Boot del trait — Laravel lo invoca automáticamente
     * al inicializar cualquier modelo que lo use.
     */
    protected static function bootPerteneceAEmpresa(): void
    {
        // 1. Filtro automático en SELECTs
        static::addGlobalScope(new EmpresaScope);

        // 2. Auto-asignación al crear
        static::creating(function ($model) {
            if (empty($model->id_empresa) && Auth::check()) {
                $model->id_empresa = Auth::user()->id_empresa;
            }
        });
    }

    /**
     * Helper para queries que necesitan saltarse el scope.
     * Uso: Producto::sinFiltroEmpresa()->get();
     */
    public function scopeSinFiltroEmpresa($query)
    {
        return $query->withoutGlobalScope(EmpresaScope::class);
    }
}