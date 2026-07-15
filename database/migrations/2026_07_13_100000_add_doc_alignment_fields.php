<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignUuid('hospital_id')->nullable()->after('clinic_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->json('working_hours')->nullable()->after('video_call_price');
        });

        Schema::table('laboratory_profiles', function (Blueprint $table) {
            $table->string('special_offer_title')->nullable()->after('settings');
            $table->text('special_offer_description')->nullable()->after('special_offer_title');
            $table->string('special_offer_discount')->nullable()->after('special_offer_description');
        });

        Schema::table('radiology_profiles', function (Blueprint $table) {
            $table->string('special_offer_title')->nullable()->after('settings');
            $table->text('special_offer_description')->nullable()->after('special_offer_title');
            $table->string('special_offer_discount')->nullable()->after('special_offer_description');
        });

        Schema::create('working_hour_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('event_name');
            $table->boolean('is_closed')->default(false);
            $table->string('opening_time')->nullable();
            $table->string('closing_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_hour_overrides');

        Schema::table('radiology_profiles', function (Blueprint $table) {
            $table->dropColumn(['special_offer_title', 'special_offer_description', 'special_offer_discount']);
        });

        Schema::table('laboratory_profiles', function (Blueprint $table) {
            $table->dropColumn(['special_offer_title', 'special_offer_description', 'special_offer_discount']);
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn('working_hours');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hospital_id');
        });
    }
};
