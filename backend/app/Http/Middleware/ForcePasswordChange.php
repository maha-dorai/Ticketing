<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class ForcePasswordChange
{
    // Routes autorisées même si force_password_change = true
    private const ALLOWED_ROUTES = [
        'api/users/change-password',
        'api/auth/logout',
    ];

    public function handle($request, Closure $next)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if ($user->force_password_change) {
            // Vérifie si la route actuelle est dans la liste autorisée
            foreach (self::ALLOWED_ROUTES as $route) {
                if ($request->is($route)) {
                    return $next($request);
                }
            }

            return response()->json([
                'message'               => 'Vous devez changer votre mot de passe avant de continuer.',
                'force_password_change' => true,
            ], 403);
        }

        return $next($request);
    }
}