<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_services', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider_role');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->boolean('is_home_collection_available')->default(false);
            $table->string('turnaround_time')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });

        Schema::create('lab_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider_role');
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('working_hours')->nullable();
            $table->boolean('is_home_collection_available')->default(false);
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        Schema::create('lab_home_visit_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('provider_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('provider_role');
            $table->boolean('is_service_visible')->default(true);
            $table->decimal('collection_fee', 10, 2)->default(0);
            $table->decimal('minimum_booking_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('lab_service_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')->constrained('lab_home_visit_configs')->cascadeOnDelete();
            $table->string('name');
            $table->integer('radius_km')->default(10);
            $table->integer('techs_available')->default(1);
            $table->timestamps();
        });

        Schema::create('lab_time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')->constrained('lab_home_visit_configs')->cascadeOnDelete();
            $table->string('slot_type');
            $table->string('time');
            $table->string('am_pm');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_time_slots');
        Schema::dropIfExists('lab_service_areas');
        Schema::dropIfExists('lab_home_visit_configs');
        Schema::dropIfExists('lab_branches');
        Schema::dropIfExists('lab_services');
    }
};
