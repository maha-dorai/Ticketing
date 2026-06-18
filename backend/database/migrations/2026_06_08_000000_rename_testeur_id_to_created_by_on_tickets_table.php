<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Drop the existing FK constraint before renaming
            $table->dropForeign(['testeur_id']);

            // Rename the column
            $table->renameColumn('testeur_id', 'created_by');
        });

        Schema::table('tickets', function (Blueprint $table) {
            // Re-add the FK constraint under the new column name
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->renameColumn('created_by', 'testeur_id');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('testeur_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }
};
