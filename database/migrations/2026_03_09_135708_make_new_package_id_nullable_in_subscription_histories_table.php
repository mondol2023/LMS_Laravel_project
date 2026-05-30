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
        Schema::table('subscription_histories', function (Blueprint $table) {
            $table->dropForeign(['new_package_id']);
            $table->foreignId('new_package_id')->nullable()->change()->constrained('subscription_packages')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_histories', function (Blueprint $table) {
            $table->dropForeign(['new_package_id']);
            $table->foreignId('new_package_id')->nullable(false)->change()->constrained('subscription_packages')->cascadeOnDelete();
        });
    }
};
