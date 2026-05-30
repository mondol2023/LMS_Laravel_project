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
        Schema::create('highlights', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('exam_id', 20);
            $table->enum('section', ['writing', 'reading', 'listening']);
            $table->string('part', 10); // part1, part2, etc.
            $table->text('text'); // The highlighted text
            $table->string('color', 20)->default('yellow'); // Highlight color
            $table->integer('start_offset'); // Start position in the text
            $table->integer('end_offset'); // End position in the text
            $table->timestamps();

            // Index for efficient querying
            $table->index(['phone', 'exam_id', 'section', 'part'], 'highlights_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('highlights');
    }
};
