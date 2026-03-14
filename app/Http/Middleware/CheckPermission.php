<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware : CheckPermission
 *
 * Vérifie que l'utilisateur possède la permission requise
 * via l'un de ses rôles actifs.
 *
 * ── Utilisation dans les routes ──────────────────────────────────────────
 *
 *   // Permission unique
 *   Route::post('/products', ...)->middleware('permission:products.create');
 *
 *   // Au moins UNE permission parmi la liste (OU logique)
 *   Route::get('/orders', ...)->middleware('permission:orders.view,orders.manage');
 *
 *   // Toutes les permissions obligatoires (ET logique avec "|")
 *   Route::delete('/products/{id}', ...)->middleware('permission:products.delete|products.manage');
 *
 * ── Enregistrement dans bootstrap/app.php ────────────────────────────────
 *
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->alias([
 *           'permission' => \App\Http\Middleware\CheckPermission::class,
 *       ]);
 *   })
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (! $request->user()) {
            return $this->unauthorized($request);
        }

        $user = $request->user();

        // L'admin a toujours tous les droits
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        foreach ($permissions as $permExpression) {
            // "|" = toutes les permissions requises (ET logique)
            if (str_contains($permExpression, '|')) {
                $required = explode('|', $permExpression);
                $hasAll = true;
                foreach ($required as $perm) {
                    if (! $user->hasPermission(trim($perm))) {
                        $hasAll = false;
                        break;
                    }
                }
                if ($hasAll) {
                    return $next($request);
                }
            } else {
                // Chaque paramètre "," = une permission acceptée (OU logique)
                if ($user->hasPermission($permExpression)) {
                    return $next($request);
                }
            }
        }

        return $this->forbidden($request);
    }

    private function unauthorized(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Authentification requise.'], 401);
        }
        return redirect()->route('login');
    }

    private function forbidden(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Permission insuffisante.'], 403);
        }
        abort(403, 'Permission insuffisante.');
    }
}
