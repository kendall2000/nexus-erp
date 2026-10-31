<?php

namespace App\Models\Concerns;

use App\Models\Core\Empresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Trait multi-empresa para modelos de negocio en Nexus ERP.
 *
 * Centraliza la relación con Empresa, el scope `deEmpresa()` / `deMiEmpresa()`
 * y la asignación automática de `id_empresa`, `created_by` y `updated_by`
 * cuando no han sido provistos explícitamente.
 */
trait PerteneceAEmpresa
{
    public static function bootPerteneceAEmpresa(): void
    {
        static::creating(function ($model) {
            $user = Auth::user();
            if ($user) {
                if (empty($model->id_empresa) && in_array('id_empresa', $model->getFillable(), true)) {
                    $model->id_empresa = $user->id_empresa;
                }
                if (empty($model->created_by) && in_array('created_by', $model->getFillable(), true)) {
                    $model->created_by = $user->id_usuario;
                }
                if (empty($model->updated_by) && in_array('updated_by', $model->getFillable(), true)) {
                    $model->updated_by = $user->id_usuario;
                }
            }
        });

        static::updating(function ($model) {
            $user = Auth::user();
            if ($user && in_array('updated_by', $model->getFillable(), true) && !$model->isDirty('updated_by')) {
                $model->updated_by = $user->id_usuario;
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function scopeDeEmpresa(Builder $query, ?int $idEmpresa = null): Builder
    {
        $idEmpresa ??= Auth::user()?->id_empresa;

        return $query->where($this->qualifyColumn('id_empresa'), $idEmpresa);
    }

    public function scopeDeMiEmpresa(Builder $query): Builder
    {
        return $this->scopeDeEmpresa($query, Auth::user()?->id_empresa);
    }
}
