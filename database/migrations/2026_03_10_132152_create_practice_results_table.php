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
        Schema::create('practice_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('module'); // listening, reading, writing, speaking
            $table->string('exam_name'); // Test 1, Test 2, etc.
            $table->integer('score'); // correct answers or marks
            $table->integer('total')->nullable(); // total questions (for listening/reading)
            $table->decimal('band_score', 3, 1)->nullable(); // 7.5, 8.0, etc.
            $table->text('details')->nullable(); // JSON data for additional details
            $table->timestamps();

            $table->index(['user_id', 'module']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_results');
    }
};
