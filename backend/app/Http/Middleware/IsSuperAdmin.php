<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class IsSuperAdmin
{
    public function handle($request, Closure $next)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if ($user->role !== 'admin')
            return response()->json(['message' => 'Accès réservé au super administrateur.'], 403);

        return $next($request);
    }
}