<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 30)->index();
            $table->string('purpose', 20)->index();
            $table->string('code_hash');
            $table->timestamp('code_expires_at');
            $table->string('verification_token', 255)->nullable()->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_otp_codes');
    }
};