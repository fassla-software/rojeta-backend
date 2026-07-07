<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->onDelete('set null');
            $table->date('date');
            $table->time('time');
            $table->string('status')->default('pending'); // pending, confirmed, completed, cancelled
            $table->string('visit_type')->default('clinic_visit'); // clinic_visit, home_visit, home_sample
            $table->string('payment_status')->default('pending');
            $table->decimal('consultation_fee', 8, 2)->default(0);
            $table->text('important_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
