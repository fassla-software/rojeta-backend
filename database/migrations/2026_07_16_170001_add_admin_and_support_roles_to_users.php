<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'doctor',
            'hospital',
            'laboratory',
            'radiology',
            'nursing',
            'patient',
            'admin',
            'support'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'doctor',
            'hospital',
            'laboratory',
            'radiology',
            'nursing',
            'patient'
        ) NOT NULL");
    }
};
