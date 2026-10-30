<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Permiso = módulo × acción. Código «modulo.accion», p. ej. «bodegas.crear». */
class Permiso extends Model
{
    protected $table = 'permiso';

    protected $primaryKey = 'id_permiso';

    public $timestamps = false;

    protected $fillable = ['id_modulo', 'id_accion', 'descripcion'];

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'id_modulo');
    }

    public function accion(): BelongsTo
    {
        return $this->belongsTo(Accion::class, 'id_accion');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'id_permiso', 'id_rol')->withPivot('asignado_at');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'usuario_permiso', 'id_permiso', 'id_usuario')->withPivot('asignado_por', 'asignado_at');
    }

    /** Código «modulo.accion» (requiere cargar modulo y accion). */
    public function getCodigoAttribute(): string
    {
        return $this->modulo?->codigo.'.'.$this->accion?->codigo;
    }

    /** Filtra por código «modulo.accion» (la acción es lo que va después del último punto). */
    public function scopeCodigo(Builder $query, string $codigo): Builder
    {
        $punto = strrpos($codigo, '.');
        if ($punto === false) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('modulo', fn ($q) => $q->where('codigo', substr($codigo, 0, $punto)))
            ->whereHas('accion', fn ($q) => $q->where('codigo', substr($codigo, $punto + 1)));
    }
}
