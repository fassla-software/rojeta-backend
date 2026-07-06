<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('clinic_type')->nullable()->after('doctor_id');
            $table->unsignedInteger('consultation_time')->nullable()->after('name');
            $table->json('photos')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn(['clinic_type', 'consultation_time', 'photos']);
        });
    }
};
