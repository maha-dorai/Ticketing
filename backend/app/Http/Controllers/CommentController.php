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
            'contenu' => 'required|string',
            'ticket_id' => 'required|exists:tickets,id'
        ]);

        $ticket = Ticket::findOrFail($validated['ticket_id']);

        // Optionnel : vérifier si l'utilisateur a le droit de commenter ce ticket
        // (ex: admin, ou assigné au projet, ou créateur/developpeur du ticket)

        $comment = Comment::create([
            'contenu' => $validated['contenu'],
            'ticket_id' => $validated['ticket_id'],
            'user_id' => $user->id
        ]);

        // Notifier les personnes concernées
        $usersToNotify = collect([$ticket->testeur_id, $ticket->developpeur_id])
            ->filter()
            ->reject(fn($id) => $id === $user->id)
            ->unique();

        foreach ($usersToNotify as $userId) {
            Notification::create([
                'message' => "Nouveau commentaire sur le ticket '{$ticket->titre}'",
                'user_id' => $userId,
                'ticket_id' => $ticket->id
            ]);
        }

        return response()->json($comment->load('user'), 201);
    }
}
