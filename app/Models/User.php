<?php

namespace App\Models;

use App\Concerns\HasTeams;
use App\Support\Preferencias;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Reemplaza el App\Models\User que trae Laravel.
 * Roles: administrador, recepcionista, mecanico (spatie/laravel-permission).
 */
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasTeams, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable {
        // Los equipos de Spatie están desactivados (permission.teams = false); manda el del kit.
        HasTeams::teams insteadof HasRoles;
    }

    protected $fillable = [
        'sucursal_id',
        'name',
        'email',
        'telefono',
        'password',
        'activo',
        'current_team_id',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    /** Iniciales del usuario para el avatar. */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    // ── Relaciones ───────────────────────────────────────────────

    /** @return BelongsTo<Sucursal, $this> */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /** @return HasMany<OrdenServicio, $this> */
    public function ordenesRecibidas(): HasMany
    {
        return $this->hasMany(OrdenServicio::class, 'recibido_por_id');
    }

    /** @return HasMany<OrdenServicio, $this> */
    public function ordenesAsignadas(): HasMany
    {
        return $this->hasMany(OrdenServicio::class, 'mecanico_responsable_id');
    }

    /** Trabajos en los que participó como mecánico, con sus horas.
     * @return BelongsToMany<OrdenServicioItem, $this>
     */
    public function trabajos(): BelongsToMany
    {
        return $this->belongsToMany(OrdenServicioItem::class, 'orden_servicio_mecanico', 'user_id', 'orden_servicio_item_id')
            ->withPivot('horas')
            ->withTimestamps();
    }

    // ── Scopes ───────────────────────────────────────────────────

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    // ── Reglas de visibilidad de dinero ─────────────────────────

    public function esAdministrador(): bool
    {
        return $this->hasRole('administrador');
    }

    /**
     * ¿Puede ver importes? Combina la preferencia del taller, el modo de la orden y el rol.
     * Si el administrador bloquea usar_precios, nadie ve precios.
     */
    public function puedeVerPrecios(?OrdenServicio $orden = null): bool
    {
        if ($orden !== null && ! $orden->con_precios) {
            return false;
        }

        if (! Preferencias::bool('usar_precios', $this->sucursal_id, true)) {
            return false;
        }

        if ($this->esAdministrador()) {
            return true;
        }

        if ($this->hasRole('mecanico')) {
            return Preferencias::bool('mostrar_precios_a_mecanicos', $this->sucursal_id);
        }

        return $this->checkPermissionTo('precios.ver');
    }

    /** Costo de compra y utilidad: solo con permiso explícito. */
    public function puedeVerCostos(): bool
    {
        return Preferencias::bool('usar_precios', $this->sucursal_id, true)
            && ($this->esAdministrador() || $this->checkPermissionTo('costos.ver'));
    }
}
