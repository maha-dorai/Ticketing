<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Http\Request;

// ✅ Fix : Pusher private channel authentication
Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:api');

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
    Route::get('/projects/{id}',         [ProjectController::class, 'show']);      // ✅ Fix #3 — route manquante

    // ── Routes Sprint 3 : Tickets (imbriqués dans les projets) ─────────────────
    Route::get('/projects/{projectId}/tickets',        [TicketController::class, 'index']);
    Route::post('/projects/{projectId}/tickets',       [TicketController::class, 'store']);
    Route::get('/tickets/{id}',                        [TicketController::class, 'show']);
    Route::put('/tickets/{id}',                        [TicketController::class, 'update']);
    Route::put('/tickets/{id}/status',                 [TicketController::class, 'changeStatus']);
    Route::put('/tickets/{id}/close',                  [TicketController::class, 'close']);
    
    // ── Routes Auto-assign Flow ────────────────────────────────────────────────
    Route::patch('/tickets/{id}/accept', [App\Http\Controllers\TicketController::class, 'accept']);
    Route::patch('/tickets/{id}/reject', [App\Http\Controllers\TicketController::class, 'reject']);
    Route::patch('/tickets/{id}/reassign', [App\Http\Controllers\TicketController::class, 'reassign']);

    // Stats & Dashboard
    Route::get('/dashboard/stats', [App\Http\Controllers\StatsController::class, 'getAdminDashboardStats']);
    Route::get('/user/stats', [App\Http\Controllers\StatsController::class, 'getUserStats']);
    Route::post('/comments',             [CommentController::class, 'store']);
    Route::put('/comments/{id}',         [CommentController::class, 'update']);
    Route::delete('/comments/{id}',      [CommentController::class, 'destroy']);

    Route::get('/notifications',              [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/read',         [NotificationController::class, 'markAsRead']);

    // ── Routes Admin (chef_de_projet + admin) ────────────────────────────────
    Route::middleware('is_admin')->group(function () {
        Route::post('/projects',              [ProjectController::class, 'store']);
        Route::put('/projects/{id}',          [ProjectController::class, 'update']);
        Route::delete('/projects/{id}',       [ProjectController::class, 'destroy']);
        Route::post('/projects/{id}/assign',  [ProjectController::class, 'assignUsers']);
        Route::get('/projects/{id}/developers/workload', [ProjectController::class, 'getDevelopersWorkload']);
        Route::get('/users',                     [UserController::class, 'getAllUsers']);
        Route::put('/users/{id}/validate',       [UserController::class, 'validateUser']);
        Route::put('/users/{id}/deactivate',     [UserController::class, 'deactivateUser']);
        Route::put('/users/{id}/reactivate',     [UserController::class, 'reactivateUser']); // ✅ Fix Sprint 1
        Route::put('/users/{id}',                [UserController::class, 'updateUser']);
        Route::delete('/users/{id}',             [UserController::class, 'deleteUser']);      // retourne 403
    });

    // ── Routes Admin uniquement ───────────────────────────────────────────────
    Route::middleware('is_full_admin')->group(function () {
        Route::post('/admin/chefs',              [SuperAdminController::class, 'createAdmin']);
        Route::get('/admin/chefs',               [SuperAdminController::class, 'listAdmins']);
        Route::put('/admin/chefs/{id}/revoke',   [SuperAdminController::class, 'revokeAdmin']);
    });
});