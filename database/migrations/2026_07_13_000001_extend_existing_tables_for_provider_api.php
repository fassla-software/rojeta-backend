<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->boolean('is_urgent')->default(false)->after('status');
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->decimal('original_fee', 8, 2)->nullable()->after('consultation_fee');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('original_fee');
            $table->text('location')->nullable()->after('discount_percent');
            $table->boolean('is_online')->default(false)->after('location');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('priority')->default('Normal')->after('type');
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->decimal('clinic_visit_price', 10, 2)->nullable()->after('photos');
            $table->decimal('follow_up_price', 10, 2)->nullable()->after('clinic_visit_price');
            $table->decimal('home_visit_price', 10, 2)->nullable()->after('follow_up_price');
            $table->decimal('video_call_price', 10, 2)->nullable()->after('home_visit_price');
        });

        Schema::table('clinic_services', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(true)->after('price');
            $table->string('currency')->default('EGP')->after('is_enabled');
        });

        Schema::table('doctor_profiles', function (Blueprint $table) {
            $table->string('payment_type')->nullable()->after('chat_price');
            $table->string('account_holder_name')->nullable()->after('payment_type');
            $table->string('bank_name')->nullable()->after('account_holder_name');
            $table->string('account_number')->nullable()->after('bank_name');
            $table->boolean('is_pro')->default(false)->after('account_number');
        });

        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->json('allergies')->nullable()->after('image');
            $table->json('chronic_conditions')->nullable()->after('allergies');
        });

        Schema::table('working_hours', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_closed');
            $table->json('time_slots')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['is_urgent', 'payment_method', 'original_fee', 'discount_percent', 'location', 'is_online']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('priority');
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn(['clinic_visit_price', 'follow_up_price', 'home_visit_price', 'video_call_price']);
        });

        Schema::table('clinic_services', function (Blueprint $table) {
            $table->dropColumn(['is_enabled', 'currency']);
        });

        Schema::table('doctor_profiles', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'account_holder_name', 'bank_name', 'account_number', 'is_pro']);
        });

        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->dropColumn(['allergies', 'chronic_conditions']);
        });

        Schema::table('working_hours', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'time_slots']);
        });
    }
};
