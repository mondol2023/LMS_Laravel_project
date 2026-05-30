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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['academic', 'general_training']);
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->json('listening_content');
            $table->json('reading_content');
            $table->json('writing_tasks');
            $table->json('speaking_tasks');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
