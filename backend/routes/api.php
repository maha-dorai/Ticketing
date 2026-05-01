<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

// Public routes with Throttle (max 5 requests per minute)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/register',               [AuthController::class, 'register']);
    Route::post('/auth/login',                  [AuthController::class, 'login']);
    Route::post('/auth/forgot-password',        [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password/{token}', [AuthController::class, 'resetPassword']);
});

// Protected routes (require auth + check_status)
Route::middleware(['auth:api', 'check_status'])->group(function () {
    Route::post('/auth/logout',          [AuthController::class, 'logout']);
    Route::get('/users/profile',         [UserController::class, 'getProfile']);
    Route::put('/users/profile',         [UserController::class, 'updateProfile']);
    Route::put('/users/change-password', [UserController::class, 'changePassword']);
    Route::get('/projects',              [ProjectController::class, 'index']);

    // Admin routes
    Route::middleware('is_admin')->group(function () {
        Route::post('/projects',              [ProjectController::class, 'store']);
        Route::put('/projects/{id}',          [ProjectController::class, 'update']);
        Route::delete('/projects/{id}',       [ProjectController::class, 'destroy']);
        Route::post('/projects/{id}/assign',  [ProjectController::class, 'assignUsers']);

        Route::get('/users',                  [UserController::class, 'getAllUsers']);
        Route::put('/users/{id}/validate',    [UserController::class, 'validateUser']);
        Route::put('/users/{id}/deactivate',  [UserController::class, 'deactivateUser']);
        Route::put('/users/{id}',             [UserController::class, 'updateUser']);
        Route::delete('/users/{id}',          [UserController::class, 'deleteUser']);
    });
});