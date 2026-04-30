<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Public ──────────────────────────────────────────
Route::post('/auth/register',               [AuthController::class, 'register']);
Route::post('/auth/login',                  [AuthController::class, 'login']);
Route::post('/auth/forgot-password',        [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password/{token}', [AuthController::class, 'resetPassword']);

// ── Authentifié ─────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    Route::post('/auth/logout',          [AuthController::class, 'logout']);
    Route::get('/users/profile',         [UserController::class, 'getProfile']);
    Route::put('/users/profile',         [UserController::class, 'updateProfile']);
    Route::put('/users/change-password', [UserController::class, 'changePassword']);
    Route::put('/users/change-email',    [UserController::class, 'changeEmail']);

    // ── Admin seulement ───────────────────────────
    Route::middleware('is_admin')->group(function () {
        Route::get('/users',               [UserController::class, 'getAllUsers']);
        Route::put('/users/{id}/validate', [UserController::class, 'validateUser']);
        Route::put('/users/{id}/disable',  [UserController::class, 'disableUser']);
        Route::put('/users/{id}/enable',   [UserController::class, 'enableUser']);
        Route::put('/users/{id}',          [UserController::class, 'updateUser']);
        // Pas de DELETE — suppression interdite (traçabilité)
    });
});