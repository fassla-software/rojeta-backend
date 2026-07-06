<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('organization_name')->nullable();
            $table->string('organization_type')->nullable();
            $table->string('organization_number')->nullable();
            $table->text('about')->nullable();
            $table->string('logo')->nullable();
            $table->string('governorate')->nullable();
            $table->string('city')->nullable();
            $table->text('full_address')->nullable();
            $table->string('license_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
