<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Mail\TicketAssigned;
use App\Services\AutoAssignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class TicketController extends Controller
{
    private function notify(int $userId, string $message, int $ticketId): void
    {
        if ($userId) {
            NotificationController::createAndBroadcast($userId, $message, $ticketId);
        }
    }

    private function notifyMany(array $userIds, string $message, int $ticketId): void
    {
        foreach (array_unique(array_filter($userIds)) as $userId) {
            $this->notify($userId, $message, $ticketId);
        }
    }

    public function index($projectId)
    {
        $user    = Auth::user();
        $project = \App\Models\Project::findOrFail($projectId);

        if (!$user->isManager() && !$project->users->contains($user->id)) {
            return response()->json(['message' => 'Accès refusé à ce projet'], 403);
        }

        $query = Ticket::with(['testeur', 'developpeur', 'proposedDeveloppeur', 'attachments'])
            ->where('project_id', $projectId);

        if ($user->role === 'testeur') {
            $query->where('testeur_id', $user->id);
        }

        if ($user->role === 'developpeur') {
            $query->where('developpeur_id', $user->id)
                ->where('assignment_status', 'approved');
        }

        return response()->json($query->get(), 200);
    }

    public function show($id)
    {
        $ticket = Ticket::with([
            'comments.user',
            'project',
            'testeur',
            'developpeur',
            'proposedDeveloppeur',
            'attachments',
        ])->findOrFail($id);

        return response()->json($ticket, 200);
    }

    public function store(Request $request, $projectId)
    {
        $user    = Auth::user();
        $project = \App\Models\Project::findOrFail($projectId);

        if ($user->role !== 'testeur') {
            return response()->json(['message' => 'Seuls les testeurs peuvent créer des tickets'], 403);
        }

        if (!$project->users->contains($user->id)) {
            return response()->json(['message' => "Vous n'êtes pas membre de ce projet"], 403);
        }

        $validated = $request->validate([
            'titre'        => 'required|string|max:255',
            'etapes'       => 'nullable|string',
            'resultat'     => 'nullable|string',
            'notes'        => 'nullable|string',
            'priorite'     => 'nullable|in:BASSE,MOYENNE,HAUTE,CRITIQUE',
            'temps_estime' => 'required|numeric|min:0',
            'attachments.*'=> 'file|mimes:jpg,jpeg,png,pdf,doc,docx,mp4,mov|max:10240', // max 10MB per file
        ]);

        // Build description
        $description = "";
        if (!empty($validated['etapes'])) {
            $description .= "**Étapes pour reproduire :**\n" . $validated['etapes'] . "\n\n";
        }
        if (!empty($validated['resultat'])) {
            $description .= "**Résultat attendu vs obtenu :**\n" . $validated['resultat'] . "\n\n";
        }
        if (!empty($validated['notes'])) {
            $description .= "**Notes supplémentaires :**\n" . $validated['notes'] . "\n\n";
        }

        $ticket = Ticket::create([
            'titre'                   => $validated['titre'],
            'description'             => trim($description) ?: null,
            'priorite'                => $validated['priorite'] ?? 'BASSE',
            'etat'                    => 'OUVERT',
            'project_id'              => $projectId,
            'testeur_id'              => $user->id,
            'developpeur_id'          => null,
            'proposed_developpeur_id' => null,
            'assignment_status'       => 'none',
            'force_assigned'          => false,
            'rejected_by'             => [],
            'temps_estime'            => $validated['temps_estime'],
            'temps_passe'             => 0,
        ]);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                \App\Models\Attachment::create([
                    'ticket_id' => $ticket->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                ]);
            }
        }

        // 🚀 Si le projet était "ouvert", il passe automatiquement "en_cours"
        if ($project->statut === 'ouvert') {
            $project->update(['statut' => 'en_cours']);
        }

        // 🤖 Lancer l'auto-assignation
        $autoAssignService = new AutoAssignService();
        $autoAssignService->assign($ticket);

        // Reload avec le développeur proposé (ou assigné)
        $ticket = $ticket->fresh(['developpeur', 'proposedDeveloppeur']);
        $dev = $ticket->proposedDeveloppeur ?? $ticket->developpeur;

        return response()->json([
            'ticket'      => $ticket,
            'auto_assign' => $dev ? [
                'success' => true,
                'dev_nom'    => $dev->nom,
                'dev_prenom' => $dev->prenom,
            ] : [
                'success' => false,
                'message' => "Aucun développeur disponible. L'Admin assignera manuellement.",
            ],
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user   = Auth::user();
        $ticket = Ticket::findOrFail($id);

        if ($user->role !== 'testeur') {
            return response()->json(['message' => 'Seuls les testeurs peuvent modifier des tickets'], 403);
        }

        if ($ticket->testeur_id !== $user->id) {
            return response()->json(['message' => "Non autorisé. Vous n'êtes pas le créateur de ce ticket"], 403);
        }

        if (in_array($ticket->etat, ['EN_COURS', 'A_TESTER', 'RECLAMATION', 'VALIDE'])) {
            return response()->json([
                'message' => "Modification impossible : le ticket est déjà « {$ticket->etat} ». Seuls les tickets OUVERTS peuvent être modifiés.",
            ], 403);
        }

        $validated = $request->validate([
            'titre'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'priorite'    => 'sometimes|in:BASSE,MOYENNE,HAUTE,CRITIQUE',
        ]);

        $ticket->update($validated);

        return response()->json($ticket->refresh(), 200);
    }

    public function changeStatus(Request $request, $id)
    {
        $user   = Auth::user();
        $ticket = Ticket::findOrFail($id);

        // ── Transitions autorisées par rôle ───────────────────────────────────
        // Développeur assigné : OUVERT → EN_COURS → A_TESTER
        // Testeur créateur    : A_TESTER → RECLAMATION | A_TESTER → VALIDE
        // Admin/chef          : toute transition
        if ($user->isManager()) {
            return response()->json(['message' => 'Les administrateurs ne peuvent pas modifier l\'état des tickets via le Kanban.'], 403);
        } elseif ($user->role === 'developpeur') {
            if (!$ticket->isAssignmentApproved() || $ticket->developpeur_id !== $user->id) {
                return response()->json(['message' => 'Non autorisé. Ce ticket ne vous est pas assigné.'], 403);
            }
            $allowed = ['OUVERT', 'EN_COURS', 'A_TESTER'];
        } elseif ($user->role === 'testeur') {
            if ($ticket->testeur_id !== $user->id) {
                return response()->json(['message' => "Non autorisé. Vous n'êtes pas le créateur de ce ticket."], 403);
            }
            if ($ticket->etat !== 'A_TESTER') {
                return response()->json(['message' => 'Vous pouvez seulement agir sur un ticket « À tester ».'], 403);
            }
            $allowed = ['RECLAMATION', 'VALIDE'];
        } else {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'etat' => 'required|in:OUVERT,EN_COURS,A_TESTER,RECLAMATION,VALIDE',
        ]);

        if (!in_array($validated['etat'], $allowed)) {
            return response()->json(['message' => 'Transition non autorisée pour votre rôle.'], 403);
        }

        $ticket->update(['etat' => $validated['etat']]);

        $etatLabels = [
            'OUVERT'      => '🟢 À traiter',
            'EN_COURS'    => '🔵 En cours',
            'A_TESTER'    => '🧪 À tester',
            'RECLAMATION' => '⚠️ Réclamation',
            'VALIDE'      => '✅ Validé',
        ];
        $label = $etatLabels[$validated['etat']] ?? $validated['etat'];

        if ($validated['etat'] === 'A_TESTER') {
            $this->notify($ticket->testeur_id,
                "🧪 Le ticket « {$ticket->titre} » est prêt à tester.",
                $ticket->id);
        } elseif ($validated['etat'] === 'RECLAMATION') {
            $this->notify($ticket->developpeur_id,
                "⚠️ Réclamation sur « {$ticket->titre} » — le testeur a rejeté la résolution.",
                $ticket->id);
        } elseif ($validated['etat'] === 'VALIDE') {
            $this->notify($ticket->developpeur_id,
                "✅ Le ticket « {$ticket->titre} » a été validé par le testeur.",
                $ticket->id);
        } else {
            $this->notify($ticket->testeur_id,
                "🔄 Le ticket « {$ticket->titre} » est maintenant : {$label}",
                $ticket->id);
        }

        return response()->json($ticket->fresh(), 200);
    }

    public function close(Request $request, $id)
    {
        // Remplacé par changeStatus avec etat=VALIDE — conservé pour compatibilité
        $request->merge(['etat' => 'VALIDE']);
        return $this->changeStatus($request, $id);
    }

    public function accept(Request $request, $id)
    {
        $user   = Auth::user();
        $ticket = Ticket::findOrFail($id);
        
        if (!$user->isManager()) {
            return response()->json(['message' => 'Non autorisé. Seuls les administrateurs peuvent valider l\'assignation'], 403);
        }

        if ($user->role === 'chef_de_projet' && $ticket->project->created_by !== $user->id) {
            return response()->json(['message' => 'Non autorisé. Ce projet ne vous appartient pas.'], 403);
        }

        if ($ticket->assignment_status === 'approved') {
            return response()->json(['message' => 'Cette assignation a déjà été validée.'], 409);
        }

        if ($ticket->assignment_status !== 'pending' || !$ticket->proposed_developpeur_id) {
            return response()->json(['message' => 'Aucune assignation en attente de validation.'], 400);
        }

        $dev = User::findOrFail($ticket->proposed_developpeur_id);

        $ticket->update([
            'developpeur_id'          => $dev->id,
            'proposed_developpeur_id' => null,
            'assignment_status'       => 'approved',
        ]);

        \App\Models\Notification::where('ticket_id', $ticket->id)
            ->where('message', 'LIKE', '%validation%')
            ->delete();

        $devMessage = "🎫 Nouveau ticket assigné et validé par l'Admin : « {$ticket->titre} » — Priorité : {$ticket->priorite}";
        if ($ticket->force_assigned) {
            $devMessage = "🚨 URGENT: Ticket CRITIQUE assigné et validé : « {$ticket->titre} »";
        }

        $this->notify($dev->id, $devMessage, $ticket->id);

        try {
            Mail::to($dev->email)->send(new TicketAssigned($ticket->load('project'), $dev, 'developpeur'));
        } catch (\Exception $e) {
        }

        $this->notify(
            $ticket->testeur_id,
            "✅ Votre ticket « {$ticket->titre} » a été assigné à {$dev->prenom} {$dev->nom} (validation admin).",
            $ticket->id
        );

        return response()->json([
            'message' => 'Assignation validée.',
            'ticket'  => $ticket->fresh(['developpeur']),
        ], 200);
    }

    public function reject(Request $request, $id)
    {
        \Log::info("Reject called for ticket $id");
        $user   = Auth::user();
        $ticket = Ticket::findOrFail($id);

        if (!$user->isManager()) {
            \Log::info("User not admin");
            return response()->json(['message' => 'Non autorisé. Seuls les administrateurs peuvent refuser l\'assignation'], 403);
        }

        if ($user->role === 'chef_de_projet' && $ticket->project->created_by !== $user->id) {
            return response()->json(['message' => 'Non autorisé. Ce projet ne vous appartient pas.'], 403);
        }

        if ($ticket->assignment_status === 'approved') {
            \Log::info("Ticket already approved");
            return response()->json(['message' => 'Impossible de refuser une assignation déjà validée.'], 409);
        }

        if ($ticket->assignment_status !== 'pending' || !$ticket->proposed_developpeur_id) {
            \Log::info("Not pending or no proposed dev. Status: {$ticket->assignment_status}, ProposedDev: {$ticket->proposed_developpeur_id}");
            return response()->json(['message' => 'Aucune assignation en attente de validation.'], 400);
        }

        $rejectedBy = $ticket->rejected_by ?? [];
        $devId      = $ticket->proposed_developpeur_id;

        if (!in_array($devId, $rejectedBy)) {
            $rejectedBy[] = $devId;
        }

        try {
            $ticket->update([
                'developpeur_id'          => null,
                'proposed_developpeur_id' => null,
                'assignment_status'       => 'rejected',
                'rejected_by'             => $rejectedBy,
            ]);

            \App\Models\Notification::where('ticket_id', $ticket->id)
                ->where('message', 'LIKE', '%validation%')
                ->delete();

            \Log::info("Ticket updated successfully");
        } catch (\Exception $e) {
            \Log::error("Error updating ticket: " . $e->getMessage());
            throw $e;
        }

        return response()->json([
            'message' => 'Assignation refusée. Veuillez assigner manuellement un développeur.',
            'ticket'  => $ticket,
        ], 200);
    }

    public function reassign(Request $request, $id)
    {
        $request->validate(['developpeur_id' => 'required|exists:users,id']);

        $user   = Auth::user();
        $ticket = Ticket::findOrFail($id);

        if (!$user->isManager()) {
            return response()->json(['message' => 'Seuls les administrateurs ou les chefs de projet peuvent assigner un développeur'], 403);
        }

        if ($user->role === 'chef_de_projet' && $ticket->project->created_by !== $user->id) {
            return response()->json(['message' => 'Non autorisé. Ce projet ne vous appartient pas.'], 403);
        }

        $validated = $request->validate([
            'developpeur_id' => 'required|exists:users,id',
        ]);

        $dev = User::findOrFail($validated['developpeur_id']);

        if ($dev->role !== 'developpeur') {
            return response()->json(['message' => 'L\'utilisateur sélectionné n\'est pas un développeur'], 400);
        }

        $ticket->update([
            'developpeur_id'          => $dev->id,
            'proposed_developpeur_id' => null,
            'assignment_status'       => 'approved',
            'force_assigned'          => true,
        ]);

        $this->notify(
            $dev->id,
            "🎫 Un administrateur vous a assigné le ticket « {$ticket->titre} ».",
            $ticket->id
        );

        try {
            Mail::to($dev->email)->send(new TicketAssigned($ticket->load('project'), $dev, 'developpeur'));
        } catch (\Exception $e) {
        }

        $this->notify(
            $ticket->testeur_id,
            "✅ Votre ticket « {$ticket->titre} » a été assigné à {$dev->prenom} {$dev->nom}.",
            $ticket->id
        );

        return response()->json($ticket->fresh(['developpeur']), 200);
    }

    public function logTime(Request $request, $id)
    {
        $user   = Auth::user();
        $ticket = Ticket::findOrFail($id);

        if ($user->role !== 'developpeur' || $ticket->developpeur_id !== $user->id) {
            return response()->json(['message' => 'Seul le développeur assigné peut enregistrer du temps'], 403);
        }

        $validated = $request->validate([
            'temps_ajoute' => 'required|numeric|min:0.1',
        ]);

        $ticket->temps_passe += $validated['temps_ajoute'];
        $ticket->save();

        $this->notify($ticket->testeur_id, "⏱️ Du temps a été enregistré sur le ticket « {$ticket->titre} »", $ticket->id);

        return response()->json(['message' => 'Temps enregistré avec succès', 'ticket' => $ticket], 200);
    }
}