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
        // Drop table if it exists to ensure clean state
        Schema::dropIfExists('drafts');

        Schema::create('drafts', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('exam_id', 20);
            $table->enum('section', ['writing', 'reading', 'listening']);
            $table->string('part', 10); // part1, part2, part3, part4
            $table->string('question_key', 10); // q1, q2, etc or part1, part2 for writing
            $table->text('answer')->nullable();
            $table->timestamp('last_saved_at')->useCurrent();
            $table->timestamps();

            // Composite unique index to ensure one draft per phone/exam/section/part/question
            $table->unique(['phone', 'exam_id', 'section', 'part', 'question_key'], 'drafts_unique_constraint');

            // Index for efficient querying
            $table->index(['phone', 'exam_id'], 'drafts_user_exam_idx');
            $table->index(['phone', 'exam_id', 'section'], 'drafts_user_section_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drafts');
    }
};
