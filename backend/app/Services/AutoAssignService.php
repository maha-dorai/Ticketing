<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Http\Controllers\NotificationController;

class AutoAssignService
{
    /**
     * Propose the least-loaded developer (pending admin validation).
     * Does not set developpeur_id — effective assignment happens on admin accept.
     */
    public function assign(Ticket $ticket): void
    {
        $project = $ticket->project;
        $testeur = $ticket->testeur;

        $developers = $project->users()
            ->where('role', 'developpeur')
            ->where('statut', 'actif')
            ->get();

        if ($developers->isEmpty()) {
            $ticket->update([
                'proposed_developpeur_id' => null,
                'assignment_status'       => 'none',
            ]);
            $this->notifyAdminsNewTicket($ticket, $testeur, null, "Aucun développeur actif dans ce projet.");
            return;
        }

        $rejectedBy = $ticket->rejected_by ?? [];

        $availableDevelopers = [];
        foreach ($developers as $dev) {
            if (in_array($dev->id, $rejectedBy)) {
                continue;
            }

            $activeTicketsCount = Ticket::where('developpeur_id', $dev->id)
                ->where('assignment_status', 'approved')
                ->whereIn('etat', ['OUVERT', 'EN_COURS'])
                ->count();

            $availableDevelopers[] = [
                'developer' => $dev,
                'count'     => $activeTicketsCount,
            ];
        }

        if (empty($availableDevelopers)) {
            $ticket->update([
                'proposed_developpeur_id' => null,
                'assignment_status'       => 'none',
            ]);
            $this->notifyAdminsNewTicket(
                $ticket,
                $testeur,
                null,
                'Tous les développeurs du projet ont déjà été refusés pour ce ticket ou aucun n\'est disponible.'
            );
            return;
        }

        usort($availableDevelopers, fn ($a, $b) => $a['count'] <=> $b['count']);

        $selectedDeveloper = $availableDevelopers[0]['developer'];
        $forceAssigned     = ($ticket->priorite === 'CRITIQUE');

        $ticket->update([
            'developpeur_id'          => null,
            'proposed_developpeur_id' => $selectedDeveloper->id,
            'assignment_status'       => 'pending',
            'force_assigned'          => $forceAssigned,
        ]);

        $this->notifyAdminsNewTicket($ticket, $testeur, $selectedDeveloper);
    }

    private function notifyAdminsNewTicket(
        Ticket $ticket,
        ?User $testeur,
        ?User $proposedDev,
        ?string $failureReason = null
    ): void {
        $admins = User::whereIn('role', ['admin', 'super_admin'])->where('statut', 'actif')->get();

        $testeurName = $testeur
            ? "{$testeur->prenom} {$testeur->nom}"
            : 'Un testeur';

        if ($proposedDev) {
            $message = "🎫 Nouveau ticket « {$ticket->titre} » créé par {$testeurName}. "
                . "Assignation proposée : {$proposedDev->prenom} {$proposedDev->nom} — en attente de votre validation.";
            if ($ticket->force_assigned) {
                $message = "🚨 Nouveau ticket CRITIQUE « {$ticket->titre} » par {$testeurName}. "
                    . "Assignation proposée : {$proposedDev->prenom} {$proposedDev->nom} — validation requise.";
            }
        } else {
            $message = "🎫 Nouveau ticket « {$ticket->titre} » créé par {$testeurName}. "
                . "Assignation automatique impossible. {$failureReason}";
        }

        foreach ($admins as $admin) {
            NotificationController::createAndBroadcast($admin->id, $message, $ticket->id);
        }
    }
}
