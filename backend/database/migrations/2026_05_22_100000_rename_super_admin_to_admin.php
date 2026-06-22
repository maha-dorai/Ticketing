<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur','developpeur','chef_de_projet','admin','super_admin') NOT NULL DEFAULT 'testeur'");
        }
        DB::statement("UPDATE users SET role = 'admin' WHERE role = 'super_admin'");
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur','developpeur','chef_de_projet','admin') NOT NULL DEFAULT 'testeur'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur','developpeur','chef_de_projet','admin','super_admin') NOT NULL DEFAULT 'testeur'");
        }
        DB::statement("UPDATE users SET role = 'super_admin' WHERE role = 'admin'");
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('testeur','developpeur','chef_de_projet','super_admin') NOT NULL DEFAULT 'testeur'");
        }
    }
};
