<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_services', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('hospital_id')->constrained('users')->cascadeOnDelete();
            $table->string('title_key');
            $table->string('description_key');
            $table->boolean('is_enabled')->default(true);
            $table->string('icon_path')->nullable();
            $table->string('extra_info_key')->nullable();
            $table->string('extra_info_value')->nullable();
            $table->timestamps();
        });

        Schema::create('hospital_specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('hospital_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('has_emergency')->default(false);
            $table->timestamps();
        });

        Schema::create('hospital_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained('hospital_specialties')->cascadeOnDelete();
            $table->string('name');
            $table->string('role');
            $table->string('price')->nullable();
            $table->string('availability')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('icu_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('hospital_id')->constrained('users')->cascadeOnDelete();
            $table->string('room_number');
            $table->string('bed_id');
            $table->string('floor_wing')->nullable();
            $table->json('equipment_list')->nullable();
            $table->string('status')->default('available');
            $table->string('assigned_patient')->nullable();
            $table->timestamps();
        });

        Schema::create('incubators', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('hospital_id')->constrained('users')->cascadeOnDelete();
            $table->string('unit_id');
            $table->string('model')->nullable();
            $table->string('wing_section')->nullable();
            $table->json('monitoring_type')->nullable();
            $table->string('status')->default('ready');
            $table->decimal('temperature', 4, 1)->nullable();
            $table->integer('humidity')->nullable();
            $table->timestamps();
        });

        Schema::create('hospital_lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('hospital_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->string('category')->nullable();
            $table->boolean('home_collection_available')->default(false);
            $table->text('preparation_instructions')->nullable();
            $table->string('estimated_result_time')->nullable();
            $table->timestamps();
        });

        Schema::create('hospital_radiology_services', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('hospital_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->string('category')->nullable();
            $table->boolean('contrast_agent_required')->default(false);
            $table->text('preparation_instructions')->nullable();
            $table->string('estimated_duration')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_radiology_services');
        Schema::dropIfExists('hospital_lab_tests');
        Schema::dropIfExists('incubators');
        Schema::dropIfExists('icu_rooms');
        Schema::dropIfExists('hospital_staff');
        Schema::dropIfExists('hospital_specialties');
        Schema::dropIfExists('hospital_services');
    }
};
