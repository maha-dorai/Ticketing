<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('mot_de_passe');
            $table->enum('role', ['testeur', 'developpeur', 'admin'])->default('testeur');
            $table->enum('statut', ['en_attente', 'actif', 'rejete', 'desactive'])->default('en_attente');
            $table->string('github_link')->nullable();
            $table->string('reset_token')->nullable();
            $table->timestamp('reset_token_expires')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
