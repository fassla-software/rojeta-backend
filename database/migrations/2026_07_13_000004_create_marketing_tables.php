<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_packages', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name_key');
            $table->string('posts_per_month');
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('yearly_price', 10, 2);
            $table->boolean('is_most_popular')->default(false);
            $table->json('platforms');
            $table->json('features');
            $table->timestamps();
        });

        Schema::create('marketing_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->string('package_id');
            $table->foreign('package_id')->references('id')->on('marketing_packages')->cascadeOnDelete();
            $table->string('billing_cycle');
            $table->integer('posts_used')->default(0);
            $table->integer('total_posts');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_subscriptions');
        Schema::dropIfExists('marketing_packages');
    }
};
