<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Désactiver le mode strict MySQL pour cette session
        DB::statement("SET SESSION sql_mode = ''");

        // ── 1. USERS : ajout super_admin ──────────────────────────────────────
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur','developpeur','admin','super_admin') NOT NULL DEFAULT 'testeur'");

        // ── 2. PROJECTS statut : 3 étapes ─────────────────────────────────────
        DB::statement("ALTER TABLE projects MODIFY COLUMN statut ENUM('ouvert','en_cours','ferme','archive') NOT NULL DEFAULT 'ouvert'");
        DB::statement("UPDATE projects SET statut = 'archive' WHERE statut = 'ferme'");
        DB::statement("ALTER TABLE projects MODIFY COLUMN statut ENUM('ouvert','en_cours','archive') NOT NULL DEFAULT 'ouvert'");

        // ── 3. PROJECTS : created_by ──────────────────────────────────────────
        if (!Schema::hasColumn('projects', 'created_by')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('statut');
            });
        }
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");
        DB::statement("UPDATE projects SET statut = 'ferme' WHERE statut = 'archive'");
        DB::statement("ALTER TABLE projects MODIFY COLUMN statut ENUM('ouvert','en_cours','ferme','archive') NOT NULL DEFAULT 'ouvert'");
        DB::statement("ALTER TABLE projects MODIFY COLUMN statut ENUM('ouvert','en_cours','ferme') NOT NULL DEFAULT 'ouvert'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur','developpeur','admin') NOT NULL DEFAULT 'testeur'");

        if (Schema::hasColumn('projects', 'created_by')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};