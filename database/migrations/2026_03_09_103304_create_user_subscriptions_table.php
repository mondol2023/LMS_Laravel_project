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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained('subscription_packages')->onDelete('restrict');
            $table->timestamp('subscribed_at');
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);

            // Usage Tracking - Mock Tests
            $table->integer('full_mock_tests_used')->default(0);

            // Usage Tracking - Partial Mock Test Parts
            $table->integer('partial_reading_used')->default(0);
            $table->integer('partial_writing_used')->default(0);
            $table->integer('partial_listening_used')->default(0);
            $table->integer('partial_speaking_used')->default(0);

            // Usage Tracking - Practice Modules
            $table->integer('practice_writing_used')->default(0);
            $table->integer('practice_speaking_used')->default(0);

            $table->timestamps();

            // Index for faster queries
            $table->index(['user_id', 'is_active']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
