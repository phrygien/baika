<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * RoleService
 *
 * Centralise la logique de vérification des rôles et permissions.
 * Utilise un cache Redis/file par utilisateur pour éviter les requêtes répétitives
 * à chaque requête HTTP.
 *
 * Le cache est invalidé automatiquement quand les rôles de l'utilisateur changent.
 *
 * ── Utilisation directe ────────────────────────────────────────────────────
 *
 *   $service = app(RoleService::class);
 *
 *   $service->hasRole($user, 'admin');
 *   $service->hasPermission($user, 'products.create');
 *   $service->getRoleNames($user);      // ['admin', 'supplier']
 *   $service->getPermissions($user);    // ['products.create', 'orders.view', ...]
 *   $service->clearCache($user);        // Invalider le cache après un changement de rôle
 */
class RoleService
{
    /** Durée du cache en secondes (5 minutes) */
    private const CACHE_TTL = 300;

    /** Préfixe de clé cache */
    private const CACHE_PREFIX = 'user_roles_';

    // =========================================================================
    //  VÉRIFICATIONS PUBLIQUES
    // =========================================================================

    /**
     * L'utilisateur possède-t-il ce rôle actif ?
     */
    public function hasRole(User $user, int|string $role): bool
    {
        return in_array(
            is_int($role) ? $role : $role,
            $this->getRoleNames($user),
            strict: true
        ) || in_array($role, $this->getRoleIds($user), strict: true);
    }

    /**
     * L'utilisateur possède-t-il AU MOINS UN de ces rôles ?
     */
    public function hasAnyRole(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($user, $role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * L'utilisateur possède-t-il TOUS ces rôles ?
     */
    public function hasAllRoles(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (! $this->hasRole($user, $role)) {
                return false;
            }
        }
        return true;
    }

    /**
     * L'utilisateur possède-t-il cette permission (via l'un de ses rôles actifs) ?
     */
    public function hasPermission(User $user, string $permission): bool
    {
        return in_array($permission, $this->getPermissions($user), strict: true);
    }

    /**
     * L'utilisateur possède-t-il AU MOINS UNE de ces permissions ?
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        $userPerms = $this->getPermissions($user);
        foreach ($permissions as $perm) {
            if (in_array($perm, $userPerms, strict: true)) {
                return true;
            }
        }
        return false;
    }

    // =========================================================================
    //  RÉCUPÉRATION (avec cache)
    // =========================================================================

    /**
     * Retourne les noms des rôles actifs de l'utilisateur.
     *
     * @return string[]  Ex : ['admin', 'supplier']
     */
    public function getRoleNames(User $user): array
    {
        return $this->cached($user)['role_names'];
    }

    /**
     * Retourne les IDs des rôles actifs.
     *
     * @return int[]
     */
    public function getRoleIds(User $user): array
    {
        return $this->cached($user)['role_ids'];
    }

    /**
     * Retourne toutes les permissions agrégées des rôles actifs.
     *
     * @return string[]  Ex : ['products.create', 'orders.view']
     */
    public function getPermissions(User $user): array
    {
        return $this->cached($user)['permissions'];
    }

    /**
     * Retourne le nom du rôle principal (is_primary = true).
     */
    public function getPrimaryRole(User $user): ?string
    {
        return $this->cached($user)['primary_role'];
    }

    // =========================================================================
    //  GESTION DU CACHE
    // =========================================================================

    /**
     * Invalider le cache d'un utilisateur.
     * À appeler après chaque modification de rôle (assignRole, revokeRole…).
     */
    public function clearCache(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    /**
     * Réchauffer le cache d'un utilisateur (utile après un login).
     */
    public function warmCache(User $user): void
    {
        Cache::forget($this->cacheKey($user));
        $this->cached($user);
    }

    // =========================================================================
    //  PRIVÉ
    // =========================================================================

    /**
     * Charge et met en cache les données de rôles/permissions d'un utilisateur.
     */
    private function cached(User $user): array
    {
        return Cache::remember(
            $this->cacheKey($user),
            self::CACHE_TTL,
            fn () => $this->buildPayload($user)
        );
    }

    /**
     * Construit le payload complet depuis la base de données.
     */
    private function buildPayload(User $user): array
    {
        // Charger les user_roles actifs avec les rôles et leurs permissions
        $userRoles = UserRole::query()
            ->where('user_id', $user->id)
            ->active()
            ->with('role.permissions')
            ->get();

        $roleNames   = [];
        $roleIds     = [];
        $permissions = [];
        $primaryRole = null;

        foreach ($userRoles as $userRole) {
            if (! $userRole->role) {
                continue;
            }

            $roleNames[] = $userRole->role->name;
            $roleIds[]   = $userRole->role->id;

            if ($userRole->is_primary) {
                $primaryRole = $userRole->role->name;
            }

            // Agréger les permissions de ce rôle
            foreach ($userRole->role->permissions ?? [] as $permission) {
                $permissions[] = $permission->name;
            }
        }

        return [
            'role_names'   => array_unique($roleNames),
            'role_ids'     => array_unique($roleIds),
            'permissions'  => array_unique($permissions),
            'primary_role' => $primaryRole,
        ];
    }

    private function cacheKey(User $user): string
    {
        return self::CACHE_PREFIX . $user->id;
    }
}
