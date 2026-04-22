<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {

        // كل API يرجّع JSON
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*');
        });

        // 🔐 Token manquant / invalide
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Token manquant ou invalide.'
            ], 401);
        });

        // ⚠️ أي خطأ آخر
        $exceptions->render(function (\Throwable $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        });

    })

    ->create();