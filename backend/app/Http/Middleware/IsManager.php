<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Accès réservé aux rôles avec droits de gestion :
 * chef_de_projet  ET  admin
 */
class IsManager
{
    public function handle($request, Closure $next)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!in_array($user->role, ['chef_de_projet', 'admin']))
            return response()->json(['message' => 'Accès réservé aux managers (chef de projet / admin).'], 403);

        return $next($request);
    }
}
