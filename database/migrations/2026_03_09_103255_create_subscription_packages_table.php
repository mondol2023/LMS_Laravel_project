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
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('duration'); // Duration in days
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 10, 2)->nullable();
            $table->enum('discount_type', ['flat', 'percent'])->nullable();
            $table->timestamp('discount_till')->nullable();
            $table->boolean('is_active')->default(true);

            // Mock Test Limits
            $table->integer('full_mock_test_limit')->nullable(); // NULL = unlimited

            // Partial Mock Test - Individual Part Limits
            $table->integer('partial_reading_limit')->nullable();
            $table->integer('partial_writing_limit')->nullable();
            $table->integer('partial_listening_limit')->nullable();
            $table->integer('partial_speaking_limit')->nullable();

            // Practice Module Access
            $table->boolean('practice_reading_enabled')->default(false);
            $table->boolean('practice_listening_enabled')->default(false);
            $table->boolean('practice_writing_enabled')->default(false);
            $table->integer('practice_writing_limit')->nullable();
            $table->boolean('practice_speaking_enabled')->default(false);
            $table->integer('practice_speaking_limit')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_packages');
    }
};
