<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider_type');
            $table->foreignUuid('patient_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('branchVisit');
            $table->string('patient_name');
            $table->string('patient_phone');
            $table->string('service_name');
            $table->string('location_name')->nullable();
            $table->date('date');
            $table->time('time');
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');
            $table->decimal('fee', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('users')->cascadeOnDelete();
            $table->text('diagnosis')->nullable();
            $table->text('medication_details')->nullable();
            $table->text('laboratory_tests')->nullable();
            $table->text('radiology_tests')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('prescription_path')->nullable();
            $table->timestamps();
        });

        Schema::create('visit_records', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->date('date');
            $table->string('type');
            $table->text('diagnosis')->nullable();
            $table->text('prescription')->nullable();
            $table->text('notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->timestamps();
        });

        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('patient_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('tests_performed');
            $table->text('results')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_normal')->default(true);
            $table->timestamps();
        });

        Schema::create('doctor_vacations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });

        Schema::create('provider_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('role');
            $table->json('settings');
            $table->timestamps();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider_role');
            $table->string('patient_name');
            $table->string('visit_type');
            $table->string('status')->default('completed');
            $table->date('transaction_date');
            $table->time('transaction_time')->nullable();
            $table->decimal('service_fee', 10, 2);
            $table->decimal('platform_commission', 10, 2)->default(0);
            $table->decimal('earning', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('provider_settings');
        Schema::dropIfExists('doctor_vacations');
        Schema::dropIfExists('lab_results');
        Schema::dropIfExists('visit_records');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('bookings');
    }
};
