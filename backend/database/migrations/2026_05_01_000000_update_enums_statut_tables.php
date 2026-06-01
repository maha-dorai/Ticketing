<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('statut', ['en_attente', 'actif', 'rejete', 'desactive'])
                  ->default('en_attente')
                  ->change();
        });

        Schema::table('projects', function (Blueprint $table) {
            // ✅ CORRIGÉ : ouvert/en_cours/ferme selon CDC §4.1
            // Supprimé : termine (n'existe pas dans le CDC)
            $table->enum('statut', ['ouvert', 'en_cours', 'ferme'])
                  ->default('ouvert')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('statut', ['en_attente', 'actif', 'rejete'])
                  ->default('en_attente')
                  ->change();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->enum('statut', ['en_cours', 'termine'])
                  ->default('en_cours')
                  ->change();
        });
    }
};