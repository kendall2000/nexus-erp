<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    /** Nombre reservado: tiene todos los permisos (Usuario::esAdministrador). */
    public const ADMINISTRADOR = 'Administrador';

    protected $table      = 'rol';
    protected $primaryKey = 'id_rol';
    public $timestamps    = false;

    protected $fillable = [
        'id_empresa',
        'nombre',
        'descripcion',
        'es_rol_sistema',
        'activo',
        'requiere_2fa',
    ];

    protected $casts = [
        'es_rol_sistema' => 'boolean',
        'activo'         => 'boolean',
        'requiere_2fa'   => 'boolean',
        'created_at'     => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function usuarios()
    {
        return $this->belongsToMany(
            Usuario::class,
            'usuario_rol',
            'id_rol',
            'id_usuario'
        )->withPivot('fecha_asignacion', 'asignado_por');
    }

    public function permisos()
    {
        return $this->belongsToMany(
            Permiso::class,
            'rol_permiso',
            'id_rol',
            'id_permiso'
        )->withPivot(
            'puede_crear',
            'puede_leer',
            'puede_editar',
            'puede_eliminar',
            'puede_exportar'
        );
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function esAdministrador(): bool
    {
        return $this->nombre === self::ADMINISTRADOR;
    }

    /** El Administrador y los roles de sistema no se renombran, desactivan, limitan ni eliminan. */
    public function esProtegido(): bool
    {
        return $this->esAdministrador() || $this->es_rol_sistema;
    }

    public function asignarPermiso(int $idPermiso, array $acciones = []): void
    {
        $this->permisos()->syncWithoutDetaching([
            $idPermiso => array_merge([
                'puede_crear'    => false,
                'puede_leer'     => true,
                'puede_editar'   => false,
                'puede_eliminar' => false,
                'puede_exportar' => false,
            ], $acciones),
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopeSistema($query)
    {
        return $query->where('es_rol_sistema', true);
    }
}