<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing fields to doctor_profiles
        Schema::table('doctor_profiles', function (Blueprint $table) {
            $table->string('title')->nullable()->after('specialty'); // Consultant, Specialist, etc.
            $table->decimal('rating', 3, 2)->default(0)->after('title');
            $table->integer('reviews_count')->default(0)->after('rating');
            $table->string('languages')->nullable()->after('reviews_count'); // JSON string "Arabic,English"
            $table->string('image_url')->nullable()->after('languages');
            $table->boolean('is_ad')->default(false)->after('image_url');
        });

        // Add missing fields to patient_profiles
        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('phone_number');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->integer('points')->default(0)->after('gender');
            $table->string('image')->nullable()->after('points');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_profiles', function (Blueprint $table) {
            $table->dropColumn(['title', 'rating', 'reviews_count', 'languages', 'image_url', 'is_ad']);
        });

        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->dropColumn(['date_of_birth', 'gender', 'points', 'image']);
        });
    }
};
