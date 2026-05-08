<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

// ── Routes publiques (limitées à 5 req/min) ──────────────────────────────────
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/register',               [AuthController::class, 'register']);
    Route::post('/auth/login',                  [AuthController::class, 'login']);
    Route::post('/auth/forgot-password',        [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password/{token}', [AuthController::class, 'resetPassword']);
});

// ── Routes protégées (JWT + statut actif + vérification changement mdp) ──────
Route::middleware(['auth:api', 'check_status', 'force_password_change'])->group(function () {

    Route::post('/auth/logout',          [AuthController::class, 'logout']);
    Route::get('/users/profile',         [UserController::class, 'getProfile']);
    Route::put('/users/profile',         [UserController::class, 'updateProfile']);
    Route::put('/users/change-password', [UserController::class, 'changePassword']);
    Route::put('/users/change-email',    [UserController::class, 'changeEmail']);
    Route::get('/projects',              [ProjectController::class, 'index']);

    // ── Routes Admin (admin + super_admin) ───────────────────────────────────
    Route::middleware('is_admin')->group(function () {
        Route::post('/projects',              [ProjectController::class, 'store']);
        Route::put('/projects/{id}',          [ProjectController::class, 'update']);
        Route::delete('/projects/{id}',       [ProjectController::class, 'destroy']);
        Route::post('/projects/{id}/assign',  [ProjectController::class, 'assignUsers']);

        Route::get('/users',                     [UserController::class, 'getAllUsers']);
        Route::put('/users/{id}/validate',       [UserController::class, 'validateUser']);
        Route::put('/users/{id}/deactivate',     [UserController::class, 'deactivateUser']);
        Route::put('/users/{id}/reactivate',     [UserController::class, 'reactivateUser']); // ✅ Fix Sprint 1
        Route::put('/users/{id}',                [UserController::class, 'updateUser']);
        Route::delete('/users/{id}',             [UserController::class, 'deleteUser']);      // retourne 403
    });

    // ── Routes Super Admin uniquement ────────────────────────────────────────
    Route::middleware('is_super_admin')->group(function () {
        Route::post('/super-admin/admins',          [SuperAdminController::class, 'createAdmin']);
        Route::get('/super-admin/admins',           [SuperAdminController::class, 'listAdmins']);
        Route::put('/super-admin/admins/{id}/revoke', [SuperAdminController::class, 'revokeAdmin']);
    });
});