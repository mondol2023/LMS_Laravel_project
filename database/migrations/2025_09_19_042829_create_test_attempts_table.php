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
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('test_id')->constrained()->onDelete('cascade');
            $table->integer('attempt_number')->default(1);
            $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->decimal('listening_score', 3, 1)->nullable();
            $table->decimal('reading_score', 3, 1)->nullable();
            $table->decimal('writing_score', 3, 1)->nullable();
            $table->decimal('speaking_score', 3, 1)->nullable();
            $table->decimal('overall_band', 2, 1)->nullable();
            $table->json('time_spent')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'test_id', 'attempt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};
