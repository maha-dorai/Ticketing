<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. USERS : ajout super_admin + force_password_change ─────────────
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur','developpeur','admin','super_admin') NOT NULL DEFAULT 'testeur'");

        Schema::table('users', function (Blueprint $table) {
            // Force le changement de mot de passe à la première connexion (pour les admins créés par super_admin)
            $table->boolean('force_password_change')->default(false)->after('github_link');
        });

        // ── 2. PROJECTS : archive au lieu de ferme + created_by ──────────────
        DB::statement("ALTER TABLE projects MODIFY COLUMN statut ENUM('ouvert','en_cours','archive') NOT NULL DEFAULT 'ouvert'");

        Schema::table('projects', function (Blueprint $table) {
            // Qui a créé ce projet (FK vers users)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
        DB::statement("ALTER TABLE projects MODIFY COLUMN statut ENUM('ouvert','en_cours','ferme') NOT NULL DEFAULT 'ouvert'");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('force_password_change');
        });
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur','developpeur','admin') NOT NULL DEFAULT 'testeur'");
    }
};