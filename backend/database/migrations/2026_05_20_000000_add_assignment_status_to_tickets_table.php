<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('assignment_status', ['none', 'pending', 'approved', 'rejected'])
                ->default('none')
                ->after('developpeur_id');
            $table->foreignId('proposed_developpeur_id')
                ->nullable()
                ->after('assignment_status')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Tickets déjà assignés avant cette migration → considérés comme approuvés
        DB::table('tickets')
            ->whereNotNull('developpeur_id')
            ->update(['assignment_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['proposed_developpeur_id']);
            $table->dropColumn(['assignment_status', 'proposed_developpeur_id']);
        });
    }
};
