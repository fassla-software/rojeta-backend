<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_profiles', function (Blueprint $table) {
            $table->string('lab_name')->nullable()->after('user_id');
            $table->string('license_number')->nullable()->after('lab_name');
            $table->string('primary_contact_email')->nullable()->after('license_number');
            $table->string('phone_number')->nullable()->after('primary_contact_email');
            $table->text('description')->nullable()->after('phone_number');
            $table->boolean('is_verified')->default(false)->after('description');
            $table->string('verification_year')->nullable()->after('is_verified');
            $table->string('icon_url')->nullable()->after('verification_year');
            $table->json('settings')->nullable()->after('service_radius_km');
        });

        Schema::table('radiology_profiles', function (Blueprint $table) {
            $table->string('lab_name')->nullable()->after('user_id');
            $table->string('license_number')->nullable()->after('lab_name');
            $table->string('primary_contact_email')->nullable()->after('license_number');
            $table->string('phone_number')->nullable()->after('primary_contact_email');
            $table->text('description')->nullable()->after('phone_number');
            $table->boolean('is_verified')->default(false)->after('description');
            $table->string('verification_year')->nullable()->after('is_verified');
            $table->string('icon_url')->nullable()->after('verification_year');
            $table->json('settings')->nullable();
        });

        Schema::table('nursing_profiles', function (Blueprint $table) {
            $table->string('office_name')->nullable()->after('user_id');
            $table->string('license_number')->nullable()->after('office_name');
            $table->string('email')->nullable()->after('license_number');
            $table->string('phone_number')->nullable()->after('email');
            $table->text('description')->nullable()->after('phone_number');
            $table->string('verification_status')->default('pending')->after('description');
            $table->string('avatar_url')->nullable()->after('verification_status');
            $table->json('settings')->nullable();
        });

        Schema::table('hospital_profiles', function (Blueprint $table) {
            $table->string('hospital_name')->nullable()->after('user_id');
            $table->string('email')->nullable()->after('hospital_name');
            $table->string('phone_number')->nullable()->after('email');
            $table->text('description')->nullable()->after('phone_number');
            $table->json('settings')->nullable()->after('images');
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_profiles', function (Blueprint $table) {
            $table->dropColumn(['lab_name', 'license_number', 'primary_contact_email', 'phone_number', 'description', 'is_verified', 'verification_year', 'icon_url', 'settings']);
        });

        Schema::table('radiology_profiles', function (Blueprint $table) {
            $table->dropColumn(['lab_name', 'license_number', 'primary_contact_email', 'phone_number', 'description', 'is_verified', 'verification_year', 'icon_url', 'settings']);
        });

        Schema::table('nursing_profiles', function (Blueprint $table) {
            $table->dropColumn(['office_name', 'license_number', 'email', 'phone_number', 'description', 'verification_status', 'avatar_url', 'settings']);
        });

        Schema::table('hospital_profiles', function (Blueprint $table) {
            $table->dropColumn(['hospital_name', 'email', 'phone_number', 'description', 'settings']);
        });
    }
};
