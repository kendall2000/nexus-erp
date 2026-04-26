<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class AuditoriaAcceso extends Model
{
    protected $table      = 'auditoria_acceso';
    protected $primaryKey = 'id_auditoria';
    public $timestamps    = false;

    // Solo INSERT — nunca se actualiza ni elimina
    protected $fillable = [
        'id_usuario',
        'username_intento',
        'accion',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    // Sin FK obligatoria — puede ser null si el usuario no existe
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeLogins($query)
    {
        return $query->where('accion', 'LOGIN_OK');
    }

    public function scopeFallidos($query)
    {
        return $query->where('accion', 'LOGIN_FAIL');
    }

    public function scopePorUsuario($query, $idUsuario)
    {
        return $query->where('id_usuario', $idUsuario);
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public static function registrar(
        string $accion,
        string $username,
        string $ip,
        ?int $idUsuario = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'id_usuario'      => $idUsuario,
            'username_intento'=> $username,
            'accion'          => $accion,
            'ip_address'      => $ip,
            'user_agent'      => $userAgent,
        ]);
    }
}