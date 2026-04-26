<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table      = 'permiso';
    protected $primaryKey = 'id_permiso';
    public $timestamps    = false;

    protected $fillable = [
        'id_modulo',
        'codigo',
        'descripcion',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function modulo()
    {
        return $this->belongsTo(ModuloSistema::class, 'id_modulo');
    }

    public function roles()
    {
        return $this->belongsToMany(
            Rol::class,
            'rol_permiso',
            'id_permiso',
            'id_rol'
        )->withPivot(
            'puede_crear',
            'puede_leer',
            'puede_editar',
            'puede_eliminar',
            'puede_exportar'
        );
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopePorModulo($query, $idModulo)
    {
        return $query->where('id_modulo', $idModulo);
    }

    public function scopePorCodigo($query, string $codigo)
    {
        return $query->where('codigo', $codigo);
    }
}