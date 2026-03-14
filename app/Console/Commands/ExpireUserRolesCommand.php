<?php

namespace App\Console\Commands;

use App\Models\UserRole;
use App\Services\RoleService;
use Illuminate\Console\Command;

/**
 * Commande : expire:user-roles
 *
 * Passe tous les UserRole dont expires_at est dépassé en statut 'expired'
 * et invalide leur cache.
 *
 * ── Planification dans routes/console.php ────────────────────────────────
 *
 *   Schedule::command('roles:expire')->hourly();
 *
 * ── Exécution manuelle ────────────────────────────────────────────────────
 *
 *   php artisan roles:expire
 */
class ExpireUserRolesCommand extends Command
{
    protected $signature   = 'roles:expire';
    protected $description = 'Marque les rôles expirés et invalide leur cache.';

    public function handle(RoleService $roleService): int
    {
        $expired = UserRole::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with('user')
            ->get();

        $count = 0;
        foreach ($expired as $userRole) {
            $userRole->markExpired();
            if ($userRole->user) {
                $roleService->clearCache($userRole->user);
            }
            $count++;
        }

        $this->info("✅ {$count} rôle(s) expiré(s) traité(s).");
        return self::SUCCESS;
    }
}
