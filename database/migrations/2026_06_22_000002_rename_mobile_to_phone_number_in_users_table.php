<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add phone_number if missing
        if (! Schema::hasColumn('users', 'phone_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone_number')->nullable()->after('email');
            });
        }

        // Copy values from mobile to phone_number if mobile exists
        if (Schema::hasColumn('users', 'mobile')) {
            DB::table('users')
                ->whereNotNull('mobile')
                ->update(['phone_number' => DB::raw('mobile')]);

            // Attempt to drop old column; some drivers (sqlite) may not support it.
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn('mobile');
                });
            } catch (\Throwable $e) {
                // Ignore: if the platform doesn't support dropping columns, keep both.
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore mobile column if missing
        if (! Schema::hasColumn('users', 'mobile')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('mobile')->nullable()->after('email');
            });
        }

        // Copy back phone_number -> mobile
        if (Schema::hasColumn('users', 'phone_number')) {
            DB::table('users')
                ->whereNotNull('phone_number')
                ->update(['mobile' => DB::raw('phone_number')]);

            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn('phone_number');
                });
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }
};
