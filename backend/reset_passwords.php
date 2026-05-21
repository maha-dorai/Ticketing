<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$emails = [
    'admin' => 'maya@gmail.com',
    'testeur' => 'ahlem.elhaj.iset@gmail.com',
    'developpeur' => 'lakhelmay896@gmail.com'
];

foreach ($emails as $role => $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $user->mot_de_passe = Hash::make('password');
        $user->save();
        echo "Reset password for $role ($email) to 'password'\n";
    }
}
