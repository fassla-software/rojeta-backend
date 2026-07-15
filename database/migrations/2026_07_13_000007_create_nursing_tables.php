<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_services', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('nursing_office_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('duration_tag')->nullable();
            $table->json('bullet_points')->nullable();
            $table->timestamps();
        });

        Schema::create('nursing_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('nursing_office_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('image_url')->nullable();
            $table->string('gender')->nullable();
            $table->string('status')->default('available');
            $table->json('skills')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_staff');
        Schema::dropIfExists('nursing_services');
    }
};
