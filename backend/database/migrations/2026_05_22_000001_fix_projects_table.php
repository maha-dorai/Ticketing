<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE projects MODIFY COLUMN statut ENUM('ouvert', 'en_cours', 'ferme', 'archive') NOT NULL DEFAULT 'ouvert'");
        }
        DB::statement("UPDATE projects SET statut = 'archive' WHERE statut = 'ferme'");
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE projects MODIFY COLUMN statut ENUM('ouvert', 'en_cours', 'archive') NOT NULL DEFAULT 'ouvert'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE projects MODIFY COLUMN statut ENUM('ouvert', 'en_cours', 'ferme', 'archive') NOT NULL DEFAULT 'ouvert'");
        }
        DB::statement("UPDATE projects SET statut = 'ferme' WHERE statut = 'archive'");
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE projects MODIFY COLUMN statut ENUM('ouvert', 'en_cours', 'ferme') NOT NULL DEFAULT 'ouvert'");
        }
    }
};