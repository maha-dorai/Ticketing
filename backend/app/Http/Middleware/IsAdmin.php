<?php
// ============================================================
// app/Http/Middleware/IsAdmin.php — Gardien des routes Admin
// Ce middleware vérifie que l'utilisateur connecté est bien un admin
// Il est appliqué sur les routes dans le groupe 'is_admin' de api.php
// ============================================================

namespace App\Http\Middleware;

use Closure;

// On importe JWTAuth pour récupérer l'utilisateur depuis le token JWT
use Tymon\JWTAuth\Facades\JWTAuth;

class IsAdmin
{
    // La méthode handle() est appelée automatiquement avant chaque requête sur les routes protégées
    // $request : la requête HTTP entrante (contient headers, body, etc.)
    // $next    : la fonction à appeler pour laisser passer la requête vers le contrôleur
    public function handle($request, Closure $next)
    {
        // Extrait et authentifie l'utilisateur depuis le token JWT dans le header Authorization
        // Si le token est invalide ou absent, une exception est levée automatiquement
        $user = JWTAuth::parseToken()->authenticate();

        // Vérifie que le rôle de l'utilisateur est bien 'admin'
        // Si ce n'est pas le cas, on bloque l'accès
        if ($user->role !== 'admin')
            // Retourne une erreur 403 Forbidden avec un message clair
            return response()->json(
                ['message' => 'Accès réservé aux administrateurs.'], 403
            );

        // L'utilisateur est admin → on le laisse passer vers le contrôleur
        return $next($request);
    }
}