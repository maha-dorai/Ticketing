<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::all();
echo "--- LISTE DES COMPTES ---\n";
foreach($users as $u) {
    echo str_pad($u->role, 15) . " | " . str_pad($u->email, 30) . "\n";
}
echo "-------------------------\n";
