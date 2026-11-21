<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Models\Role;

#[Hidden([
    'password',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'remember_token',
])]
class User extends Authenticatable implements PasskeyUser
{
    use LogsActivity, HasApiTokens,
        HasFactory,
        HasRoles,
        Notifiable,
        PasskeyAuthenticatable,
        TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'qr_token',
        'is_active',
        'last_login_at',
        'active_role_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            do {
                $token = Str::random(64);
            } while (User::where('qr_token', $token)->exists());

            $user->qr_token = $token;
        });
    }

    public function people(): HasOne
    {
        return $this->hasOne(People::class, 'user_id');
    }

    public function activeRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'active_role_id');
    }

    /**
     * Rol con el que el usuario navega. Si el guardado ya no le pertenece,
     * se usa el primero de sus roles.
     */
    public function currentRole(): ?Role
    {
        $roles = $this->roles;

        return $roles->firstWhere('id', $this->active_role_id) ?? $roles->first();
    }

    /**
     * Los permisos heredados de roles se toman solo del rol activo, para que el
     * selector de rol restrinja también las rutas y no solo el menú.
     * Los permisos asignados directamente al usuario siguen aplicando.
     */
    protected function hasPermissionViaRole(Permission $permission): bool
    {
        $role = $this->currentRole();

        return $role !== null && $permission->roles->contains('id', $role->id);
    }

    public function getPermissionsViaRoles(): Collection
    {
        return $this->currentRole()?->permissions ?? collect();
    }

    public function isSuperAdmin(): bool
    {
        return $this->currentRole()?->name === 'SuperAdmin';
    }

    public function switchRole(Role $role): void
    {
        abort_unless($this->roles->contains('id', $role->id), 403);

        $this->forceFill(['active_role_id' => $role->id])->save();
    }
}
