<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return response()->json(Ticket::with(['project', 'testeur', 'developpeur'])->get(), 200);
        }

        if ($user->role === 'testeur') {
            return response()->json(Ticket::with(['project', 'developpeur'])->where('testeur_id', $user->id)->get(), 200);
        }

        if ($user->role === 'developpeur') {
            return response()->json(Ticket::with(['project', 'testeur'])->where('developpeur_id', $user->id)->get(), 200);
        }

        return response()->json([], 200);
    }

    public function show($id)
    {
        $ticket = Ticket::with(['comments.user', 'project', 'testeur', 'developpeur'])->findOrFail($id);
        return response()->json($ticket, 200);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role !== 'testeur' && !$user->isAdmin()) {
            return response()->json(['message' => 'Seuls les testeurs peuvent créer des tickets'], 403);
        }

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priorite' => 'nullable|in:BASSE,MOYENNE,HAUTE,CRITIQUE',
            'project_id' => 'required|exists:projects,id',
            'developpeur_id' => 'nullable|exists:users,id'
        ]);

        $ticket = Ticket::create([
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'priorite' => $validated['priorite'] ?? 'BASSE',
            'etat' => 'OUVERT',
            'project_id' => $validated['project_id'],
            'testeur_id' => $user->id,
            'developpeur_id' => $validated['developpeur_id'] ?? null
        ]);

        if ($ticket->developpeur_id) {
            Notification::create([
                'message' => "Un nouveau ticket vous a été assigné: {$ticket->titre}",
                'user_id' => $ticket->developpeur_id,
                'ticket_id' => $ticket->id
            ]);
        }

        return response()->json($ticket, 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $ticket = Ticket::findOrFail($id);

        if ($user->role !== 'testeur' && !$user->isAdmin()) {
            return response()->json(['message' => 'Seuls les testeurs peuvent modifier des tickets'], 403);
        }

        if ($user->role === 'testeur' && $ticket->testeur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé. Vous n\'êtes pas le créateur de ce ticket'], 403);
        }

        $validated = $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'priorite' => 'sometimes|in:BASSE,MOYENNE,HAUTE,CRITIQUE',
            'developpeur_id' => 'nullable|exists:users,id'
        ]);

        $ticket->update($validated);

        return response()->json($ticket, 200);
    }

    public function changeStatus(Request $request, $id)
    {
        $user = Auth::user();
        $ticket = Ticket::findOrFail($id);

        if ($user->role !== 'developpeur' && !$user->isAdmin()) {
            return response()->json(['message' => 'Seuls les développeurs peuvent changer l\'état du ticket'], 403);
        }

        if ($user->role === 'developpeur' && $ticket->developpeur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé. Ce ticket ne vous est pas assigné'], 403);
        }

        $validated = $request->validate([
            'etat' => 'required|in:OUVERT,EN_COURS,RESOLU,FERME'
        ]);

        $ticket->update(['etat' => $validated['etat']]);

        Notification::create([
            'message' => "Le ticket '{$ticket->titre}' a changé d'état vers {$validated['etat']}",
            'user_id' => $ticket->testeur_id,
            'ticket_id' => $ticket->id
        ]);

        return response()->json($ticket, 200);
    }

    public function close(Request $request, $id)
    {
        $user = Auth::user();
        $ticket = Ticket::findOrFail($id);

        if ($user->role !== 'testeur' && !$user->isAdmin()) {
            return response()->json(['message' => 'Seuls les testeurs ou admins peuvent fermer un ticket'], 403);
        }

        if ($user->role === 'testeur' && $ticket->testeur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé. Vous n\'êtes pas le créateur de ce ticket'], 403);
        }

        $ticket->update(['etat' => 'FERME']);

        if ($ticket->developpeur_id) {
            Notification::create([
                'message' => "Le ticket '{$ticket->titre}' a été fermé.",
                'user_id' => $ticket->developpeur_id,
                'ticket_id' => $ticket->id
            ]);
        }

        return response()->json($ticket, 200);
    }
}
