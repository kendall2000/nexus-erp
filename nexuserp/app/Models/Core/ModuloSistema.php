<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class ModuloSistema extends Model
{
    protected $table      = 'modulo_sistema';
    protected $primaryKey = 'id_modulo';
    public $timestamps    = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'icono',
        'ruta_base',
        'orden_menu',
        'activo',
    ];

    protected $casts = [
        'activo'     => 'boolean',
        'orden_menu' => 'integer',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'id_modulo');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden_menu');
    }
}