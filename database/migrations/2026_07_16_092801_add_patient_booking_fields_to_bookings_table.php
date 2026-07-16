<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('service_id')->nullable()->after('service_name');
            $table->string('payment_type')->nullable()->after('payment_status');
            $table->string('promo_code')->nullable()->after('payment_type');
            $table->text('patient_notes')->nullable()->after('promo_code');
            $table->json('attachments')->nullable()->after('patient_notes');
            $table->json('address')->nullable()->after('attachments');
            $table->decimal('subtotal', 10, 2)->nullable()->after('fee');
            $table->decimal('discount', 10, 2)->nullable()->after('subtotal');
            $table->decimal('total_price', 10, 2)->nullable()->after('discount');
            $table->integer('points_earned')->nullable()->after('total_price');
            $table->string('payment_url')->nullable()->after('points_earned');
            $table->string('video_call_link')->nullable()->after('payment_url');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'service_id',
                'payment_type',
                'promo_code',
                'patient_notes',
                'attachments',
                'address',
                'subtotal',
                'discount',
                'total_price',
                'points_earned',
                'payment_url',
                'video_call_link',
            ]);
        });
    }
};
