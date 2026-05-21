<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AutoAssignFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $testeur;
    private User $dev;
    private User $dev2;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'nom' => 'Admin', 'prenom' => 'Test', 'email' => 'admin-flow@test.com',
            'mot_de_passe' => Hash::make('password'), 'role' => 'admin', 'statut' => 'actif',
        ]);
        $this->testeur = User::create([
            'nom' => 'Test', 'prenom' => 'Testeur', 'email' => 'testeur-flow@test.com',
            'mot_de_passe' => Hash::make('password'), 'role' => 'testeur', 'statut' => 'actif',
        ]);
        $this->dev = User::create([
            'nom' => 'Dev', 'prenom' => 'One', 'email' => 'dev1-flow@test.com',
            'mot_de_passe' => Hash::make('password'), 'role' => 'developpeur', 'statut' => 'actif',
            'github_link' => 'https://github.com/dev1',
        ]);
        $this->dev2 = User::create([
            'nom' => 'Dev', 'prenom' => 'Two', 'email' => 'dev2-flow@test.com',
            'mot_de_passe' => Hash::make('password'), 'role' => 'developpeur', 'statut' => 'actif',
            'github_link' => 'https://github.com/dev2',
        ]);

        $this->project = Project::create([
            'nom' => 'Projet Flow Test', 'description' => 'Test', 'statut' => 'ouvert',
            'created_by' => $this->admin->id,
        ]);
        $this->project->users()->sync([$this->testeur->id, $this->dev->id, $this->dev2->id]);
    }

    private function bearer(User $user): array
    {
        return ['Authorization' => 'Bearer ' . JWTAuth::fromUser($user)];
    }

    public function test_ticket_creation_proposes_least_loaded_dev_without_effective_assignment(): void
    {
        Ticket::create([
            'titre' => 'Existing', 'etat' => 'EN_COURS', 'priorite' => 'BASSE',
            'project_id' => $this->project->id, 'testeur_id' => $this->testeur->id,
            'developpeur_id' => $this->dev->id, 'assignment_status' => 'approved',
        ]);

        $response = $this->withHeaders($this->bearer($this->testeur))
            ->postJson("/api/projects/{$this->project->id}/tickets", [
                'titre' => 'Bug auto-assign', 'description' => 'Test', 'priorite' => 'HAUTE',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('auto_assign.success', true)
            ->assertJsonPath('auto_assign.pending', true)
            ->assertJsonPath('ticket.assignment_status', 'pending')
            ->assertJsonPath('ticket.developpeur_id', null);

        $proposedId = $response->json('ticket.proposed_developpeur_id');
        $this->assertEquals($this->dev2->id, $proposedId);
    }

    public function test_developer_does_not_see_ticket_until_admin_accepts(): void
    {
        $ticket = Ticket::create([
            'titre' => 'Pending ticket', 'etat' => 'OUVERT', 'priorite' => 'BASSE',
            'project_id' => $this->project->id, 'testeur_id' => $this->testeur->id,
            'proposed_developpeur_id' => $this->dev->id, 'assignment_status' => 'pending',
            'developpeur_id' => null,
        ]);

        $this->withHeaders($this->bearer($this->dev))
            ->getJson("/api/projects/{$this->project->id}/tickets")
            ->assertOk()
            ->assertJsonCount(0);

        $this->withHeaders($this->bearer($this->admin))
            ->patchJson("/api/tickets/{$ticket->id}/accept")
            ->assertOk();

        $this->withHeaders($this->bearer($this->dev))
            ->getJson("/api/projects/{$this->project->id}/tickets")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.assignment_status', 'approved');
    }

    public function test_accept_is_idempotent(): void
    {
        $ticket = Ticket::create([
            'titre' => 'Idempotent', 'etat' => 'OUVERT', 'priorite' => 'BASSE',
            'project_id' => $this->project->id, 'testeur_id' => $this->testeur->id,
            'proposed_developpeur_id' => $this->dev->id, 'assignment_status' => 'pending',
        ]);

        $this->withHeaders($this->bearer($this->admin))
            ->patchJson("/api/tickets/{$ticket->id}/accept")
            ->assertOk();

        $this->withHeaders($this->bearer($this->admin))
            ->patchJson("/api/tickets/{$ticket->id}/accept")
            ->assertStatus(409);
    }

    public function test_reject_clears_proposal_for_manual_assign(): void
    {
        $ticket = Ticket::create([
            'titre' => 'Reject me', 'etat' => 'OUVERT', 'priorite' => 'BASSE',
            'project_id' => $this->project->id, 'testeur_id' => $this->testeur->id,
            'proposed_developpeur_id' => $this->dev->id, 'assignment_status' => 'pending',
            'rejected_by' => [],
        ]);

        $this->withHeaders($this->bearer($this->admin))
            ->patchJson("/api/tickets/{$ticket->id}/reject")
            ->assertOk();

        $ticket->refresh();
        $this->assertNull($ticket->developpeur_id);
        $this->assertEquals('rejected', $ticket->assignment_status);
        $this->assertContains($this->dev->id, $ticket->rejected_by);

        $this->withHeaders($this->bearer($this->admin))
            ->patchJson("/api/tickets/{$ticket->id}/reassign", ['developpeur_id' => $this->dev2->id])
            ->assertOk()
            ->assertJsonPath('assignment_status', 'approved')
            ->assertJsonPath('developpeur_id', $this->dev2->id);
    }

    public function test_admin_cannot_create_ticket(): void
    {
        $this->withHeaders($this->bearer($this->admin))
            ->postJson("/api/projects/{$this->project->id}/tickets", ['titre' => 'Nope'])
            ->assertStatus(403);
    }
}
