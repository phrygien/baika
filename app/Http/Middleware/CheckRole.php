<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware : CheckRole
 *
 * Vérifie que l'utilisateur connecté possède au moins UN des rôles requis.
 *
 * ── Utilisation dans les routes ──────────────────────────────────────────
 *
 *   // Un seul rôle
 *   Route::get('/admin', ...)->middleware('role:admin');
 *
 *   // Plusieurs rôles acceptés (OU logique)
 *   Route::get('/dashboard', ...)->middleware('role:admin,supplier');
 *
 *   // Plusieurs rôles obligatoires (ET logique)
 *   Route::get('/special', ...)->middleware('role:admin|supplier');
 *   // → l'utilisateur doit avoir TOUS les rôles séparés par "|"
 *
 * ── Utilisation dans les contrôleurs ─────────────────────────────────────
 *
 *   public function __construct()
 *   {
 *       $this->middleware('role:admin');
 *   }
 *
 * ── Enregistrement dans bootstrap/app.php ────────────────────────────────
 *
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->alias([
 *           'role' => \App\Http\Middleware\CheckRole::class,
 *       ]);
 *   })
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Utilisateur non connecté
        if (! $request->user()) {
            return $this->unauthorized($request, 'Authentification requise.');
        }

        $user = $request->user();

        foreach ($roles as $roleExpression) {
            // "|" sépare les rôles requis simultanément (ET logique)
            if (str_contains($roleExpression, '|')) {
                $required = explode('|', $roleExpression);
                if ($user->hasAllRoles($required)) {
                    return $next($request);
                }
            } else {
                // Chaque paramètre séparé par "," est un rôle accepté (OU logique)
                if ($user->hasRole($roleExpression)) {
                    return $next($request);
                }
            }
        }

        return $this->forbidden($request, 'Rôle insuffisant.');
    }

    private function unauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }
        return redirect()->route('login');
    }

    private function forbidden(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }
        abort(403, $message);
    }
}
