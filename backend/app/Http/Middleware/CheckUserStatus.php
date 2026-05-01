<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->statut !== 'actif') {
            try {
                JWTAuth::invalidate(JWTAuth::getToken());
            } catch (\Exception $e) {
                // Ignore exception if token is already invalid/expired
            }

            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Votre compte n\'est pas actif (statut actuel : ' . $user->statut . ').'
            ], 403);
        }

        return $next($request);
    }
}
