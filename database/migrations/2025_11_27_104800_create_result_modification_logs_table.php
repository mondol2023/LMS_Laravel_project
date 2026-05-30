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
        Schema::create('result_modification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('modification_type'); // pdf_upload, speaking_update, writing_update, etc.
            $table->json('old_values')->nullable(); // Previous state
            $table->json('new_values')->nullable(); // New state
            $table->text('description')->nullable(); // Human-readable description
            $table->string('ip_address')->nullable(); // IP address of the user who made the change
            $table->text('user_agent')->nullable(); // User agent for audit trail
            $table->timestamps();

            // Add indexes for better query performance
            $table->index('result_id');
            $table->index('user_id');
            $table->index('modification_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_modification_logs');
    }
};
