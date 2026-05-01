<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // En SQLite (et Laravel 11), on utilise change() de manière native 
        // au lieu d'utiliser du SQL Brut conçu pour MySQL.
        Schema::table('users', function (Blueprint $table) {
            $table->enum('statut', ['en_attente', 'actif', 'rejete', 'desactive'])->default('en_attente')->change();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->enum('statut', ['en_cours', 'termine', 'ferme'])->default('en_cours')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('statut', ['en_attente', 'actif', 'rejete'])->default('en_attente')->change();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->enum('statut', ['en_cours', 'termine'])->default('en_cours')->change();
        });
    }
};
