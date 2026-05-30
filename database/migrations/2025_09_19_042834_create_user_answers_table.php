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
        Schema::create('user_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('test_attempts')->onDelete('cascade');
            $table->enum('section', ['listening', 'reading', 'writing', 'speaking']);
            $table->integer('question_number');
            $table->text('user_answer');
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_earned', 3, 1)->default(0);
            $table->timestamps();

            $table->index(['attempt_id', 'section']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_answers');
    }
};
