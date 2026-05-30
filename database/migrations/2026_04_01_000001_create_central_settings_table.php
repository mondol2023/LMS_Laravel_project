<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('seller_status', ['active', 'suspended', 'blocked'])->default('active');
            $table->string('status_message')->nullable();
            $table->string('status_reason')->nullable();
            $table->timestamp('last_status_update')->nullable();
            $table->timestamps();
        });

        // Insert default row
        DB::table('central_settings')->insert([
            'seller_status' => 'active',
            'status_message' => 'Your account is active.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('central_settings');
    }
};
