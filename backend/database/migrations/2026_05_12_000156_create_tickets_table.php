<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('priorite', ['BASSE', 'MOYENNE', 'HAUTE', 'CRITIQUE'])->default('BASSE');
            $table->enum('etat', ['OUVERT', 'EN_COURS', 'RESOLU', 'FERME'])->default('OUVERT');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('testeur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('developpeur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
