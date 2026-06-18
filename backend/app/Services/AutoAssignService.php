<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Http\Controllers\NotificationController;

class AutoAssignService
{
    /**
     * [Sprint 3] L'Algorithme d'Auto-Assignation Intelligente.
     * Cette fonction analyse la charge de travail (workload) de tous les développeurs du projet,
     * et propose le ticket à celui qui a le moins de travail "en cours".
     * Note: La proposition nécessite l'approbation d'un Admin/Chef.
     */
    public function assign(Ticket $ticket): void
    {
        $project = $ticket->project;
        $testeur = $ticket->testeur;

        // 1. Récupérer uniquement les développeurs ACTIFS faisant partie du projet
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

        // Le tableau rejected_by contient les ID des développeurs qui ont déjà été refusés par l'admin pour ce ticket
        $rejectedBy = $ticket->rejected_by ?? [];

        $availableDevelopers = [];
        
        // 2. Calculer la "Charge de travail" (Workload) pour chaque développeur
        foreach ($developers as $dev) {
            // Ne pas reproposer un développeur déjà rejeté
            if (in_array($dev->id, $rejectedBy)) {
                continue;
            }

            // Compter ses tickets NON TERMINÉS (Ouverts ou En Cours) validés pour lui
            $activeTicketsCount = Ticket::where('developpeur_id', $dev->id)
                ->where('assignment_status', 'approved')
                ->whereIn('etat', ['OUVERT', 'EN_COURS'])
                ->count();

            $availableDevelopers[] = [
                'developer' => $dev,
                'count'     => $activeTicketsCount, // Sa charge actuelle
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

        // 3. Trier les développeurs par charge de travail croissante (le moins chargé en 1er)
        // La fonction usort trie le tableau $availableDevelopers en utilisant une comparaison `<=>`
        usort($availableDevelopers, fn ($a, $b) => $a['count'] <=> $b['count']);

        // 4. Sélectionner le gagnant (le 1er du tableau)
        $selectedDeveloper = $availableDevelopers[0]['developer'];
        
        // Règle de priorité Critique : Un ticket critique force un certain bypass (traité en amont dans TicketController)
        $forceAssigned     = ($ticket->priorite === 'CRITIQUE');

        // 5. Sauvegarder la PROPOSITION (pending), le ticket n'est pas encore officiellement à lui
        $ticket->update([
            'developpeur_id'          => null, // Toujours nul tant que l'admin n'a pas dit OUI
            'proposed_developpeur_id' => $selectedDeveloper->id,
            'assignment_status'       => 'pending',
            'force_assigned'          => $forceAssigned,
        ]);

        // 6. Alerter l'admin qu'une décision l'attend
        $this->notifyAdminsNewTicket($ticket, $testeur, $selectedDeveloper);
    }

    /**
     * Utilitaire pour envoyer les requêtes d'approbation aux Managers
     */
    private function notifyAdminsNewTicket(
        Ticket $ticket,
        ?User $testeur,
        ?User $proposedDev,
        ?string $failureReason = null
    ): void {
        $admins = User::where('role', 'admin')->where('statut', 'actif')->get();
        $chef = User::where('id', $ticket->project->created_by)
                    ->where('role', 'chef_de_projet')
                    ->where('statut', 'actif')
                    ->first();
        
        $managersToNotify = $admins;
        if ($chef) {
            $managersToNotify->push($chef);
        }

        $testeurName = $testeur ? "{$testeur->prenom} {$testeur->nom}" : 'Un testeur';

        if ($proposedDev) {
            $message = "🎫 Nouveau ticket « {$ticket->titre} » créé par {$testeurName}. "
                . "Assignation proposée par l'algorithme : {$proposedDev->prenom} {$proposedDev->nom} — en attente de votre validation.";
            if ($ticket->force_assigned) {
                $message = "🚨 Nouveau ticket CRITIQUE « {$ticket->titre} » par {$testeurName}. "
                    . "Assignation proposée : {$proposedDev->prenom} {$proposedDev->nom} — validation requise.";
            }
        } else {
            $message = "🎫 Nouveau ticket « {$ticket->titre} » créé par {$testeurName}. "
                . "Assignation automatique impossible. {$failureReason}";
        }

        // Pousse les notifications WebSocket (Pusher)
        foreach ($managersToNotify as $admin) {
            NotificationController::createAndBroadcast($admin->id, $message, $ticket->id);
        }
    }
}