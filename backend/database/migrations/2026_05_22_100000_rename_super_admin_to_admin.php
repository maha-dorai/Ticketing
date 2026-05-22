<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur','developpeur','chef_de_projet','admin','super_admin') NOT NULL DEFAULT 'testeur'");
        DB::statement("UPDATE users SET role = 'admin' WHERE role = 'super_admin'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur','developpeur','chef_de_projet','admin') NOT NULL DEFAULT 'testeur'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur','developpeur','chef_de_projet','admin','super_admin') NOT NULL DEFAULT 'testeur'");
        DB::statement("UPDATE users SET role = 'super_admin' WHERE role = 'admin'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur','developpeur','chef_de_projet','super_admin') NOT NULL DEFAULT 'testeur'");
    }
};
