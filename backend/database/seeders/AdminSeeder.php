<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'nom'          => 'Admin',
            'prenom'       => 'System',
            'email'        => 'admin@platform.com',
            'mot_de_passe' => Hash::make('Admin@1234'),
            'role'         => 'admin',
            'statut'       => 'actif',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}