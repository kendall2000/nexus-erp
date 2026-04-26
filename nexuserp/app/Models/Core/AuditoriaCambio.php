<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class AuditoriaCambio extends Model
{
    protected $table      = 'auditoria_cambio';
    protected $primaryKey = 'id_cambio';
    public $timestamps    = false;

    // Solo INSERT — registro inmutable
    protected $fillable = [
        'id_usuario',
        'id_empresa',
        'tabla_afectada',
        'id_registro',
        'accion',
        'datos_anteriores',
        'datos_nuevos',
        'ip_address',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',  // JSON → array PHP automáticamente
        'datos_nuevos'     => 'array',
        'created_at'       => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopePorTabla($query, string $tabla)
    {
        return $query->where('tabla_afectada', $tabla);
    }

    public function scopePorRegistro($query, string $tabla, string $id)
    {
        return $query->where('tabla_afectada', $tabla)
                     ->where('id_registro', $id);
    }

    public function scopePorUsuario($query, $idUsuario)
    {
        return $query->where('id_usuario', $idUsuario);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public static function registrar(
        string $tabla,
        string $idRegistro,
        string $accion,
        ?array $anterior = null,
        ?array $nuevo = null,
        ?int $idUsuario = null,
        ?int $idEmpresa = null,
        ?string $ip = null
    ): self {
        return self::create([
            'id_usuario'       => $idUsuario,
            'id_empresa'       => $idEmpresa,
            'tabla_afectada'   => $tabla,
            'id_registro'      => $idRegistro,
            'accion'           => $accion,
            'datos_anteriores' => $anterior,
            'datos_nuevos'     => $nuevo,
            'ip_address'       => $ip,
        ]);
    }
}