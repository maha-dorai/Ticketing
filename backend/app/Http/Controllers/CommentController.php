<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'contenu'   => 'required|string',
            'ticket_id' => 'required|exists:tickets,id'
        ]);

        $ticket = Ticket::findOrFail($validated['ticket_id']);

        $comment = Comment::create([
            'contenu'   => $validated['contenu'],
            'ticket_id' => $validated['ticket_id'],
            'user_id'   => $user->id
        ]);

        // Notifier les personnes concernées
        $usersToNotify = collect([$ticket->testeur_id, $ticket->developpeur_id])
            ->filter()
            ->reject(fn($id) => $id === $user->id)
            ->unique();

foreach ($usersToNotify as $userId) {
    // ❌ قبل: Notification::create (لا يبث real-time)
    // ✅ بعد:
    \App\Http\Controllers\NotificationController::createAndBroadcast(
        $userId,
        "💬 Nouveau commentaire sur le ticket « {$ticket->titre} »",
        $ticket->id
    );
}

        return response()->json($comment->load('user'), 201);
    }

    public function update(Request $request, $id)
    {
        $user    = Auth::user();
        $comment = Comment::findOrFail($id);

        if ($comment->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé. Vous ne pouvez modifier que vos propres commentaires.'], 403);
        }

        $validated = $request->validate([
            'contenu' => 'required|string'
        ]);

        $comment->update(['contenu' => $validated['contenu']]);

        return response()->json($comment->load('user'), 200);
    }

    public function destroy($id)
    {
        $user    = Auth::user();
        $comment = Comment::findOrFail($id);

        if ($comment->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Non autorisé. Vous ne pouvez supprimer que vos propres commentaires.'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Commentaire supprimé.'], 200);
    }
}