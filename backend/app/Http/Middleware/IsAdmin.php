<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class IsAdmin
{
    public function handle($request, Closure $next)
    {
        $user = JWTAuth::parseToken()->authenticate();

        // Accès autorisé pour admin ET super_admin
        if (!in_array($user->role, ['chef_de_projet', 'super_admin']))
            return response()->json(['message' => 'Accès réservé aux administrateurs.'], 403);

        return $next($request);
    }
}