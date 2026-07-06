<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('laboratory_profiles', 'tax_id')) {
                $table->string('tax_id')->nullable()->after('service_radius_km');
            }

            if (! Schema::hasColumn('laboratory_profiles', 'medical_license')) {
                $table->string('medical_license')->nullable()->after('tax_id');
            }

            if (! Schema::hasColumn('laboratory_profiles', 'certificates')) {
                $table->json('certificates')->nullable()->after('medical_license');
            }

            if (! Schema::hasColumn('laboratory_profiles', 'images')) {
                $table->json('images')->nullable()->after('certificates');
            }
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('laboratory_profiles', 'images')) {
                $table->dropColumn('images');
            }

            if (Schema::hasColumn('laboratory_profiles', 'certificates')) {
                $table->dropColumn('certificates');
            }

            if (Schema::hasColumn('laboratory_profiles', 'medical_license')) {
                $table->dropColumn('medical_license');
            }

            if (Schema::hasColumn('laboratory_profiles', 'tax_id')) {
                $table->dropColumn('tax_id');
            }
        });
    }
};