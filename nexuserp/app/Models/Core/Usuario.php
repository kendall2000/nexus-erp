<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Support\MatrizPermisos;
use App\Support\Sistema;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\TwoFactorAuthenticatable;

class Usuario extends Authenticatable
{
    use Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /** @var list<string>|null Códigos de permiso calculados (ver codigosPermiso). */
    private ?array $codigosPermiso = null;

    /** @var list<int>|null Ids de permiso propios (ver idsPermisoPropios). */
    private ?array $idsPermiso = null;

    private ?bool $esAdmin = null;

    protected $table      = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps    = true;

    // SoftDeletes usa 'deleted_at'
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
        'two_factor_secret',
        'two_factor_recovery_codes',
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
        'two_factor_confirmed_at' => 'datetime',
        'password_cambiado_at' => 'datetime',
        'debe_cambiar_password' => 'boolean',
    ];

    // Laravel espera 'password' — mapeamos a password_hash (lectura y rehash)
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /** El QR de 2 pasos muestra el nombre del sistema y el correo (o usuario) de la cuenta. */
    public function twoFactorQrCodeUrl()
    {
        return app(TwoFactorAuthenticationProvider::class)->qrCodeUrl(
            Sistema::nombre(),
            $this->email ?: $this->username,
            decrypt($this->two_factor_secret)
        );
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

    /** Puede iniciar sesión: usuario activo, no eliminado y con al menos un rol activo. */
    public function puedeEntrar(): bool
    {
        return $this->activo && ! $this->trashed()
            && $this->roles()->where('rol.activo', true)->exists();
    }

    /** Tiene un rol de acceso total activo (Administrador o Superadmin): pasa todas las validaciones de permisos. */
    public function esAdministrador(): bool
    {
        return $this->roles()
            ->where('rol.activo', true)
            ->whereIn(DB::raw('LOWER(rol.nombre)'), Rol::nombresAccesoTotal())
            ->exists();
    }

    /** Alguno de sus roles activos exige la verificación en dos pasos. */
    public function rolExigeDosPasos(): bool
    {
        return $this->roles()->where('rol.activo', true)->where('rol.requiere_2fa', true)->exists();
    }

    /** Su rol exige 2 pasos y todavía no la activó (o no la confirmó). */
    public function debeActivarDosPasos(): bool
    {
        return ! $this->two_factor_confirmed_at && $this->rolExigeDosPasos();
    }

    /** Permisos extra del usuario, además de los de su rol (como en sistema-inventario). */
    public function permisosExtras()
    {
        return $this->belongsToMany(Permiso::class, 'usuario_permiso', 'id_usuario', 'id_permiso')
            ->withPivot('asignado_por', 'asignado_at');
    }

    /**
     * Uso: $usuario->puede('bodegas.crear'). El Administrador puede todo; los demás,
     * lo que den sus roles activos más sus permisos extra.
     */
    public function puede(string $codigoPermiso): bool
    {
        return $this->esAdministradorEnCache() || in_array($codigoPermiso, $this->codigosPermiso(), true);
    }

    /**
     * Códigos «modulo.accion» del usuario (roles activos + extras), calculados una vez
     * por instancia: el menú y las vistas preguntan muchas veces en la misma petición.
     *
     * @return list<string>
     */
    public function codigosPermiso(): array
    {
        return $this->codigosPermiso ??= Permiso::query()
            ->join('modulo', 'modulo.id_modulo', '=', 'permiso.id_modulo')
            ->join('accion', 'accion.id_accion', '=', 'permiso.id_accion')
            ->where('modulo.activo', true)
            ->where(fn ($q) => $q
                ->whereIn('permiso.id_permiso', fn ($s) => $s->select('rol_permiso.id_permiso')->from('rol_permiso')
                    ->join('usuario_rol', 'usuario_rol.id_rol', '=', 'rol_permiso.id_rol')
                    ->join('rol', 'rol.id_rol', '=', 'rol_permiso.id_rol')
                    ->where('usuario_rol.id_usuario', $this->id_usuario)->where('rol.activo', true))
                ->orWhereIn('permiso.id_permiso', fn ($s) => $s->select('id_permiso')->from('usuario_permiso')
                    ->where('id_usuario', $this->id_usuario)))
            ->get(['modulo.codigo as modulo', 'accion.codigo as accion'])
            ->map(fn ($p) => $p->modulo.'.'.$p->accion)->unique()->values()->all();
    }

    /**
     * Anti-escalada: quien no tiene acceso total solo gestiona (edita, cambia la contraseña,
     * desactiva, elimina) a usuarios sin acceso total cuyos permisos también tiene él.
     */
    public function puedeGestionar(Usuario $otro): bool
    {
        if ($this->esAdministradorEnCache()) {
            return true;
        }
        $delOtro = MatrizPermisos::idsDe($otro);

        return $delOtro !== null && ! array_diff($delOtro, $this->idsPermisoPropios());
    }

    /** Solo asigna roles sin acceso total cuyos permisos también tiene él. */
    public function puedeAsignarRol(Rol $rol): bool
    {
        if ($this->esAdministradorEnCache()) {
            return true;
        }
        $delRol = $rol->permisos()->pluck('permiso.id_permiso')->map(fn ($id) => (int) $id)->all();

        return ! $rol->esAdministrador() && ! array_diff($delRol, $this->idsPermisoPropios());
    }

    /** @return list<int> */
    private function idsPermisoPropios(): array
    {
        return $this->idsPermiso ??= MatrizPermisos::idsDe($this) ?? [];
    }

    /** Llamar si cambian sus roles o permisos durante la misma petición. */
    public function olvidarPermisos(): void
    {
        $this->codigosPermiso = null;
        $this->idsPermiso = null;
        $this->esAdmin = null;
    }

    private function esAdministradorEnCache(): bool
    {
        return $this->esAdmin ??= $this->esAdministrador();
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