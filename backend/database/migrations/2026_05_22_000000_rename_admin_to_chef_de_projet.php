<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // 1. Add chef_de_projet while keeping admin (prevents truncation)
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur', 'developpeur', 'admin', 'chef_de_projet', 'super_admin') NOT NULL DEFAULT 'testeur'");
        }
        
        // 2. Migrate existing rows
        DB::statement("UPDATE users SET role = 'chef_de_projet' WHERE role = 'admin'");
        
        if (DB::getDriverName() === 'mysql') {
            // 3. Remove admin from enum
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur', 'developpeur', 'chef_de_projet', 'super_admin') NOT NULL DEFAULT 'testeur'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur', 'developpeur', 'admin', 'chef_de_projet', 'super_admin') NOT NULL DEFAULT 'testeur'");
        }
        
        DB::statement("UPDATE users SET role = 'admin' WHERE role = 'chef_de_projet'");
        
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur', 'developpeur', 'admin', 'super_admin') NOT NULL DEFAULT 'testeur'");
        }
    }
};