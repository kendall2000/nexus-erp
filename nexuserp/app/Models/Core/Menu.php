<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table      = 'menu';
    protected $primaryKey = 'id_menu';
    public $timestamps    = false;

    protected $fillable = [
        'id_empresa',
        'id_padre',
        'nombre',
        'icono',
        'ruta',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden'  => 'integer',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    // Grupo padre
    public function padre()
    {
        return $this->belongsTo(Menu::class, 'id_padre');
    }

    // Ítems hijos
    public function hijos()
    {
        return $this->hasMany(Menu::class, 'id_padre')
                    ->where('activo', true)
                    ->orderBy('orden');
    }

    // Roles que tienen acceso a este ítem
    public function roles()
    {
        return $this->belongsToMany(
            Rol::class,
            'menu_rol',
            'id_menu',
            'id_rol'
        );
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    // Solo grupos principales (sin padre)
    public function scopeGrupos($query, $idEmpresa)
    {
        return $query->whereNull('id_padre')
                     ->where('id_empresa', $idEmpresa)
                     ->where('activo', true)
                     ->orderBy('orden');
    }
}