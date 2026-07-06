<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('specialty');
            $table->string('sub_specialty')->nullable();
            $table->integer('experience_years')->nullable();
            $table->text('education')->nullable();
            $table->text('about')->nullable();
            $table->string('medical_license')->nullable();
            $table->decimal('home_visit_price', 10, 2)->nullable();
            $table->decimal('call_price', 10, 2)->nullable();
            $table->decimal('chat_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};
