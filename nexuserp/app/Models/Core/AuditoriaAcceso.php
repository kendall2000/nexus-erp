<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class AuditoriaAcceso extends Model
{
    protected $table = 'auditoria_acceso';

    protected $primaryKey = 'id_auditoria';

    public $timestamps = false;

    /** Acciones del enum con su nombre para mostrar. */
    public const EVENTOS = [
        'LOGIN_OK' => 'Ingreso',
        'LOGIN_FAIL' => 'Fallido',
        'LOGIN_FAIL_2FA' => 'Fallido (2 pasos)',
        'BLOQUEO' => 'Bloqueo',
        'DESACTIVADO' => 'Desactivado',
        'LOGOUT' => 'Salida',
        'SESION_CERRADA' => 'Sesiones cerradas',
        'CAMBIO_PASSWORD' => 'Cambio de contraseña',
        'RESET_PASSWORD' => 'Recuperó contraseña',
        'DESBLOQUEO' => 'Desbloqueo',
    ];

    // Solo INSERT — nunca se actualiza ni elimina
    protected $fillable = [
        'id_usuario',
        'username_intento',
        'accion',
        'ip_address',
        'user_agent',
        'detalle',
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

    /** Color de la etiqueta en el historial (badge-phoenix-*). */
    public function color(): string
    {
        return match ($this->accion) {
            'LOGIN_OK' => 'success',
            'LOGIN_FAIL', 'LOGIN_FAIL_2FA' => 'warning',
            'BLOQUEO', 'DESACTIVADO' => 'danger',
            'CAMBIO_PASSWORD', 'RESET_PASSWORD', 'SESION_CERRADA', 'DESBLOQUEO' => 'info',
            default => 'secondary',
        };
    }

    public static function registrar(
        string $accion,
        string $username,
        string $ip,
        ?int $idUsuario = null,
        ?string $userAgent = null,
        ?string $detalle = null
    ): self {
        return self::create([
            'id_usuario' => $idUsuario,
            'username_intento' => $username,
            'accion' => $accion,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'detalle' => $detalle,
        ]);
    }
}
