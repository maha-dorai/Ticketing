<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── عام — بدون login ──────────────────────────────
Route::post('/auth/register',               [AuthController::class, 'register']);
Route::post('/auth/login',                  [AuthController::class, 'login']);
Route::post('/auth/forgot-password',        [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password/{token}', [AuthController::class, 'resetPassword']);

// ── يحتاج login ───────────────────────────────────
Route::middleware('auth:api')->group(function () {

    Route::post('/auth/logout',          [AuthController::class, 'logout']);
    Route::get('/users/profile',         [UserController::class, 'getProfile']);
    Route::put('/users/profile',         [UserController::class, 'updateProfile']);
    Route::put('/users/change-password', [UserController::class, 'changePassword']);

    // ── Admin فقط ─────────────────────────────────
    Route::middleware('is_admin')->group(function () {
        Route::get('/users',                [UserController::class, 'getAllUsers']);
        Route::put('/users/{id}/validate',  [UserController::class, 'validateUser']);
        Route::put('/users/{id}',           [UserController::class, 'updateUser']);
        Route::delete('/users/{id}',        [UserController::class, 'deleteUser']);
    });
});