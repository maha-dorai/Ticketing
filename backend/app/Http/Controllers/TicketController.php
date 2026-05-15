<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Notification;
use App\Models\User;
use App\Mail\TicketAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class TicketController extends Controller
{
    // ── Helpers ────────────────────────────────────────────────────────────────

    private function notify(int $userId, string $message, int $ticketId): void
    {
        if ($userId) {
            \App\Http\Controllers\NotificationController::createAndBroadcast($userId, $message, $ticketId);
        }
    }

    private function notifyMany(array $userIds, string $message, int $ticketId): void
    {
        foreach (array_unique(array_filter($userIds)) as $userId) {
            $this->notify($userId, $message, $ticketId);
        }
    }

    // ── Index ──────────────────────────────────────────────────────────────────

    public function index($projectId)
    {
        $user    = Auth::user();
        $project = \App\Models\Project::findOrFail($projectId);

        if (!$user->isAdmin() && !$project->users->contains($user->id)) {
            return response()->json(['message' => 'Accès refusé à ce projet'], 403);
        }

        $query = Ticket::with(['testeur', 'developpeur'])->where('project_id', $projectId);

        if ($user->role === 'testeur') {
            $query->where('testeur_id', $user->id);
        }

        if ($user->role === 'developpeur') {
            $query->where('developpeur_id', $user->id);
        }

        return response()->json($query->get(), 200);
    }

    // ── Show ───────────────────────────────────────────────────────────────────

    public function show($id)
    {
        $ticket = Ticket::with(['comments.user', 'project', 'testeur', 'developpeur'])->findOrFail($id);
        return response()->json($ticket, 200);
    }

    // ── Store ──────────────────────────────────────────────────────────────────

    public function store(Request $request, $projectId)
    {
        $user    = Auth::user();
        $project = \App\Models\Project::findOrFail($projectId);

        if ($user->role !== 'testeur' && !$user->isAdmin()) {
            return response()->json(['message' => 'Seuls les testeurs peuvent créer des tickets'], 403);
        }

        if (!$user->isAdmin() && !$project->users->contains($user->id)) {
            return response()->json(['message' => "Vous n'êtes pas membre de ce projet"], 403);
        }

        $validated = $request->validate([
            'titre'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'priorite'       => 'nullable|in:BASSE,MOYENNE,HAUTE,CRITIQUE',
            'developpeur_id' => 'nullable|exists:users,id',
        ]);

        $ticket = Ticket::create([
            'titre'          => $validated['titre'],
            'description'    => $validated['description'] ?? null,
            'priorite'       => $validated['priorite'] ?? 'BASSE',
            'etat'           => 'OUVERT',
            'project_id'     => $projectId,
            'testeur_id'     => $user->id,
            'developpeur_id' => $validated['developpeur_id'] ?? null,
        ]);

        // 🔔 Notifier le développeur assigné
        if ($ticket->developpeur_id) {
            $this->notify(
                $ticket->developpeur_id,
                "🎫 Nouveau ticket assigné : « {$ticket->titre} » — Priorité : {$ticket->priorite}",
                $ticket->id
            );
            // 📧 Email au développeur
            try {
                $dev = User::find($ticket->developpeur_id);
                if ($dev) Mail::to($dev->email)->send(new TicketAssigned($ticket->load('project'), $dev, 'developpeur'));
            } catch (\Exception $e) { /* ne pas bloquer si mail échoue */ }
        }

        return response()->json($ticket, 201);
    }

    // ── Update ─────────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $user   = Auth::user();
        $ticket = Ticket::findOrFail($id);

        if ($user->role !== 'testeur' && !$user->isAdmin()) {
            return response()->json(['message' => 'Seuls les testeurs peuvent modifier des tickets'], 403);
        }

        if ($user->role === 'testeur' && $ticket->testeur_id !== $user->id) {
            return response()->json(['message' => "Non autorisé. Vous n'êtes pas le créateur de ce ticket"], 403);
        }

        $validated = $request->validate([
            'titre'          => 'sometimes|required|string|max:255',
            'description'    => 'nullable|string',
            'priorite'       => 'sometimes|in:BASSE,MOYENNE,HAUTE,CRITIQUE',
            'developpeur_id' => 'nullable|exists:users,id',
        ]);

        $oldDevId = $ticket->developpeur_id;
        $ticket->update($validated);
        $ticket->refresh();

        // 🔔 Notifier le nouveau développeur si changement d'assignation
        if (
            isset($validated['developpeur_id']) &&
            $validated['developpeur_id'] &&
            $validated['developpeur_id'] != $oldDevId
        ) {
            $this->notify(
                $ticket->developpeur_id,
                "👤 Le ticket « {$ticket->titre} » vous a été assigné.",
                $ticket->id
            );
            // 📧 Email au nouveau développeur
            try {
                $dev = User::find($ticket->developpeur_id);
                if ($dev) Mail::to($dev->email)->send(new TicketAssigned($ticket->load('project'), $dev, 'developpeur'));
            } catch (\Exception $e) { /* ne pas bloquer si mail échoue */ }

            // Notifier l'ancien développeur s'il y en avait un
            if ($oldDevId) {
                $this->notify(
                    $oldDevId,
                    "🔄 Le ticket « {$ticket->titre} » ne vous est plus assigné.",
                    $ticket->id
                );
            }
        }

        return response()->json($ticket, 200);
    }

    // ── Change Status ──────────────────────────────────────────────────────────

    public function changeStatus(Request $request, $id)
    {
        $user   = Auth::user();
        $ticket = Ticket::findOrFail($id);

        // Seul le développeur assigné peut changer l'état (pas l'admin)
        if ($user->role !== 'developpeur') {
            return response()->json(['message' => "Seuls les développeurs assignés peuvent changer l'état du ticket"], 403);
        }

        if ($ticket->developpeur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé. Ce ticket ne vous est pas assigné'], 403);
        }

        $validated = $request->validate([
            'etat' => 'required|in:OUVERT,EN_COURS,RESOLU',
        ]);

        $oldEtat = $ticket->etat;
        $ticket->update(['etat' => $validated['etat']]);

        $etatLabels = [
            'OUVERT'   => '🟢 Ouvert',
            'EN_COURS' => '🔵 En cours',
            'RESOLU'   => '✅ Résolu',
        ];
        $label = $etatLabels[$validated['etat']] ?? $validated['etat'];

        // 🔔 Notifier le testeur du changement d'état
        $this->notify(
            $ticket->testeur_id,
            "🔄 Le ticket « {$ticket->titre} » est maintenant : {$label}",
            $ticket->id
        );

        return response()->json($ticket, 200);
    }

    // ── Close ──────────────────────────────────────────────────────────────────

    public function close(Request $request, $id)
    {
        $user   = Auth::user();
        $ticket = Ticket::findOrFail($id);

        // ❌ Admin n'a plus le droit de fermer un ticket
        // Seul le testeur créateur ou le développeur assigné peuvent fermer
        $isTesteurCreateur   = $user->role === 'testeur'      && $ticket->testeur_id     === $user->id;
        $isDeveloppeurAssigne = $user->role === 'developpeur' && $ticket->developpeur_id === $user->id;

        if (!$isTesteurCreateur && !$isDeveloppeurAssigne) {
            return response()->json([
                'message' => 'Seul le testeur créateur ou le développeur assigné peuvent fermer ce ticket'
            ], 403);
        }

        $ticket->update(['etat' => 'FERME']);

        // 🔔 Notifier toutes les parties concernées
        $fermePar = "{$user->prenom} {$user->nom}";

        $this->notifyMany(
            array_filter([$ticket->testeur_id, $ticket->developpeur_id], fn($uid) => $uid !== $user->id),
            "🔒 Le ticket « {$ticket->titre} » a été fermé par {$fermePar}.",
            $ticket->id
        );

        return response()->json($ticket, 200);
    }
}