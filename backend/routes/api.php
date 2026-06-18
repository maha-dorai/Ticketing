<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ChefDeProjetController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Http\Request;

// [Sprint 4] - Authentification pour les WebSockets (Pusher)
// Permet de sécuriser les canaux de diffusion pour les notifications en temps réel.
Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:api');

// ── [Sprint 1] Routes publiques ──────────────────────────────────
// Accessible par tous. Le middleware 'throttle' limite à 5 requêtes par minute pour éviter le spam.
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/register',               [AuthController::class, 'register']); // Création de compte (en_attente)
    Route::post('/auth/login',                  [AuthController::class, 'login']);    // Connexion et génération du JWT
    Route::post('/auth/forgot-password',        [AuthController::class, 'forgotPassword']); // Mot de passe oublié
    Route::post('/auth/reset-password/{token}', [AuthController::class, 'resetPassword']);  // Réinitialisation du mot de passe
});

// ── Routes protégées (Accès réservé aux utilisateurs connectés) ──────
// 'auth:api' : Vérifie la validité du JWT
// 'check_status' : [Sprint 1] Vérifie que l'utilisateur est 'actif'
// 'force_password_change' : [Sprint 1] Bloque l'accès si l'admin a exigé un changement de mot de passe
Route::middleware(['auth:api', 'check_status', 'force_password_change'])->group(function () {

    // [Sprint 1] - Déconnexion et Gestion du Profil
    Route::post('/auth/logout',          [AuthController::class, 'logout']); // Invalide le JWT
    Route::get('/users/profile',         [UserController::class, 'getProfile']);
    Route::put('/users/profile',         [UserController::class, 'updateProfile']);
    Route::put('/users/change-password', [UserController::class, 'changePassword']);
    Route::put('/users/change-email',    [UserController::class, 'changeEmail']);
    
    // [Sprint 2] - Consultation des projets
    Route::get('/projects',              [ProjectController::class, 'index']); // Liste des projets visibles par l'utilisateur
    Route::get('/projects/{id}',         [ProjectController::class, 'show']);  // Détails d'un projet

    // ── [Sprint 3] Tickets (Tableau Kanban) ──────────────────────────────────
    Route::get('/projects/{projectId}/tickets',  [TicketController::class, 'index']); // Liste des tickets d'un projet
    Route::post('/projects/{projectId}/tickets', [TicketController::class, 'store']); // Création d'un ticket
    Route::get('/tickets/{id}',                  [TicketController::class, 'show']);  // Détails d'un ticket
    Route::put('/tickets/{id}',                  [TicketController::class, 'update']);// Modification d'un ticket
    Route::put('/tickets/{id}/status',           [TicketController::class, 'changeStatus']); // Drag&Drop (Changement de colonne Kanban)
    Route::put('/tickets/{id}/close',            [TicketController::class, 'close']); // Clôture d'un ticket (Testeur)
    Route::post('/tickets/{id}/log-time',        [TicketController::class, 'logTime']); // Suivi du temps de travail
    
    // [Sprint 4] - Analyse IA des tickets avec Groq Llama 3
    Route::post('/tickets/{id}/analyze-ai',       [TicketController::class, 'analyzeAI']); // Analyse IA stockée en BDD
    Route::post('/ai/analyze',                     [TicketController::class, 'analyzePreview']); // Analyse IA en direct (Preview)

    // ── [Sprint 3] Flux d'auto-assignation (Algorithme de charge) ──────────────
    Route::patch('/tickets/{id}/accept',   [TicketController::class, 'accept']);   // Le développeur accepte l'assignation
    Route::patch('/tickets/{id}/reject',   [TicketController::class, 'reject']);   // Le développeur refuse
    Route::patch('/tickets/{id}/reassign', [TicketController::class, 'reassign']); // L'algorithme cherche le dev le moins chargé

    // ── [Sprint 4] Stats & Tableau de bord KPI ───────────────────────────────
    Route::get('/dashboard/stats', [App\Http\Controllers\StatsController::class, 'getAdminDashboardStats']);
    Route::get('/user/stats',      [App\Http\Controllers\StatsController::class, 'getUserStats']);
    
    Route::get('/stats/admin',                [App\Http\Controllers\StatsController::class, 'admin']); // KPI globaux pour l'admin
    Route::get('/stats/manager/{projectId?}', [App\Http\Controllers\StatsController::class, 'manager']); // KPI par projet
    Route::get('/stats/me',                   [App\Http\Controllers\StatsController::class, 'me']); // KPI personnels

    // ── [Sprint 3] Commentaires sur les tickets ────────────────────────────────
    Route::post('/comments',        [CommentController::class, 'store']);
    Route::put('/comments/{id}',    [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

    // ── [Sprint 4] Notifications en temps réel ─────────────────────────────────
    Route::get('/notifications',              [NotificationController::class, 'index']); // Historique
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']); // Compteur (Cloche)
    Route::put('/notifications/read',         [NotificationController::class, 'markAsRead']); // Marquer comme lu

    // ── [Sprint 2] Routes Manager (Admin + Chef de projet) ───────────────────
    Route::middleware('is_manager')->group(function () {
        Route::post('/projects',             [ProjectController::class, 'store']); // Créer un projet
        Route::put('/projects/{id}',         [ProjectController::class, 'update']);// Modifier
        Route::delete('/projects/{id}',      [ProjectController::class, 'destroy']);// Clôturer (Archiver)
        Route::post('/projects/{id}/assign', [ProjectController::class, 'assignUsers']); // Affecter des membres
        Route::get('/projects/{id}/developers/workload', [ProjectController::class, 'getDevelopersWorkload']); // Voir la charge de travail
        
        // Consultation de la liste des utilisateurs pour les affectations
        Route::get('/users',                   [UserController::class, 'getAllUsers']);
        Route::get('/users/{id}',              [UserController::class, 'getUser']);
        Route::put('/users/{id}',              [UserController::class, 'updateUser']);
        Route::delete('/users/{id}',           [UserController::class, 'deleteUser']); // Bloqué par défaut (Traçabilité)
    });

    // ── [Sprint 1] Routes Admin uniquement (Le Grand Manitou) ─────────────────
    Route::middleware('is_admin')->group(function () {
        Route::post('/admin/chefs',            [ChefDeProjetController::class, 'create']); // Créer un Chef de projet
        Route::get('/admin/chefs',             [ChefDeProjetController::class, 'list']);
        Route::put('/admin/chefs/{id}/revoke', [ChefDeProjetController::class, 'revoke']); // Révoquer un Chef
        
        // Modération des membres
        Route::put('/users/{id}/validate',     [UserController::class, 'validateUser']); // Accepter/Rejeter une inscription
        Route::put('/users/{id}/deactivate',   [UserController::class, 'deactivateUser']);// Ban temporaire
        Route::put('/users/{id}/reactivate',   [UserController::class, 'reactivateUser']);// Dé-Ban
    });
});