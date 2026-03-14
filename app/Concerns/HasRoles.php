<?php

namespace App\Concerns;

use App\Models\Role;
use App\Models\UserRole;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Trait HasRoles
 *
 * À inclure dans le modèle User.
 * Délègue toutes les vérifications au RoleService (avec cache).
 *
 * ── Utilisation ────────────────────────────────────────────────────────────
 *
 *   class User extends Authenticatable
 *   {
 *       use HasRoles;
 *   }
 *
 * ── Méthodes disponibles ───────────────────────────────────────────────────
 *
 *   Vérifications
 *   $user->hasRole('admin')
 *   $user->hasRole(1)
 *   $user->hasAnyRole(['admin', 'supplier'])
 *   $user->hasAllRoles(['admin', 'moderator'])
 *   $user->hasPermission('products.create')
 *   $user->hasAnyPermission(['products.create', 'products.edit'])
 *   $user->isAdmin()
 *   $user->isSupplier()
 *   $user->isTransporter()
 *   $user->isCustomer()
 *
 *   Informations
 *   $user->getRoleNames()       // ['admin', 'supplier']
 *   $user->getPermissions()     // ['products.create', 'orders.view', ...]
 *   $user->getPrimaryRole()     // 'admin'
 *
 *   Modifications
 *   $user->assignRole('supplier')
 *   $user->assignRole('moderator', assignedBy: 1, expiresAt: '2026-12-31')
 *   $user->assignRoles(['supplier', 'moderator'])
 *   $user->syncRoles(['supplier'])
 *   $user->revokeRole('moderator')
 *   $user->revokeAllRoles()
 *   $user->setPrimaryRole('supplier')
 */
trait HasRoles
{
    // =========================================================================
    //  RELATIONS
    // =========================================================================

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function activeUserRoles(): HasMany
    {
        return $this->hasMany(UserRole::class)->active()->with("role");
    }

    public function primaryRole(): HasOne
    {
        return $this->hasOne(UserRole::class)
            ->where("is_primary", true)
            ->active()
            ->with("role");
    }

    // =========================================================================
    //  VÉRIFICATIONS (via RoleService + cache)
    // =========================================================================

    public function hasRole(int|string $role): bool
    {
        return app(RoleService::class)->hasRole($this, $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return app(RoleService::class)->hasAnyRole($this, $roles);
    }

    public function hasAllRoles(array $roles): bool
    {
        return app(RoleService::class)->hasAllRoles($this, $roles);
    }

    public function hasPermission(string $permission): bool
    {
        return app(RoleService::class)->hasPermission($this, $permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return app(RoleService::class)->hasAnyPermission($this, $permissions);
    }

    // =========================================================================
    //  RACCOURCIS MÉTIER
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
    //  INFORMATIONS
    // =========================================================================

    /** @return string[] */
    public function getRoleNames(): array
    {
        return app(RoleService::class)->getRoleNames($this);
    }

    /** @return string[] */
    public function getPermissions(): array
    {
        return app(RoleService::class)->getPermissions($this);
    }

    public function getPrimaryRole(): ?string
    {
        return app(RoleService::class)->getPrimaryRole($this);
    }

    // =========================================================================
    //  MODIFICATIONS (invalident le cache automatiquement)
    // =========================================================================

    /**
     * Affecter un rôle.
     *
     * @example
     *   $user->assignRole('supplier');
     *   $user->assignRole('moderator', assignedBy: 1, expiresAt: '2026-12-31 23:59:59');
     */
    public function assignRole(
        int|string|Role $role,
        ?int $assignedBy = null,
        ?string $expiresAt = null,
        bool $makePrimary = false,
        ?string $notes = null,
    ): UserRole {
        $roleId = $this->resolveRoleId($role);

        if ($makePrimary) {
            $this->userRoles()->update(["is_primary" => false]);
        }

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

        $this->invalidateRoleCache();

        return $userRole;
    }

    /**
     * Affecter plusieurs rôles d'un coup.
     *
     * @example
     *   $user->assignRoles(['supplier', 'moderator']);
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
            );
        }
        return $results;
    }

    /**
     * Remplace TOUS les rôles actifs par la liste fournie.
     *
     * @example
     *   $user->syncRoles(['supplier', 'moderator']);
     */
    public function syncRoles(array $roles, ?int $assignedBy = null): void
    {
        $newRoleIds = array_map(fn($r) => $this->resolveRoleId($r), $roles);

        // Révoquer les rôles absents de la nouvelle liste
        $this->userRoles()
            ->active()
            ->whereNotIn("role_id", $newRoleIds)
            ->each(fn(UserRole $ur) => $ur->revoke($assignedBy ?? $this->id));

        // Créer ou réactiver les rôles de la nouvelle liste
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

        $this->invalidateRoleCache();
    }

    /**
     * Révoquer un rôle spécifique.
     *
     * @example
     *   $user->revokeRole('moderator');
     *   $user->revokeRole('moderator', revokedBy: auth()->id());
     */
    public function revokeRole(
        int|string|Role $role,
        ?int $revokedBy = null,
    ): void {
        $roleId = $this->resolveRoleId($role);
        $userRole = $this->userRoles()->where("role_id", $roleId)->first();

        if ($userRole) {
            $userRole->revoke($revokedBy ?? $this->id);

            if ($userRole->is_primary) {
                $this->promoteNextPrimaryRole();
            }
        }

        $this->invalidateRoleCache();
    }

    /**
     * Révoquer tous les rôles actifs.
     */
    public function revokeAllRoles(?int $revokedBy = null): void
    {
        $this->userRoles()
            ->active()
            ->each(fn(UserRole $ur) => $ur->revoke($revokedBy ?? $this->id));

        $this->invalidateRoleCache();
    }

    /**
     * Définir le rôle principal parmi les rôles actifs existants.
     *
     * @example
     *   $user->setPrimaryRole('supplier');
     */
    public function setPrimaryRole(int|string|Role $role): void
    {
        $roleId = $this->resolveRoleId($role);
        $userRole = $this->userRoles()
            ->active()
            ->where("role_id", $roleId)
            ->first();

        if (!$userRole) {
            throw new \RuntimeException(
                "Le rôle [{$roleId}] n'est pas actif pour cet utilisateur.",
            );
        }

        $this->userRoles()->update(["is_primary" => false]);
        $userRole->update(["is_primary" => true]);

        $this->invalidateRoleCache();
    }

    public function hasPrimaryRole(): bool
    {
        return $this->userRoles()
            ->where("is_primary", true)
            ->active()
            ->exists();
    }

    // =========================================================================
    //  PRIVÉ
    // =========================================================================

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

    protected function invalidateRoleCache(): void
    {
        app(RoleService::class)->clearCache($this);
    }
}
