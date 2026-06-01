<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Accès réservé à l'admin uniquement.
 * (gestion des chefs de projet, actions sensibles)
 */
class IsAdmin
{
    public function handle($request, Closure $next)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if ($user->role !== 'admin')
            return response()->json(['message' => "Accès réservé à l'administrateur."], 403);

        return $next($request);
    }
}
