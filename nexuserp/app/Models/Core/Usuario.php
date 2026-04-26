<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $table      = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps    = true;

    // Sanctum y Laravel buscan 'deleted_at' para SoftDeletes
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'id_empresa',
        'id_sucursal',
        'username',
        'email',
        'password_hash',
        'nombre_completo',
        'avatar_url',
        'ultimo_login',
        'intentos_fallidos',
        'bloqueado_hasta',
        'token_reset',
        'token_reset_exp',
        'activo',
    ];

    protected $hidden = [
        'password_hash',
        'token_reset',
        'remember_token',
    ];

    protected $casts = [
        'activo'            => 'boolean',
        'ultimo_login'      => 'datetime',
        'bloqueado_hasta'   => 'datetime',
        'token_reset_exp'   => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
        'intentos_fallidos' => 'integer',
    ];

    // Laravel espera 'password' — mapeamos a password_hash
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal');
    }

    public function roles()
    {
        return $this->belongsToMany(
            Rol::class,
            'usuario_rol',
            'id_usuario',
            'id_rol'
        )->withPivot('fecha_asignacion', 'asignado_por');
    }

    public function auditoriaAccesos()
    {
        return $this->hasMany(AuditoriaAcceso::class, 'id_usuario');
    }

    public function auditoriaCambios()
    {
        return $this->hasMany(AuditoriaCambio::class, 'id_usuario');
    }

    public function notificaciones()
    {
        return $this->hasMany(\App\Models\Core\Notificacion::class, 'id_usuario');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function estaBloqueado(): bool
    {
        return $this->bloqueado_hasta && $this->bloqueado_hasta->isFuture();
    }

    public function tienePermiso(string $codigoPermiso): bool
    {
        return $this->roles()
            ->whereHas('permisos', fn($q) => $q->where('codigo', $codigoPermiso))
            ->exists();
    }

    public function registrarLogin(): void
    {
        $this->update([
            'ultimo_login'      => now(),
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
        ]);
    }

    public function registrarLoginFallido(): void
    {
        $intentos = $this->intentos_fallidos + 1;
        $this->update([
            'intentos_fallidos' => $intentos,
            'bloqueado_hasta'   => $intentos >= 5 ? now()->addMinutes(30) : null,
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
}