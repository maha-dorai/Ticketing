<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AutoAssignService;
use Illuminate\Support\Facades\Hash;

$passed = 0;
$failed = 0;

function assert_true(bool $cond, string $label): void
{
    global $passed, $failed;
    if ($cond) {
        echo "  ✓ {$label}\n";
        $passed++;
    } else {
        echo "  ✗ {$label}\n";
        $failed++;
    }
}

echo "=== Test flux auto-assignation ===\n\n";

$admin = User::firstOrCreate(
    ['email' => 'admin-flow@test.com'],
    ['nom' => 'Ad', 'prenom' => 'min', 'role' => 'admin', 'statut' => 'actif', 'mot_de_passe' => Hash::make('password')]
);
$testeur = User::firstOrCreate(
    ['email' => 'testeur-flow@test.com'],
    ['nom' => 'Te', 'prenom' => 'steur', 'role' => 'testeur', 'statut' => 'actif', 'mot_de_passe' => Hash::make('password')]
);
$dev1 = User::firstOrCreate(
    ['email' => 'dev1-flow@test.com'],
    ['nom' => 'Dev', 'prenom' => 'One', 'role' => 'developpeur', 'statut' => 'actif', 'mot_de_passe' => Hash::make('password'), 'github_link' => 'https://github.com/d1']
);
$dev2 = User::firstOrCreate(
    ['email' => 'dev2-flow@test.com'],
    ['nom' => 'Dev', 'prenom' => 'Two', 'role' => 'developpeur', 'statut' => 'actif', 'mot_de_passe' => Hash::make('password'), 'github_link' => 'https://github.com/d2']
);

$project = Project::firstOrCreate(
    ['nom' => 'Projet Flow Test Auto'],
    ['description' => 'Test', 'statut' => 'ouvert', 'created_by' => $admin->id]
);
$project->users()->syncWithoutDetaching([$testeur->id, $dev1->id, $dev2->id]);

// Nettoyer tickets de test précédents
Ticket::where('project_id', $project->id)->where('titre', 'like', '[TEST]%')->delete();

// Dev1 a 1 ticket actif approuvé, dev2 aucun → dev2 doit être proposé
Ticket::create([
    'titre' => '[TEST] charge dev1',
    'etat' => 'EN_COURS',
    'priorite' => 'BASSE',
    'project_id' => $project->id,
    'testeur_id' => $testeur->id,
    'developpeur_id' => $dev1->id,
    'assignment_status' => 'approved',
]);

$ticket = Ticket::create([
    'titre' => '[TEST] auto-assign flow',
    'description' => 'Test script',
    'etat' => 'OUVERT',
    'priorite' => 'HAUTE',
    'project_id' => $project->id,
    'testeur_id' => $testeur->id,
    'developpeur_id' => null,
    'proposed_developpeur_id' => null,
    'assignment_status' => 'none',
    'force_assigned' => false,
    'rejected_by' => [],
]);

$service = new AutoAssignService();
$service->assign($ticket->load('testeur'));
$ticket->refresh();

echo "1. Création + auto-assign\n";
assert_true($ticket->assignment_status === 'pending', 'status = pending');
assert_true($ticket->developpeur_id === null, 'developpeur_id null (pas encore effectif)');
assert_true($ticket->proposed_developpeur_id === $dev2->id, 'propose dev2 (moins chargé)');

echo "\n2. Visibilité développeur avant validation\n";
$devSees = Ticket::where('project_id', $project->id)
    ->where('developpeur_id', $dev2->id)
    ->where('assignment_status', 'approved')
    ->where('titre', '[TEST] auto-assign flow')
    ->count();
assert_true($devSees === 0, 'dev2 ne voit pas le ticket pending');

echo "\n3. Validation admin (accept)\n";
$ticket->update([
    'developpeur_id' => $ticket->proposed_developpeur_id,
    'proposed_developpeur_id' => null,
    'assignment_status' => 'approved',
]);
$ticket->refresh();

$devSeesAfter = Ticket::where('project_id', $project->id)
    ->where('developpeur_id', $dev2->id)
    ->where('assignment_status', 'approved')
    ->where('titre', '[TEST] auto-assign flow')
    ->count();
assert_true($devSeesAfter === 1, 'dev2 voit le ticket après approval');

echo "\n4. Reject + reassign manuel\n";
$ticket2 = Ticket::create([
    'titre' => '[TEST] reject flow',
    'etat' => 'OUVERT',
    'priorite' => 'BASSE',
    'project_id' => $project->id,
    'testeur_id' => $testeur->id,
    'proposed_developpeur_id' => $dev1->id,
    'assignment_status' => 'pending',
    'rejected_by' => [],
]);
$rejected = $ticket2->rejected_by ?? [];
$rejected[] = $dev1->id;
$ticket2->update([
    'developpeur_id' => null,
    'proposed_developpeur_id' => null,
    'assignment_status' => 'rejected',
    'rejected_by' => $rejected,
]);
$ticket2->update([
    'developpeur_id' => $dev2->id,
    'assignment_status' => 'approved',
    'force_assigned' => true,
]);
$ticket2->refresh();
assert_true($ticket2->assignment_status === 'approved' && $ticket2->developpeur_id === $dev2->id, 'reassign manuel approuvé immédiatement');

echo "\n=== Résultat: {$passed} OK, {$failed} échec(s) ===\n";
exit($failed > 0 ? 1 : 0);
