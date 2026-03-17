<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        "name",
        "email",
        "password",
        "first_name",
        "last_name",
        "phone",
        "avatar",
        "gender",
        "date_of_birth",
        "locale",
        "currency",
        "status",
        "last_login_at",
        "last_login_ip",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        "password",
        "two_factor_secret",
        "two_factor_recovery_codes",
        "remember_token",
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "date_of_birth" => "date",
            "last_login_at" => "datetime",
            "deleted_at" => "datetime",
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(" ")
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode("");
    }

    // =========================================================================
    //  RELATIONS — RÔLES
    // =========================================================================

    /** Toutes les lignes user_roles (tous statuts) */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /** Rôles actifs uniquement */
    public function activeUserRoles(): HasMany
    {
        return $this->hasMany(UserRole::class)->active();
    }

    /** Rôles (instances Role) actifs via user_roles */
    public function roles(): HasManyThrough
    {
        return $this->hasManyThrough(
            Role::class,
            UserRole::class,
            "user_id", // FK sur user_roles
            "id", // FK sur roles
            "id", // PK sur users
            "role_id", // FK locale sur user_roles
        )->whereHas(
            "userRoles",
            fn($q) => $q->where("user_id", $this->id)->active(),
        );
    }

    /** Rôle principal */
    public function primaryRole(): HasOne
    {
        return $this->hasOne(UserRole::class)
            ->where("is_primary", true)
            ->active()
            ->with("role");
    }

    // =========================================================================
    //  AFFECTER / SYNCHRONISER / RÉVOQUER
    // =========================================================================

    /**
     * Affecter UN rôle à l'utilisateur.
     *
     * @param  int|string|Role  $role
     * @param  int|null         $assignedBy   ID de l'admin qui attribue (NULL = système)
     * @param  string|null      $expiresAt    Date d'expiration ('2025-12-31 23:59:59')
     * @param  bool             $makePrimary  Définir comme rôle principal
     * @param  string|null      $notes        Raison de l'attribution
     *
     * @example
     *   $user->assignRole('supplier');
     *   $user->assignRole('moderator', assignedBy: 1, expiresAt: '2025-06-30');
     *   $user->assignRole(Role::find(3), makePrimary: true);
     */
    public function assignRole(
        int|string|Role $role,
        ?int $assignedBy = null,
        ?string $expiresAt = null,
        bool $makePrimary = false,
        ?string $notes = null,
    ): UserRole {
        $roleId = $this->resolveRoleId($role);

        // Si makePrimary, retirer le flag primary des autres rôles
        if ($makePrimary) {
            $this->userRoles()->update(["is_primary" => false]);
        }

        /** @var UserRole $userRole */
        $userRole = $this->userRoles()->updateOrCreate(
            ["role_id" => $roleId],
            [
                "assigned_by" => $assignedBy,
                "assigned_at" => now(),
                "expires_at" => $expiresAt,
                "status" => "active",
                "is_primary" => $makePrimary,
                "revoked_at" => null,
                "revoked_by" => null,
                "notes" => $notes,
            ],
        );

        $this->unsetRelation("activeUserRoles");
        $this->unsetRelation("primaryRole");

        return $userRole;
    }

    /**
     * Affecter PLUSIEURS rôles en une seule opération.
     *
     * @param  array<int|string|Role>  $roles
     * @param  int|null                $assignedBy
     * @param  string|null             $expiresAt   Même expiration pour tous
     *
     * @example
     *   $user->assignRoles(['supplier', 'moderator'], assignedBy: auth()->id());
     *   $user->assignRoles([2, 5], expiresAt: '2025-12-31');
     *
     * @return UserRole[]
     */
    public function assignRoles(
        array $roles,
        ?int $assignedBy = null,
        ?string $expiresAt = null,
    ): array {
        $results = [];

        foreach ($roles as $index => $role) {
            $results[] = $this->assignRole(
                role: $role,
                assignedBy: $assignedBy,
                expiresAt: $expiresAt,
                makePrimary: $index === 0 && !$this->hasPrimaryRole(),
                // Le premier rôle devient primary si l'utilisateur n'en a pas encore
            );
        }

        return $results;
    }

    /**
     * Synchroniser les rôles : remplace la liste ACTIVE par les rôles donnés.
     * Les rôles absents sont révoqués. Les nouveaux sont créés/réactivés.
     * Le premier de la liste devient le rôle principal.
     *
     * @param  array<int|string|Role>  $roles
     * @param  int|null                $assignedBy
     *
     * @example
     *   $user->syncRoles(['supplier', 'moderator']);
     *   // => l'utilisateur a EXACTEMENT ces deux rôles, les autres sont révoqués
     */
    public function syncRoles(array $roles, ?int $assignedBy = null): void
    {
        $newRoleIds = array_map(fn($r) => $this->resolveRoleId($r), $roles);

        // Révoquer les rôles qui ne sont plus dans la liste
        $this->userRoles()
            ->active()
            ->whereNotIn("role_id", $newRoleIds)
            ->each(fn(UserRole $ur) => $ur->revoke($assignedBy ?? $this->id));

        // Créer ou réactiver les rôles de la liste
        foreach ($newRoleIds as $index => $roleId) {
            $this->userRoles()->updateOrCreate(
                ["role_id" => $roleId],
                [
                    "assigned_by" => $assignedBy,
                    "assigned_at" => now(),
                    "status" => "active",
                    "is_primary" => $index === 0,
                    "expires_at" => null,
                    "revoked_at" => null,
                    "revoked_by" => null,
                ],
            );
        }

        $this->unsetRelation("activeUserRoles");
        $this->unsetRelation("primaryRole");
    }

    /**
     * Révoquer un rôle spécifique.
     *
     * @param  int|string|Role  $role
     * @param  int|null         $revokedBy
     *
     * @example
     *   $user->revokeRole('moderator');
     *   $user->revokeRole(3, revokedBy: auth()->id());
     */
    public function revokeRole(
        int|string|Role $role,
        ?int $revokedBy = null,
    ): void {
        $roleId = $this->resolveRoleId($role);

        $userRole = $this->userRoles()->where("role_id", $roleId)->first();

        if ($userRole) {
            $userRole->revoke($revokedBy ?? $this->id);

            // Si c'était le rôle primary, promouvoir le suivant
            if ($userRole->is_primary) {
                $this->promoteNextPrimaryRole();
            }
        }

        $this->unsetRelation("activeUserRoles");
        $this->unsetRelation("primaryRole");
    }

    /**
     * Révoquer TOUS les rôles actifs de l'utilisateur.
     *
     * @param  int|null  $revokedBy
     */
    public function revokeAllRoles(?int $revokedBy = null): void
    {
        $this->userRoles()
            ->active()
            ->each(fn(UserRole $ur) => $ur->revoke($revokedBy ?? $this->id));

        $this->unsetRelation("activeUserRoles");
        $this->unsetRelation("primaryRole");
    }

    /**
     * Définir le rôle principal (primary) parmi les rôles actifs existants.
     *
     * @param  int|string|Role  $role
     *
     * @example
     *   $user->setPrimaryRole('supplier');
     */
    public function setPrimaryRole(int|string|Role $role): void
    {
        $roleId = $this->resolveRoleId($role);

        // S'assurer que le rôle est actif
        $userRole = $this->userRoles()
            ->active()
            ->where("role_id", $roleId)
            ->first();

        if (!$userRole) {
            throw new \RuntimeException(
                "Le rôle [{$roleId}] n'est pas actif pour cet utilisateur.",
            );
        }

        // Retirer primary de tous les autres
        $this->userRoles()->update(["is_primary" => false]);

        // Définir le nouveau primary
        $userRole->update(["is_primary" => true]);

        $this->unsetRelation("primaryRole");
    }

    // =========================================================================
    //  VÉRIFICATIONS DE RÔLES
    // =========================================================================

    /**
     * Vérifie si l'utilisateur possède un rôle actif.
     *
     * @param  int|string|Role  $role
     *
     * @example
     *   $user->hasRole('admin');
     *   $user->hasRole(1);
     *   $user->hasRole($roleInstance);
     */
    public function hasRole(int|string|Role $role): bool
    {
        return $this->activeUserRoles->contains(function (UserRole $ur) use (
            $role,
        ) {
            if ($role instanceof Role) {
                return $ur->role_id === $role->id;
            }
            if (is_int($role)) {
                return $ur->role_id === $role;
            }
            return $ur->role?->name === $role;
        });
    }

    /**
     * Vérifie si l'utilisateur a AU MOINS UN des rôles listés.
     *
     * @param  array<int|string|Role>  $roles
     *
     * @example
     *   $user->hasAnyRole(['admin', 'moderator']);
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur a TOUS les rôles listés.
     *
     * @param  array<int|string|Role>  $roles
     *
     * @example
     *   $user->hasAllRoles(['supplier', 'moderator']);
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Retourne les noms des rôles actifs.
     *
     * @return array<string>
     *
     * @example
     *   $user->getRoleNames(); // => ['supplier', 'moderator']
     */
    public function getRoleNames(): array
    {
        return $this->activeUserRoles
            ->map(fn(UserRole $ur) => $ur->role?->name)
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Retourne le nom du rôle principal.
     */
    public function getPrimaryRoleName(): ?string
    {
        return $this->primaryRole?->role?->name;
    }

    /**
     * Vérifie si l'utilisateur a un rôle primary défini.
     */
    public function hasPrimaryRole(): bool
    {
        return $this->userRoles()
            ->where("is_primary", true)
            ->active()
            ->exists();
    }

    // =========================================================================
    //  VÉRIFICATIONS DE PERMISSIONS
    // =========================================================================

    /**
     * Vérifie si l'utilisateur a une permission via n'importe lequel de ses rôles actifs.
     *
     * @example
     *   $user->hasPermission('products.create');
     */
    public function hasPermission(string $permission): bool
    {
        return $this->activeUserRoles
            ->map(fn(UserRole $ur) => $ur->role)
            ->filter()
            ->contains(fn(Role $role) => $role->hasPermission($permission));
    }

    /**
     * Vérifie si l'utilisateur a AU MOINS UNE des permissions listées.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if ($this->hasPermission($perm)) {
                return true;
            }
        }
        return false;
    }

    // =========================================================================
    //  RACCOURCIS RÔLES MÉTIER
    // =========================================================================

    public function isAdmin(): bool
    {
        return $this->hasRole("admin");
    }
    public function isSupplier(): bool
    {
        return $this->hasRole("supplier");
    }
    public function isTransporter(): bool
    {
        return $this->hasRole("transporter");
    }
    public function isCustomer(): bool
    {
        return $this->hasRole("customer");
    }

    // =========================================================================
    //  AUTRES RELATIONS
    // =========================================================================

    public function referrer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, "referred_by");
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, "referred_by");
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, "addressable");
    }

    public function supplier(): HasOne
    {
        return $this->hasOne(Supplier::class);
    }

    public function transporter(): HasOne
    {
        return $this->hasOne(Transporter::class);
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, "customer_id");
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wallet(): MorphMany
    {
        return $this->morphMany(Wallet::class, "owner");
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, "customer_id");
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function recentlyViewed(): HasMany
    {
        return $this->hasMany(RecentlyViewed::class)
            ->latest("viewed_at")
            ->limit(20);
    }

    // =========================================================================
    //  SCOPES
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where("status", "active");
    }

    public function scopeWithRole($query, int|string $role)
    {
        return $query->whereHas(
            "activeUserRoles.role",
            fn($q) => is_int($role)
                ? $q->where("roles.id", $role)
                : $q->where("roles.name", $role),
        );
    }

    // =========================================================================
    //  UTILITAIRES
    // =========================================================================

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    // ── Helpers privés ─────────────────────────────────────────────────────

    protected function resolveRoleId(int|string|Role $role): int
    {
        if ($role instanceof Role) {
            return $role->id;
        }
        if (is_int($role)) {
            return $role;
        }
        return Role::where("name", $role)->firstOrFail()->id;
    }

    protected function promoteNextPrimaryRole(): void
    {
        $next = $this->userRoles()
            ->active()
            ->where("is_primary", false)
            ->first();
        $next?->update(["is_primary" => true]);
    }
}
