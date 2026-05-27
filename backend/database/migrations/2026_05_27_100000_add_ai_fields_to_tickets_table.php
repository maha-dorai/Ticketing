<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('categorie_ia', [
                'BUG', 'PERFORMANCE', 'SECURITE', 'UI_UX',
                'BASE_DE_DONNEES', 'API', 'CONFIGURATION', 'AUTRE', 'NON_CLASSE'
            ])->nullable()->after('description');

            $table->enum('priorite_ia', ['BASSE', 'MOYENNE', 'HAUTE', 'CRITIQUE'])
                  ->nullable()->after('categorie_ia');

            $table->text('solution_ia')->nullable()->after('priorite_ia');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['categorie_ia', 'priorite_ia', 'solution_ia']);
        });
    }
};
