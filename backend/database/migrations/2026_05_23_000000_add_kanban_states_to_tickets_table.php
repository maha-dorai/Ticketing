<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Étape 1 : élargir l'enum pour inclure les nouveaux états
        DB::statement("ALTER TABLE tickets MODIFY COLUMN etat ENUM(
            'OUVERT',
            'EN_COURS',
            'A_TESTER',
            'RECLAMATION',
            'VALIDE',
            'RESOLU',
            'FERME'
        ) NOT NULL DEFAULT 'OUVERT'");

        // Étape 2 : migrer les anciens états vers les nouveaux
        // RESOLU → VALIDE (dev a terminé et testeur a validé)
        DB::statement("UPDATE tickets SET etat = 'VALIDE' WHERE etat = 'RESOLU'");
        // FERME → VALIDE (ticket fermé = validé dans le nouveau workflow)
        DB::statement("UPDATE tickets SET etat = 'VALIDE' WHERE etat = 'FERME'");

        // Étape 3 : retirer les anciens états devenus inutiles
        DB::statement("ALTER TABLE tickets MODIFY COLUMN etat ENUM(
            'OUVERT',
            'EN_COURS',
            'A_TESTER',
            'RECLAMATION',
            'VALIDE'
        ) NOT NULL DEFAULT 'OUVERT'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY COLUMN etat ENUM(
            'OUVERT',
            'EN_COURS',
            'A_TESTER',
            'RECLAMATION',
            'VALIDE',
            'RESOLU',
            'FERME'
        ) NOT NULL DEFAULT 'OUVERT'");

        DB::statement("UPDATE tickets SET etat = 'RESOLU' WHERE etat = 'VALIDE'");

        DB::statement("ALTER TABLE tickets MODIFY COLUMN etat ENUM(
            'OUVERT',
            'EN_COURS',
            'RESOLU',
            'FERME'
        ) NOT NULL DEFAULT 'OUVERT'");
    }
};
