<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_profiles', function (Blueprint $table) {
            $table->string('medical_license')->nullable()->after('tax_id');
            $table->json('certificates')->nullable()->after('medical_license');
            $table->json('images')->nullable()->after('certificates');
        });
    }

    public function down(): void
    {
        Schema::table('hospital_profiles', function (Blueprint $table) {
            $table->dropColumn(['medical_license', 'certificates', 'images']);
        });
    }
};
