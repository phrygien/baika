<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware : CheckRoleOrPermission
 *
 * Passe si l'utilisateur a le rôle OU la permission spécifiée.
 * Utile pour les routes accessibles soit par rôle direct, soit par permission fine.
 *
 * ── Utilisation ───────────────────────────────────────────────────────────
 *
 *   // Accès si rôle "admin" OU permission "products.view"
 *   Route::get('/products', ...)->middleware('role_or_permission:admin,products.view');
 *
 * ── Enregistrement dans bootstrap/app.php ────────────────────────────────
 *
 *   'role_or_permission' => \App\Http\Middleware\CheckRoleOrPermission::class,
 */
class CheckRoleOrPermission
{
    public function handle(Request $request, Closure $next, string $role, string $permission): Response
    {
        if (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Authentification requise.'], 401);
            }
            return redirect()->route('login');
        }

        $user = $request->user();

        if ($user->hasRole($role) || $user->hasPermission($permission)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        abort(403, 'Accès refusé.');
    }
}
