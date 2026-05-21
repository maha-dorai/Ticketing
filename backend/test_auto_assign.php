<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use App\Models\User;
use App\Models\Ticket;
use App\Services\AutoAssignService;
use Illuminate\Support\Facades\Hash;

$admin = User::firstOrCreate(['email' => 'admin@test.com'], ['nom' => 'Ad', 'prenom' => 'min', 'role' => 'admin', 'statut' => 'actif', 'mot_de_passe' => Hash::make('password')]);
$project = Project::firstOrCreate(['nom' => 'Test Project Auto'], ['description' => 'Test', 'statut' => 'ouvert', 'created_by' => $admin->id]);
$testeur = User::firstOrCreate(['email' => 'testeur@test.com'], ['nom' => 'Test', 'prenom' => 'Testeur', 'role' => 'testeur', 'statut' => 'actif', 'mot_de_passe' => Hash::make('password')]);
$dev = User::firstOrCreate(['email' => 'dev1@test.com'], ['nom' => 'Dev', 'prenom' => 'One', 'role' => 'developpeur', 'statut' => 'actif', 'mot_de_passe' => Hash::make('password')]);

$project->users()->syncWithoutDetaching([$testeur->id, $dev->id]);

$ticket = Ticket::create(['titre' => 'Test Bug', 'etat' => 'OUVERT', 'priorite' => 'BASSE', 'project_id' => $project->id, 'testeur_id' => $testeur->id]);

$service = new AutoAssignService();
$service->assign($ticket);

echo "Ticket ID: " . $ticket->id . PHP_EOL;
echo "Assigned Dev ID: " . $ticket->developpeur_id . PHP_EOL;
echo "Expected Dev ID: " . $dev->id . PHP_EOL;
